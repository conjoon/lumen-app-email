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
    Conjoon\Mail\Client\Data\MessageKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MessageItem,
    Conjoon\Mail\Client\Data\PreviewableMessageItem,
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
     * Creates a \Horde_Imap_Client_Socket with the specified account informations.
     *
     * @param MailAccount $account
     *
     * @return \Horde_Imap_Client_Socket
     */
    public function connect(MailAccount $account) : \Horde_Imap_Client_Socket {

        return new \Horde_Imap_Client_Socket(array(
            'username' => $account->getInboxUser(),
            'password' => $account->getInboxPassword(),
            'hostspec' => $account->getInboxAddress(),
            'port'     => $account->getInboxPort(),
            'secure'   => $account->getInboxSsl() ? 'ssl' : null
        ));

    }

// --------------------------
//   MailClient- Interface
// --------------------------
    /**
     * @inheritdoc
     */
    public function getMessageItemList(
        MailAccount $account, string $mailFolderId, array $options = null, callable $previewTextProcessor = null) :MessageItemList {

        try {
            $client = $this->connect($account);

            $results      = $this->queryItems($client, $mailFolderId, $options);
            $fetchedItems = $this->fetchMessageItems($client, $results["match"], $mailFolderId, $options);
            $messageItems = $this->buildMessageItems(
                $client, $mailFolderId, $fetchedItems, $previewTextProcessor
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
    public function getTotalMessageCount(MailAccount $account, string $mailFolderId) : int {

        try {
            $client  = $this->connect($account);
            $results = $this->queryItems($client, $mailFolderId);

            return count($results["match"]);

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }


    }


    /**
     * @inheritdoc
     */
    public function getUnreadMessageCount(MailAccount $account, string $mailFolderId) : int {

        try {

            $client = $this->connect($account);
            $status = $client->status($mailFolderId, \Horde_Imap_Client::STATUS_UNSEEN);

            return $status["unseen"];

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MailAccount $account, MessageKey $key, array $options = null) :?MessageItem {

        try {

            $client = $this->connect($account);
            $mailFolderId = $key->getMailFolderId();
            $fetchedItems = $this->fetchMessageItems(
                $client,
                new \Horde_Imap_Client_Ids($key->getId()), $mailFolderId, []
            );
            $messageItem = $this->buildMessageItems(
                $client, $mailFolderId, $fetchedItems
            )[0];

            return $messageItem;

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $response;

    }


    /**
     * @inheritdoc
     */
    public function getMessageBody(MailAccount $account, MessageKey $key) :MessageBody {

        $mailFolderId  = $key->getMailFolderId();
        $messageItemId = $key->getId();

        try {

            $client = $this->connect($account);

            $query = new \Horde_Imap_Client_Fetch_Query();
            $query->structure();


            $uid = new \Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();

            $d = $this->getContents($client, $messageStructure, $mailFolderId, $messageItemId, ["plain", "html"]);

            $body = new MessageBody($key);

            if ($d["html"]["content"]) {
                $htmlPart = new MessagePart();
                $htmlPart->setContents($d["html"]["content"])
                    ->setCharset($d["html"]["charset"])
                    ->setMimeType("text/html");
                $body->setTextHtml($htmlPart);
            }

            $plainPart = new MessagePart();
            $plainPart->setContents($d["plain"]["content"])
                      ->setCharset($d["plain"]["charset"])
                      ->setMimeType("text/plain");
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
     * Transform the passed list of $items to an instance of MessageItemList.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $mailFolderId
     * @param array $items
     * @param callable $previewTextProcessor
     *
     * @return MessageItemList
     *
     * @see queryItems
     */
    protected function buildMessageItems(
        \Horde_Imap_Client_Socket $client,  string $mailFolderId, array $items, callable $previewTextProcessor = null
    ) :MessageItemList {

        $messageItems = new MessageItemList;

        foreach ($items as $item) {

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

            $messageKey = new MessageKey($mailFolderId, (string)$item->getUid());

            // parse body
            $messageStructure = $item->getStructure();

            $options = ["hasAttachments"];
            if ($previewTextProcessor !== null) {
                $options[] = "plain";
            }
            $d = $this->getContents($client, $messageStructure, $mailFolderId, (string)$item->getUid(), $options);

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

            $messageItem = null;

            if ($previewTextProcessor !== null) {

                $data["previewText"] = call_user_func(
                    $previewTextProcessor, $d["plain"]["content"], $d["plain"]["charset"]
                );

                $messageItem = new PreviewableMessageItem(
                    $messageKey,
                    $data
                );

            } else {
                $messageItem = new MessageItem(
                    $messageKey,
                    $data
                );
            }


            $messageItems[] = $messageItem;
        }

        return $messageItems;
    }


    /**
     * Sends a query against the currently connected IMAP server for retrieving
     * a list of messages based on the specified $options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $mailFolderId The global name of the mailbox to search in
     * @param array $options An array with the following options
     * - start (integer) The start position from where to return the results
     * - limit (integer) The number of items to return.
     *
     * @return array
     */
    protected function queryItems(\Horde_Imap_Client_Socket $client, string $mailFolderId, array $options = null) :array {

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
        $results = $client->search($mailFolderId, $searchQuery, $searchOptions);

        return $results;
    }


    /**
     * Returns contents of the mail. Possible return keys are based on the passed
     * $options "html" (string), "plain" (string) and/or "hasAttachments" (bool)
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param $messageStructure
     * @param string $mailFolderId
     * @param string $messageItemId
     * @param array $options
     *
     * @return array
     */
    protected function getContents(
        \Horde_Imap_Client_Socket $client, $messageStructure, string $mailFolderId,
        string $messageItemId, array $options) :array {

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
            $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($messageItemId)]
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