<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
 * USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
declare(strict_types=1);

namespace Conjoon\Mail\Client\Imap;

use Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Data\MailAccount,
    Conjoon\Mail\Client\Data\MessageBody,
    Conjoon\Mail\Client\Data\MessagePart,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\CompoundKey\CompoundKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\ListMessageItem,
    Conjoon\Mail\Client\Data\MessageItem,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Data\MessageItemList;

/**
 * Class HordeClient.
 * Default implementation of a HordeClient, using \Horde_Imap_Client to communicate with
 * Imap Mail Servers.
 *
 * @package Conjoon\Mail\Client\Imap
 */
class HordeClient implements MailClient {

    /**
     * @var MailAccount
     */
    protected $mailAccount;

    /**
     * @var Horde_Imap_Client_Socket
     */
    protected $socket;

    /**
     * HordeClient constructor.
     * @param MailAccount $account
     */
    public function __construct(MailAccount $account) {
        $this->mailAccount = $account;
    }


    /**
     * Returns the MailAccount providing connection info for the CompoundKey
     * .
     * @param CompoundKey $key
     *
     * @return MailAccount|null
     */
    public function getMailAccount(CompoundKey $key) {

        if ($this->mailAccount->getId() !== $key->getMailAccountId()) {
            return null;
        }

        return $this->mailAccount;
    }


