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
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\CompoundKey\CompoundKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Folder\MailFolderList,
    Conjoon\Mail\Client\Folder\ListMailFolder,
    Conjoon\Mail\Client\Attachment\FileAttachmentList,
    Conjoon\Mail\Client\Attachment\FileAttachment,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;

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
     * or string (which will be treated as the id of the MailAccount to look up).
     *
     * @param CompoundKey|string $key
     *
     * @return MailAccount|null
     */
    public function getMailAccount($key) {

        $id = $key;

        if ($key instanceof CompoundKey) {
            $id = $key->getMailAccountId();
        }

        if ($this->mailAccount->getId() !== $id) {
            return null;
        }

        return $this->mailAccount;
    }


    /**
     * Creates a \Horde_Imap_Client_Socket.
     * Looks up the MailAccount used by this instance and throws an Exception
     * if the passed CompoundKey/id does not share the same mailAccountId/value
     * with the id of "this" MailAccount.
     * Returns a \Horde_Imap_Client_Socket if connecting was successfull.
     *
     * @param CompoundKey|string $key
     *
     * @return \Horde_Imap_Client_Socket
     *
     * @throws ImapClientException if the MailAccount used with this Client does not share
     * the same mailAccountId with the $key
     */
    public function connect($key) : \Horde_Imap_Client_Socket {

        if (!$this->socket) {
            $account = $this->getMailAccount($key);

            if (!$account) {
                throw new ImapClientException(
                    "The passed \"key\" does not share the same id-value with " .
                    "the MailAccount this class was configured with."
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
    public function getMailFolderList(MailAccount $mailAccount) :MailFolderList {

        try {
            $client = $this->connect($mailAccount->getId());

            $mailboxes = $client->listMailboxes(
                "*",
                \Horde_Imap_Client::MBOX_ALL,
                ["attributes" => true]
            );

            $mailFolderList = new MailFolderList;

            foreach ($mailboxes as $folderId => $mailbox) {

                $status = ["unseen" => 0];

                if ($this->isMailboxSelectable($mailbox)) {
                    $status = $client->status(
                        $folderId,
                        \Horde_Imap_Client::STATUS_UNSEEN
                    );
                }

                $folderKey = new FolderKey($mailAccount, $folderId);
                $mailFolderList[] = new ListMailFolder($folderKey, [
                    "name"        => $folderId,
                    "delimiter"   => $mailbox["delimiter"],
                    "unreadCount" => $status["unseen"],
                    "attributes"  => $mailbox["attributes"]
                ]);
            }

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $mailFolderList;
    }


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
    public function getMessageItem(MessageKey $key) :?MessageItem {

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

            $plainPart = new MessagePart($d["plain"]["content"], $d["plain"]["charset"], "text/plain");
            $body->setTextPlain($plainPart);


        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }


        return $body;
    }


    /**
     * @inheritdoc
     */
    public function getFileAttachmentList(MessageKey $key) :FileAttachmentList {

        try {
            $client = $this->connect($key);

            $messageItemId = $key->getId();
            $mailFolderId  = $key->getMailFolderId();

            $query = new \Horde_Imap_Client_Fetch_Query();
            $query->structure();

            $uid = new \Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();
            $typeMap          = $messageStructure->contentTypeMap();
            $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();

            foreach ($typeMap as $part => $type) {
                if ($messageStructure->getPart($part)->isAttachment()) {
                    $bodyQuery->bodyPart($part, array(
                        'peek'   => true
                    ));
                }
            }

            $messageData = $client->fetch(
                $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($messageItemId)]
            )->first();

            $attachmentList = new FileAttachmentList;

            if ($messageData) {

                $id = 0;
                foreach ($typeMap as $typePart => $type) {

                    $stream = $messageData->getBodyPart($typePart, true);

                    $part = $messageStructure->getPart($typePart);
                    $part->setContents($stream, array('usestream' => true));

                    if (!!$part->getName($typePart)) {
                        $filename = $part->getName($typePart);
                        $attachmentList[] = $this->buildAttachment(
                            $key, $part, $filename, (string)++$id
                        );
                    }

                }
            }

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $attachmentList;
    }


    /**
     * @inheritdoc
     */
    public function setFlags(MessageKey $key, Flaglist $flagList) : bool{
        try {
            $client = $this->connect($key);

            $messageItemId = $key->getId();
            $mailFolderId  = $key->getMailFolderId();

            $ids = new \Horde_Imap_Client_Ids([$messageItemId]);

            $options = [
                'ids' => $ids
            ];

            foreach ($flagList as $flag) {

                $key = $flag->getValue() ? "add" : "remove";

                if (!isset($options[$key])) {
                    $options[$key] = [];
                }

                $options[$key][] = $flag->getName();
            }

            $client->store($mailFolderId, $options);

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function createMessageBody(FolderKey $key, MessageBodyDraft $messageBodyDraft) :MessageKey {

        try {
            $client = $this->connect($key);

            $mail = new \Horde_Mime_Mail();
            $plain = $messageBodyDraft->getTextPlain();
            $html  = $messageBodyDraft->getTextHtml();

            $mail->addHeader("User-Agent", "php-conjoon", true);
            $mail->setBody($plain->getContents(), $plain->getCharset());
            $mail->setHtmlBody($html->getContents(), $html->getCharset(), false);

            $mail->send(new \Horde_Mail_Transport_Null, false, false);
            $rawMessage = $mail->getRaw(false);

            $ids = $client->append($key->getId(), [["data" =>$rawMessage]]);

            return new MessageKey($key->getMailAccountId(), $key->getId(), "" . $ids->ids[0]);
        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function updateMessageDraft(MessageKey $messageKey, MessageItemDraft $messageItemDraft) :?MessageItemDraft {

        try {
            $client = $this->connect($messageKey);

            $id = $messageKey->getId();

            $mailFolderId = $messageKey->getMailFolderId();

            $fetchQuery = new \Horde_Imap_Client_Fetch_Query();
            $fetchQuery->fullText(["peek" => true]);

            $rangeList = new \Horde_Imap_Client_Ids();
            $rangeList->add($id);

            $fetchResult = $client->fetch($messageKey->getMailFolderId(), $fetchQuery, ['ids' => $rangeList]);

            $msg = $fetchResult[$id]->getFullMsg(false);

            $part    = \Horde_Mime_Part::parseMessage($msg);
            $headers = \Horde_Mime_Headers::parseHeaders($msg);

            $mid = $messageItemDraft;
            // set headers
            $mid->getSubject() && $headers->addHeader("Subject", $mid->getSubject());
            $mid->getFrom() && $headers->addHeader("From", $mid->getFrom()->toString());
            $mid->getTo() && $headers->addHeader("To", $mid->getTo()->toString());
            $mid->getCc() && $headers->addHeader("Cc", $mid->getCc()->toString());
            $mid->getBcc() && $headers->addHeader("Bcc", $mid->getBcc()->toString());
            $mid->getDate() && $headers->addHeader("Date", $mid->getDate()->format("r"));

            $fullText = trim($headers->toString()) . "\n\n" . trim($part->toString());

            $ids    = $client->append($mailFolderId, [["data" => $fullText]]);
            $newKey = new MessageKey($messageKey->getMailAccountId(), $messageKey->getMailFolderId(), "" . $ids->ids[0]);

            $this->setFlags($newKey, $mid->getFlagList());

            $client->expunge($mailFolderId, ["delete" => true, "ids" => $rangeList]);

            $messageItemDraft->setMessageKey($newKey);

            return $messageItemDraft;

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return null;
    }

// -------------------
//   Helper
// -------------------

    /**
     * Builds an attachment from the specified data.
     *
     * @param MessageKey $key
     * @param $part
     * @param $fileName
     * @param $id
     *
     * @return FileAttachment
     */
    protected function buildAttachment(MessageKey $key, \Horde_Mime_Part $part, string $fileName, string $id) :FileAttachment {

        $mimeType = $part->getType();

        return new FileAttachment(new AttachmentKey($key, $id), [
            "type"     => $mimeType,
            "text"     => $fileName,
            "size"     => $part->getBytes(),
            "content"  => base64_encode($part->getContents()),
            "encoding" => "base64"
        ]);
    }



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
        $limit = isset($options["limit"]) ? intval($options["limit"]) : -1;

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

        $fetchQuery->headers("ContentType", ["Content-Type"], ["peek" => true]);

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
    protected function buildMessageItem(\Horde_Imap_Client_Socket $client, FolderKey $key, $item): MessageItem {

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
    protected function buildMessageItems(\Horde_Imap_Client_Socket $client, FolderKey $key, array $items
    ): MessageItemList {

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


        $charset = $this->getCharsetFromContentTypeHeaderValue($item->getHeaders("ContentType"));

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
            "charset"        => $charset,
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


        $ret             = [];
        $findHtml        = in_array("html", $options);
        $findPlain       = in_array("plain", $options);
        $findAttachments = in_array("hasAttachments", $options);

        $typeMap          = $messageStructure->contentTypeMap();
        $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();

        if ($findAttachments) {
            $ret["hasAttachments"] = false;
            foreach ($typeMap as $part => $type) {
                if (in_array($type, ["text/plain", "text/html"]) === false &&
                    $messageStructure->getPart($part)->isAttachment()) {
                    $ret["hasAttachments"] = true;
                }
            }
        }

        if (!$findHtml && !$findPlain) {
            return $ret;
        }

        foreach ($typeMap as $part => $type) {
            if (($type === "text/html" && $findHtml) ||
                ($type === "text/plain" && $findPlain)) {
                $bodyQuery->bodyPart($part, [
                    'peek' => true
                ]);
            }
        }

        $messageData = $client->fetch(
            $key->getMailFolderId(), $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($key->getId())]
        )->first();

        if ($findHtml) {
            $ret["html"] = $this->getTextContent('html', $messageStructure, $messageData, $typeMap);
        }

        if ($findPlain) {
            $ret["plain"] = $this->getTextContent('plain', $messageStructure, $messageData, $typeMap);
        }


        return $ret;
    }


    /**
     * Helper function for getting content of a message part.
     *
     * @param $type
     * @param $messageStructure
     * @param $messageData
     * @param $typeMap
     *
     * @return array
     */
    protected function getTextContent($type, $messageStructure, $messageData, $typeMap) {

        $partId = $messageStructure->findBody($type);

        foreach ($typeMap as $part => $type) {

            if ((string)$part === $partId) {

                $body    = $messageStructure->getPart($part);
                $content = $messageData->getBodyPart($part);

                if (!$messageData->getBodyPartDecode($part)) {
                    // Decode the content.
                    $body->setContents($content);
                    $content = $body->getContents();
                }

                return ["content" => $content, "charset" => $body->getCharset()];
            }

        }

        return ["content" => "", "charset" => ""];
    }


    /**
     * @param $value
     * @return string
     */
    protected function getCharsetFromContentTypeHeaderValue($value) {

        $value = "" . $value;

        $parts = explode(";", $value);
        foreach ($parts as $key => $part) {
            $part = trim($part);

            $subPart = explode("=", $part);

            if (strtolower(trim($subPart[0])) === "charset") {
                return strtolower(trim($subPart[1]));
            }
        }

        return "";
    }


    /**
     * Helper function for determining if a mailbox is selectable.
     * Will return false if querying the given mailbox would result
     * in an error (server side).
     *
     * @param array $mailbox
     * @return bool
     */
    protected function isMailboxSelectable(array $mailbox) {
       return !in_array("\\noselect", $mailbox["attributes"]) &&
              !in_array("\\nonexistent", $mailbox["attributes"]);
    }

}