    /**
     * Creates a \Horde_Imap_Client_Socket.
     * Looks up the MailAccount used by this instance and throws an Exception
     * if the passed CompoundKey does not share the same mailAccountId with the id
     * of "this" MailAccount.
     * Returns a \Horde_Imap_Client_Socket if connecting was successfull.
     *
     * @param CompoundKey $key
     *
     * @return \Horde_Imap_Client_Socket
     *
     * @throws ImapClientException if the MailAccount used with this Client does not share
     * the same mailAccountId with the $key
     */
    public function connect(CompoundKey $key) : \Horde_Imap_Client_Socket {

        if (!$this->socket) {
            $account = $this->getMailAccount($key);

            if (!$account) {
                throw new ImapClientException(
            "The key's \"mailAccountId\" is not the same as the id of the MailAccount this " .
                    "class was configured with."
                );
            }

            $this->socket = new \Horde_Imap_Client_Socket(array(
                'username' => $account->getInboxUser(),
                'password' => $account->getInboxPassword(),
                'hostspec' => $account->getInboxAddress(),
                'port'     => $account->getInboxPort(),
                'secure'   => $account->getInboxSsl() ? 'ssl' : null
            ));
        }

        return $this->socket;
    }

// --------------------------
//   MailClient- Interface
// --------------------------
    /**
     * @inheritdoc
     */
    public function getMessageItemList(FolderKey $key, array $options = null) :MessageItemList {

        try {
            $client = $this->connect($key);

            $results      = $this->queryItems($client, $key, $options);
            $fetchedItems = $this->fetchMessageItems($client, $results["match"], $key->getId(), $options);
            $messageItems = $this->buildMessageItems(
                $client, $key, $fetchedItems, true
            );

            return $messageItems;

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $response;
    }


    /**
     * @inheritdoc
     */
    public function getTotalMessageCount(FolderKey $key) : int {

        try {
            $client  = $this->connect($key);
            $results = $this->queryItems($client, $key);

            return count($results["match"]);

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }


    }


    /**
     * @inheritdoc
     */
    public function getUnreadMessageCount(FolderKey $key) : int {

        try {

            $client = $this->connect($key);
            $status = $client->status($key->getId(), \Horde_Imap_Client::STATUS_UNSEEN);

            return $status["unseen"];

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MessageKey $key, array $options = null) :?MessageItem {

        try {

            $client = $this->connect($key);
            $mailFolderId = $key->getMailFolderId();
            $fetchedItems = $this->fetchMessageItems(
                $client,
                new \Horde_Imap_Client_Ids($key->getId()), $mailFolderId, []
            );
            $messageItem = $this->buildMessageItem(
                $client, new FolderKey($key->getMailAccountId(), $mailFolderId), $fetchedItems[0]
            );

            return $messageItem;

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $response;

    }


    /**
     * @inheritdoc
     */
    public function getMessageBody(MessageKey $key) :MessageBody {

        $mailFolderId  = $key->getMailFolderId();
        $messageItemId = $key->getId();

        try {

            $client = $this->connect($key);

            $query = new \Horde_Imap_Client_Fetch_Query();
            $query->structure();


            $uid = new \Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();

            $d = $this->getContents($client, $messageStructure, $key, ["plain", "html"]);

            $body = new MessageBody($key);

            if ($d["html"]["content"]) {
                $htmlPart = new MessagePart($d["html"]["content"], $d["html"]["charset"], "text/html");
                $body->setTextHtml($htmlPart);
            }

            $plainPart = new MessagePart($d["plain"]["content"], $d["plain"]["charset"],"text/plain");
            $body->setTextPlain($plainPart);


        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }


        return $body;
    }


// -------------------
//   Helper
// -------------------

    /**
     * Fetches a list of messages from the server, considering start & limit options passed
     * with $options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param \Horde_Imap_Client_Ids $searchResultIds
     * @param string $mailFolderId
     * @param array $options
     *
     * @return array
     */
    protected function fetchMessageItems(
        \Horde_Imap_Client_Socket $client, \Horde_Imap_Client_Ids $searchResultIds, string $mailFolderId, array $options) :array {

        $start = isset($options["start"]) ? intval($options["start"]) : -1;
        $limit = isset($options["limit"])  ? intval($options["limit"]) : -1;

        if ($start >= 0 && $limit > 0) {
            $rangeList = new \Horde_Imap_Client_Ids();
            foreach ($searchResultIds as $key => $entry) {
                if ($key >= $start && $key < $start + $limit) {
                    $rangeList->add($entry);
                }
            }

            $orderedList = $rangeList->ids;
        } else {
            $orderedList = $searchResultIds->ids;
            $rangeList = $searchResultIds;
        }


        // fetch IMAP
        $fetchQuery = new \Horde_Imap_Client_Fetch_Query();
        $fetchQuery->flags();
        $fetchQuery->size();
        $fetchQuery->envelope();
        $fetchQuery->structure();

        $fetchResult = $client->fetch($mailFolderId, $fetchQuery, ['ids' => $rangeList]);

        $final = [];
        foreach ($orderedList as $id) {
            $final[] = $fetchResult[$id];
        }

        return $final;
    }


    /**
     * Transform the passed $item into an instance of MessageItem.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param FolderKey $key
     * @param $item
     *
     * @return MessageItem
     *
     * @see queryItems
     */
    protected function buildMessageItem(\Horde_Imap_Client_Socket $client,  FolderKey $key, $item) : MessageItem {

        $result = $result = $this->getItemStructure($client, $item, $key, []);

        return new MessageItem(
            $result["messageKey"],
            $result["data"]
        );

    }


    /**
     * Transform the passed list of $items to an instance of MessageItemList.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param FolderKey $key
     * @param array $items
     *
     * @return MessageItemList
     *
     * @see queryItems
     */
    protected function buildMessageItems(\Horde_Imap_Client_Socket $client,  FolderKey $key, array $items
    ) :MessageItemList {

        $messageItems = new MessageItemList;

        $options = ["plain", "html"];

        foreach ($items as $item) {

            $result = $this->getItemStructure($client, $item, $key, $options);
            $data = $result["data"];

            $d = $result["options"];

            $messageItem = null;

            $contentKey = "plain";

            if (!$d['plain']['content'] && $d['html']['content']) {
                $contentKey = "html";
            }

            $messagePart = new MessagePart(
                $d[$contentKey]['content'], $d[$contentKey]['charset'],
                $contentKey === "plain" ? "text/plain" : "text/html"
            );

            $messageKey = $result["messageKey"];

            $messageItem = new ListMessageItem(
                $messageKey,
                $data,
                $messagePart
            );

            $messageItems[] = $messageItem;
        }

        return $messageItems;
    }


    /**
     * Returns the structure of the requested items as an array, along with additional information,
     * that can be used for constructor-data for AbstractMessageItem.
     *
     * @param $client
     * @param $item
     * @param FolderKey
     * @param $options
     *
     * @return array data with the item structure, options holding additional requested data (passed via $options)
     * and messageKey holding the generated MessageKey for the item.
     */
    protected function getItemStructure(\Horde_Imap_Client_Socket $client, $item, FolderKey $key, array $options = []) :array {

        $envelope = $item->getEnvelope();
        $flags    = $item->getFlags();

        $from = null;
        foreach ($envelope->from as $from) {
            if ($from->bare_address) {
                $from = new MailAddress(
                    $from->bare_address, $from->personal ?: $from->bare_address
                );
            }
        }

        $tos = new MailAddressList;
        foreach ($envelope->to as $to) {
            if ($to->bare_address) {
                $tos[] = new MailAddress($to->bare_address, $to->personal ?: $to->bare_address);
            }
        }

        $messageKey = new MessageKey($key->getMailAccountId(), $key->getId(), (string)$item->getUid());

        // parse body
        $messageStructure = $item->getStructure();

        $options = array_merge(["hasAttachments"], $options);


        $d = $this->getContents($client, $messageStructure, $messageKey, $options);

        $data = [
            "from"           => $from,
            "to"             => $tos,
            "size"           => $item->getSize(),
            "subject"        => $envelope->subject,
            "date"           => $envelope->date,
            "seen"           => in_array(\Horde_Imap_Client::FLAG_SEEN, $flags),
            "answered"       => in_array(\Horde_Imap_Client::FLAG_ANSWERED, $flags),
            "draft"          => in_array(\Horde_Imap_Client::FLAG_DRAFT, $flags),
            "flagged"        => in_array(\Horde_Imap_Client::FLAG_FLAGGED, $flags),
            "recent"         => in_array(\Horde_Imap_Client::FLAG_RECENT, $flags),
            "hasAttachments" => $d["hasAttachments"]
        ];

       return [
           "data"       => $data,
           "options"    => $d,
           "messageKey" => $messageKey
        ];

    }


    /**
     * Sends a query against the currently connected IMAP server for retrieving
     * a list of messages based on the specified $options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param FolderKey $key The key of the folder to query
     * @param array $options An array with the following options
     * - start (integer) The start position from where to return the results
     * - limit (integer) The number of items to return.
     *
     * @return array
     */
    protected function queryItems(\Horde_Imap_Client_Socket $client, FolderKey $key, array $options = null) :array {

        $searchOptions = [];

        if ($options !== null) {
            $sort = isset($options["sort"]) ? $options["sort"] : [["property" => "date", "direction" => "DESC"]];
            $sort = $sort[0];

            $sortInfo = [];

            if ($sort["direction"] === "DESC") {
                $sortInfo[] = \Horde_Imap_Client::SORT_REVERSE;
            }

            switch ($sort["property"]) {
                case 'subject':
                    $sortInfo[] = \Horde_Imap_Client::SORT_SUBJECT;
                    break;
                case 'to':
                    $sortInfo[] = \Horde_Imap_Client::SORT_TO;
                    break;
                case 'from':
                    $sortInfo[] = \Horde_Imap_Client::SORT_FROM;
                    break;
                case 'date':
                    $sortInfo[] = \Horde_Imap_Client::SORT_DATE;
                    break;
                case 'size':
                    $sortInfo[] = \Horde_Imap_Client::SORT_SIZE;
                    break;
            }

            $searchOptions = ["sort" => $sortInfo];
        }


        // search and narrow down list
        $searchQuery = new \Horde_Imap_Client_Search_Query();
        $results = $client->search($key->getId(), $searchQuery, $searchOptions);

        return $results;
    }


    /**
     * Returns contents of the mail. Possible return keys are based on the passed
     * $options "html" (string), "plain" (string) and/or "hasAttachments" (bool)
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param $messageStructure
     * @param MessageKey $key
     * @param array $options
     *
     * @return array
     */
    protected function getContents(
        \Horde_Imap_Client_Socket $client, $messageStructure, MessageKey $key, array $options) :array {

        $typeMap          = $messageStructure->contentTypeMap();
        $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();
        foreach ($typeMap as $part => $type) {
            $bodyQuery->bodyPart($part, array(
                'peek'   => true
            ));
        }

        $ret             = [];
        $findHtml        = in_array("html", $options);
        $findPlain       = in_array("plain", $options);
        $findAttachments = in_array("hasAttachments", $options);

        if ($findHtml) {
            $ret["html"] = ["content" => "", "charset" => ""];
        }
        if ($findPlain) {
            $ret["plain"] = ["content" => "", "charset" => ""];
        }
        if ($findAttachments) {
            $ret["hasAttachments"] = false;
        }

        $messageData = $client->fetch(
            $key->getMailFolderId(), $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($key->getId())]
        )->first();

        if ($messageData) {
            $plainPartId = $messageStructure->findBody('plain');
            $htmlPartId  = $messageStructure->findBody('html');

            foreach ($typeMap as $part => $type) {

                $body = $messageStructure->getPart($part);

                if ($findAttachments && $body->isAttachment()) {
                    $ret["hasAttachments"] = true;
                    if (!$findHtml && !$findPlain) {
                        break;
                    }
                }

                if ($findHtml || $findPlain) {
                    $content = $messageData->getBodyPart($part);
                    if (!$messageData->getBodyPartDecode($part)) {
                        // Decode the content.
                        $body->setContents($content);
                        $content = $body->getContents();
                    }

                    if ($findHtml && (string)$part === $htmlPartId) {
                        $ret["html"] = ["content" => $content, "charset" => $body->getCharset()];
                    } else if ($findPlain && (string)$part === $plainPartId) {
                        $ret["plain"] = ["content" => $content, "charset" => $body->getCharset()];
                    }
                }

            }
        }

        return $ret;
    }



}