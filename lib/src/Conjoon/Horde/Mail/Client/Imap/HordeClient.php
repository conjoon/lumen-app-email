<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\CompoundKey;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\Folder\ListMailFolder;
use Conjoon\Mail\Client\Folder\MailFolderList;
use Conjoon\Mail\Client\Imap\ImapClientException;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\Message\Composer\BodyComposer;
use Conjoon\Mail\Client\Message\Composer\HeaderComposer;
use Conjoon\Mail\Client\Message\Flag\AnsweredFlag;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use DateTime;
use Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use Horde_Mail_Transport;
use Horde_Mail_Transport_Smtphorde;
use Horde_Mime_Headers;
use Horde_Mime_Mail;
use Horde_Mime_Part;

/**
 * Class HordeClient.
 * Default implementation of a HordeClient, using \Horde_Imap_Client to communicate with
 * Imap Mail Servers.
 *
 * @package Conjoon\Mail\Client\Imap
 */
class HordeClient implements MailClient
{

    /**
     * @var MailAccount
     */
    protected MailAccount $mailAccount;

    /**
     * @var Horde_Imap_Client_Socket|null
     */
    protected ?Horde_Imap_Client_Socket $socket = null;

    /**
     * @var Horde_Mail_Transport|null
     */
    protected ?Horde_Mail_Transport $mailer = null;

    /**
     * @var BodyComposer
     */
    protected BodyComposer $bodyComposer;

    /**
     * @var HeaderComposer
     */
    protected HeaderComposer $headerComposer;


    /**
     * HordeClient constructor.
     *
     * @param MailAccount $account
     * @param BodyComposer $bodyComposer
     * @param HeaderComposer $headerComposer
     */
    public function __construct(
        MailAccount $account,
        BodyComposer $bodyComposer,
        HeaderComposer $headerComposer
    ) {
        $this->mailAccount = $account;
        $this->bodyComposer = $bodyComposer;
        $this->headerComposer = $headerComposer;
    }


    /**
     * Returns the BodyComposer this instance was configured with.
     *
     * @return BodyComposer
     */
    public function getBodyComposer(): BodyComposer
    {
        return $this->bodyComposer;
    }


    /**
     * Returns the HeaderComposer this instance was configured with.
     *
     * @return HeaderComposer
     */
    public function getHeaderComposer(): HeaderComposer
    {
        return $this->headerComposer;
    }


    /**
     * Returns the MailAccount providing connection info for the CompoundKey
     * or string (which will be treated as the id of the MailAccount to look up).
     *
     * @param CompoundKey|string $key
     *
     * @return MailAccount|null
     */
    public function getMailAccount($key): ?MailAccount
    {

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
     * Returns a \Horde_Imap_Client_Socket if connecting was successful.
     *
     * @param CompoundKey|string $key
     *
     * @return Horde_Imap_Client_Socket
     *
     * @throws ImapClientException if the MailAccount used with this Client does not share
     * the same mailAccountId with the $key
     */
    public function connect($key): Horde_Imap_Client_Socket
    {

        if (!$this->socket) {
            $account = $this->getMailAccount($key);

            if (!$account) {
                throw new ImapClientException(
                    "The passed \"key\" does not share the same id-value with " .
                    "the MailAccount this class was configured with."
                );
            }

            $this->socket = new Horde_Imap_Client_Socket(array(
                'username' => $account->getInboxUser(),
                'password' => $account->getInboxPassword(),
                'hostspec' => $account->getInboxAddress(),
                'port' => $account->getInboxPort(),
                'secure' => $account->getInboxSsl() ? 'ssl' : null
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
    public function getMailFolderList(MailAccount $mailAccount): MailFolderList
    {

        try {
            $client = $this->connect($mailAccount->getId());

            $mailboxes = $client->listMailboxes(
                "*",
                Horde_Imap_Client::MBOX_ALL,
                ["attributes" => true]
            );

            $mailFolderList = new MailFolderList();

            foreach ($mailboxes as $folderId => $mailbox) {
                $status = ["unseen" => 0];

                if ($this->isMailboxSelectable($mailbox)) {
                    $status = $client->status(
                        $folderId,
                        Horde_Imap_Client::STATUS_UNSEEN
                    );
                }

                $folderKey = new FolderKey($mailAccount, $folderId);
                $mailFolderList[] = new ListMailFolder($folderKey, [
                    "name" => $folderId,
                    "delimiter" => $mailbox["delimiter"],
                    "unreadCount" => $status["unseen"],
                    "attributes" => $mailbox["attributes"]
                ]);
            }
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $mailFolderList;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemList(FolderKey $folderKey, MessageItemListResourceQuery $query): MessageItemList
    {
        $options = $query->toJson();

        try {
            $client = $this->connect($folderKey);

            $results = $this->queryItems($client, $folderKey, $options);
            $fetchedItems = $this->fetchMessageItems($client, $results["match"], $folderKey->getId(), $options);

            $options["attributes"] = $options["attributes"] ?? $this->getDefAttr();

            return $this->buildMessageItems(
                $client,
                $folderKey,
                $fetchedItems,
                $options
            );
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getTotalMessageCount(FolderKey $folderKey): int
    {

        try {
            $client = $this->connect($folderKey);
            $results = $this->queryItems($client, $folderKey);

            return count($results["match"]);
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getUnreadMessageCount(FolderKey $folderKey): int
    {

        try {
            $client = $this->connect($folderKey);
            $status = $client->status($folderKey->getId(), Horde_Imap_Client::STATUS_UNSEEN);

            return $status["unseen"];
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MessageKey $messageKey): ?MessageItem
    {

        try {
            $client = $this->connect($messageKey);
            $mailFolderId = $messageKey->getMailFolderId();
            $fetchedItems = $this->fetchMessageItems(
                $client,
                new Horde_Imap_Client_Ids($messageKey->getId()),
                $mailFolderId,
                []
            );

             $ret = $this->buildMessageItem(
                 $client,
                 new FolderKey($messageKey->getMailAccountId(), $mailFolderId),
                 $fetchedItems[0],
                 ["attributes" => $this->getDefAttr(["hasAttachments" => true, "size" => true])]
             );

             return new MessageItem($ret["messageKey"], array_filter($ret["data"], fn ($item) => $item !== null));
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function deleteMessage(MessageKey $messageKey): bool
    {

        try {
            $mailFolderId = $messageKey->getMailFolderId();
            $id = $messageKey->getId();

            $client = $this->connect($messageKey);

            $rangeList = new Horde_Imap_Client_Ids();
            $rangeList->add($id);

            $idList = $client->expunge($mailFolderId, ["delete" => true, "ids" => $rangeList, "list" => true]);

            if (count($idList) === 0) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemDraft(MessageKey $messageKey): ?MessageItemDraft
    {

        try {
            $client = $this->connect($messageKey);
            $mailFolderId = $messageKey->getMailFolderId();
            $fetchedItems = $this->fetchMessageItems(
                $client,
                new Horde_Imap_Client_Ids($messageKey->getId()),
                $mailFolderId,
                []
            );
            $ret = $this->buildMessageItem(
                $client,
                new FolderKey($messageKey->getMailAccountId(), $mailFolderId),
                $fetchedItems[0],
                ["attributes" => $this->getDefAttr(["cc" => true, "bcc" => true, "replyTo" => true])]
            );

            return new MessageItemDraft(
                $ret["messageKey"],
                array_filter($ret["data"], fn ($item) => $item !== null)
            );
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getMessageBody(MessageKey $messageKey): MessageBody
    {

        $mailFolderId = $messageKey->getMailFolderId();
        $messageItemId = $messageKey->getId();

        try {
            $client = $this->connect($messageKey);

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->structure();


            $uid = new Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();

            $d = $this->getContents($client, $messageStructure, $messageKey, [
                "attributes" => ["plain" => true, "html" => true]
            ]);

            $body = new MessageBody($messageKey);

            if ($d["html"]["content"]) {
                $htmlPart = new MessagePart($d["html"]["content"], $d["html"]["charset"], "text/html");
                $body->setTextHtml($htmlPart);
            }

            $plainPart = new MessagePart($d["plain"]["content"], $d["plain"]["charset"], "text/plain");
            $body->setTextPlain($plainPart);
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }


        return $body;
    }


    /**
     * @inheritdoc
     */
    public function getFileAttachmentList(MessageKey $messageKey): FileAttachmentList
    {

        try {
            $client = $this->connect($messageKey);

            $messageItemId = $messageKey->getId();
            $mailFolderId = $messageKey->getMailFolderId();

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->structure();

            $uid = new Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();
            $bodyQuery = new Horde_Imap_Client_Fetch_Query();

            foreach ($messageStructure as $typePart => $part) {
                if ($part->isAttachment()) {
                    $bodyQuery->bodyPart($typePart, array(
                        'peek' => true
                    ));
                }
            }

            $messageData = $client->fetch(
                $mailFolderId,
                $bodyQuery,
                ['ids' => new Horde_Imap_Client_Ids($messageItemId)]
            )->first();

            $attachmentList = new FileAttachmentList();

            if ($messageData) {
                $id = 0;
                foreach ($messageStructure as $typePart => $part) {
                    $stream = $messageData->getBodyPart($typePart, true);

                    $part->setContents($stream, array('usestream' => true));

                    if (!!$part->getName($typePart)) {
                        $filename = $part->getName($typePart);
                        $attachmentList[] = $this->buildAttachment(
                            $messageKey,
                            $part,
                            $filename,
                            (string)++$id
                        );
                    }
                }
            }
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $attachmentList;
    }


    /**
     * @inheritdoc
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList): bool
    {
        try {
            $client = $this->connect($messageKey);

            $messageItemId = $messageKey->getId();
            $mailFolderId = $messageKey->getMailFolderId();

            $ids = new Horde_Imap_Client_Ids([$messageItemId]);

            $options = [
                'ids' => $ids
            ];

            foreach ($flagList as $flag) {
                $messageKey = $flag->getValue() ? "add" : "remove";

                if (!isset($options[$messageKey])) {
                    $options[$messageKey] = [];
                }

                $options[$messageKey][] = $flag->getName();
            }

            $client->store($mailFolderId, $options);
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function createMessageBodyDraft(FolderKey $folderKey, MessageBodyDraft $messageBodyDraft): MessageBodyDraft
    {

        if ($messageBodyDraft->getMessageKey()) {
            throw new ImapClientException(
                "Cannot create a MessageBodyDraft that already has a MessageKey"
            );
        }

        try {
            $mailAccountId = $folderKey->getMailAccountId();
            $mailFolderId = $folderKey->getId();

            $client = $this->connect($folderKey);

            return $this->appendAsDraft(
                $client,
                $mailAccountId,
                $mailFolderId,
                "",
                $messageBodyDraft
            );
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function updateMessageBodyDraft(MessageBodyDraft $messageBodyDraft): MessageBodyDraft
    {

        $key = $messageBodyDraft->getMessageKey();

        if (!$key) {
            throw new ImapClientException(
                "Cannot update a MessageBodyDraft that doesn't have a MessageKey"
            );
        }

        try {
            $mailFolderId = $key->getMailFolderId();
            $mailAccountId = $key->getMailAccountId();
            $id = $key->getId();

            $client = $this->connect($key);

            $rangeList = new Horde_Imap_Client_Ids();
            $rangeList->add($id);

            $fetchQuery = new Horde_Imap_Client_Fetch_Query();
            $fetchQuery->fullText(["peek" => true]);
            $fetchResult = $client->fetch($mailFolderId, $fetchQuery, ['ids' => $rangeList]);
            $target = $fetchResult[$id]->getFullMsg(false);

            $newDraft = $this->appendAsDraft(
                $client,
                $mailAccountId,
                $mailFolderId,
                $target,
                $messageBodyDraft
            );

            // delete the previous draft
            $this->deleteMessage($key);

            return $newDraft;
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function updateMessageDraft(MessageItemDraft $messageItemDraft): ?MessageItemDraft
    {

        try {
            $messageKey = $messageItemDraft->getMessageKey();

            $client = $this->connect($messageKey);

            $id = $messageKey->getId();

            $mailFolderId = $messageKey->getMailFolderId();

            $fetchQuery = new Horde_Imap_Client_Fetch_Query();
            $fetchQuery->fullText(["peek" => true]);

            $rangeList = new Horde_Imap_Client_Ids();
            $rangeList->add($id);

            $fetchResult = $client->fetch($messageKey->getMailFolderId(), $fetchQuery, ['ids' => $rangeList]);
            $msg = $fetchResult[$id]->getFullMsg(false);

            $fullText = $this->getHeaderComposer()->compose($msg, $messageItemDraft);

            $ids = $client->append($mailFolderId, [["data" => $fullText]]);
            $newKey = new MessageKey(
                $messageKey->getMailAccountId(),
                $messageKey->getMailFolderId(),
                (string)$ids->ids[0]
            );

            $this->setFlags($newKey, $messageItemDraft->getFlagList());

            $this->deleteMessage($messageKey);

            return $messageItemDraft->setMessageKey($newKey);
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function sendMessageDraft(MessageKey $messageKey): bool
    {
        try {
            $client = $this->connect($messageKey);
            $account = $this->getMailAccount($messageKey);

            $mailFolderId = $messageKey->getMailFolderId();
            $id = $messageKey->getId();

            $rangeList = new Horde_Imap_Client_Ids();
            $rangeList->add($id);

            $fetchQuery = new Horde_Imap_Client_Fetch_Query();
            $fetchQuery->fullText(["peek" => true]);
            $fetchQuery->flags();
            $fetchResult = $client->fetch($mailFolderId, $fetchQuery, ['ids' => $rangeList]);
            $item = $fetchResult[$id];

            // check if message is a draft
            $flags = $item->getFlags();
            if (!in_array(Horde_Imap_Client::FLAG_DRAFT, $flags)) {
                throw new ImapClientException("The specified message is not a Draft-Message.");
            }

            $target = $item->getFullMsg(false);

            $part = Horde_Mime_Part::parseMessage($target);
            $headers = Horde_Mime_Headers::parseHeaders($target);

            // Check for X-CN-DRAFT-INFO...
            $xCnDraftInfo = $headers->getHeader("X-CN-DRAFT-INFO");
            $xCnDraftInfo = $xCnDraftInfo ? $xCnDraftInfo->value_single : null;
            // ...delete the header...
            $headers->removeHeader("X-CN-DRAFT-INFO");


            $mail = new Horde_Mime_Mail($headers);
            $mail->setBasePart($part);

            $mailer = $this->getMailer($account);
            $mail->send($mailer);

            // ...and set \Answered flag.
            if ($xCnDraftInfo) {
                $this->setAnsweredForDraftInfo($xCnDraftInfo, $account->getId());
            }

            return true;
        } catch (Exception $e) {
            if ($e instanceof ImapClientException) {
                throw $e;
            }
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function moveMessage(MessageKey $messageKey, FolderKey $folderKey): MessageKey
    {

        if ($messageKey->getMailAccountId() !== $folderKey->getMailAccountId()) {
            throw new ImapClientException(
                "The \"messageKey\" and the \"folderKey\" do not share the same mailAccountId."
            );
        }

        if ($messageKey->getMailFolderId() === $folderKey->getId()) {
            return $messageKey;
        }


        try {
            $client = $this->connect($messageKey);

            $sourceFolder = $messageKey->getMailFolderId();
            $destFolder = $folderKey->getId();

            $rangeList = new Horde_Imap_Client_Ids();
            $rangeList->add($messageKey->getId());

            $res = $client->copy(
                $sourceFolder,
                $destFolder,
                ["ids" => $rangeList, "move" => true, "force_map" => true]
            );

            if (!is_array($res)) {
                throw new ImapClientException("Moving the message was not successful.");
            }

            return new MessageKey(
                $folderKey->getMailAccountId(),
                $folderKey->getId(),
                (string)$res[$messageKey->getId()]
            );
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }

// -------------------
//   Helper
// -------------------

    /**
     * Sets the flag \Answered for the message specified in $xCnDraftInfo.
     * The string is expected to be a base64-encoded string representing
     * a JSON-encoded array with three indices: mailAccountId, mailFolderId
     * and id.
     * Will do nothing if the mailAccountId in $xCnDraftInfo does not match
     * the $forAccountId passed to this method, or if decoding the $xCnDraftInfo
     * failed.
     *
     * @param string $xCnDraftInfo
     * @param string $forAccountId
     */
    protected function setAnsweredForDraftInfo(string $xCnDraftInfo, string $forAccountId)
    {

        $baseDecode = base64_decode($xCnDraftInfo);

        if ($baseDecode === false) {
            return;
        }

        $info = json_decode($baseDecode, true);

        if (!is_array($info) || count($info) !== 3) {
            return;
        }

        if ($info[0] !== $forAccountId) {
            return;
        }

        $messageKey = new MessageKey($forAccountId, $info[1], (string)$info[2]);

        $flagList = new FlagList();
        $flagList[] = new AnsweredFlag(true);

        $this->setFlags($messageKey, $flagList);
    }


    /**
     * Returns the Horde_Mail_Transport to be used with this account.
     *
     *
     * @param MailAccount $account
     *
     * @return Horde_Mail_Transport
     */
    public function getMailer(MailAccount $account)
    {

        $account = $this->getMailAccount($account->getId());

        if (!$account) {
            throw new ImapClientException(
                "The passed \"account\" does not share the same id-value with " .
                "the MailAccount this class was configured with."
            );
        }

        if ($this->mailer) {
            return $this->mailer;
        }

        $smtpCfg = [
            "host" => $account->getOutboxAddress(),
            "port" => $account->getOutboxPort(),
            "password" => $account->getOutboxPassword(),
            "username" => $account->getOutboxUser()
        ];

        if ($account->getOutboxSsl()) {
            $smtpCfg["secure"] = 'ssl';
        }

        $this->mailer = new Horde_Mail_Transport_Smtphorde($smtpCfg);

        return $this->mailer;
    }


    /**
     * Appends the specified $rawMessage to $mailFolderId and returns a new MessageBodyDraft with the
     * created MessageKey.
     *
     * @param Horde_Imap_Client_Socket $client
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $target
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return MessageBodyDraft
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function appendAsDraft(
        Horde_Imap_Client_Socket $client,
        string $mailAccountId,
        string $mailFolderId,
        string $target,
        MessageBodyDraft $messageBodyDraft
    ): MessageBodyDraft {

        $rawMessage = $this->getBodyComposer()->compose($target, $messageBodyDraft);
        $rawMessage = $this->getHeaderComposer()->compose($rawMessage);

        $ids = $client->append($mailFolderId, [["data" => $rawMessage]]);
        $messageKey = new MessageKey($mailAccountId, $mailFolderId, (string)$ids->ids[0]);

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(true);
        $this->setFlags($messageKey, $flagList);

        return $messageBodyDraft->setMessageKey($messageKey);
    }


    /**
     * Builds an attachment from the specified data.
     *
     * @param MessageKey $key
     * @param Horde_Mime_Part $part
     * @param string $fileName
     * @param string $id
     *
     * @return FileAttachment
     */
    protected function buildAttachment(
        MessageKey $key,
        Horde_Mime_Part $part,
        string $fileName,
        string $id
    ): FileAttachment {

        $mimeType = $part->getType();

        return new FileAttachment(new AttachmentKey($key, $id), [
            "type" => $mimeType,
            "text" => $fileName,
            "size" => $part->getBytes(),
            "content" => base64_encode($part->getContents()),
            "encoding" => "base64"
        ]);
    }


    /**
     * Fetches a list of messages from the server, considering start & limit options passed
     * with $options.
     * The property "ids" which may or may not be available in $options should have already been
     * considered and should be found in $searchResultIds.
     *
     * @param Horde_Imap_Client_Socket $client
     * @param Horde_Imap_Client_Ids $searchResultIds
     * @param string $mailFolderId
     * @param array $options
     *
     * @return array
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function fetchMessageItems(
        Horde_Imap_Client_Socket $client,
        Horde_Imap_Client_Ids $searchResultIds,
        string $mailFolderId,
        array $options
    ): array {

        $start = isset($options["start"]) ? intval($options["start"]) : -1;
        $limit = isset($options["limit"]) ? intval($options["limit"]) : -1;

        if ($start >= 0 && $limit > 0) {
            $rangeList = new Horde_Imap_Client_Ids();
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
        $fetchQuery = new Horde_Imap_Client_Fetch_Query();
        $fetchQuery->flags();
        $fetchQuery->size();
        $fetchQuery->envelope();
        $fetchQuery->structure();

        $fetchQuery->headers("ContentType", ["Content-Type"], ["peek" => true]);
        $fetchQuery->headers("References", ["References"], ["peek" => true]);

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
     * @param Horde_Imap_Client_Socket $client
     * @param FolderKey $key
     * @param $item
     * @param array $options An array with a key "attributes" that specifies the attributes to query.
     * The keys are the attributes that should be considered, the values are configuration options for the queries
     * of the fields.
     *
     * @example
     *    $this->buildMessageItem($client, $key, $item, ["attributes" => ["size" => [], "hasAttachments" => true]]);
     *
     *
     * @return array an array indexed with "messageKey" and "data" which should both be used to create
     * concrete instances of MessageItem/MessageItemDraft
     *
     * @throws Horde_Imap_Client_Exception
     * @see queryItems
     */
    protected function buildMessageItem(
        Horde_Imap_Client_Socket $client,
        FolderKey $key,
        $item,
        array $options = []
    ): array {

        $result = $this->getItemStructure($client, $item, $key, $options);

        return [
            "messageKey" => $result["messageKey"],
            "data" => $result["data"]
        ];
    }


    /**
     * Transform the passed list of $items to an instance of MessageItemList.
     * If both html/plain attributes where requested, it will always try to use
     * text/plain for the MessagePart, then fall back to text/html if required.
     *
     * @param Horde_Imap_Client_Socket $client
     * @param FolderKey $key
     * @param array $items
     * @param array $options
     *
     * @return MessageItemList
     *
     * @throws Horde_Imap_Client_Exception
     * @see queryItems
     */
    protected function buildMessageItems(
        Horde_Imap_Client_Socket $client,
        FolderKey $key,
        array $items,
        array $options
    ): MessageItemList {

        $messageItems = new MessageItemList();

        $attributes = $options["attributes"] ?? [];

        foreach ($items as $item) {
            $result = $this->getItemStructure($client, $item, $key, $options);
            $data = $result["data"];

            $contentData = $result["contentData"];

            $messageItem = null;
            $messagePart = null;

            $contentKeys = [];
            $this->getAttr("plain", $attributes) && $contentKeys[] = "plain";
            $this->getAttr("html", $attributes) && $contentKeys[] = "html";


            // plain first
            foreach ($contentKeys as $contentKey) {
                $content = $contentData[$contentKey] ?? null;
                if (!$content || !trim($content["content"])) {
                    continue;
                }
                $messagePart = new MessagePart(
                    $content['content'],
                    $content['charset'],
                    $contentKey === "plain" ? "text/plain" : "text/html"
                );
                // exit here if we have processed plain, as we rely on this attribute for
                // ListMessageItems
                if ($contentKey === "plain") {
                    break;
                }
            }

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
     * @param Horde_Imap_Client_Socket $client
     * @param $item
     * @param FolderKey $key
     * @param array $options An array providing "attributes" which is an associative array where the keys
     * are the attributes that should be queried, and their values additional configuration options for the
     * query itself.
     *
     * @example
     *  $this->getItemStructure(
     *     $client,
     *     $item,
     *     $key,
     *     ["attributes" => [
     *          "from" => [], "to" => true, "plain" => ["length" => 200]
     *      ]]); // returns "from", "to" and "plain" with a length of 100 characters
     *
     *
     * @return array data with the item structure, options holding additional requested data (passed via $options)
     * and messageKey holding the generated MessageKey for the item.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function getItemStructure(
        Horde_Imap_Client_Socket $client,
        $item,
        FolderKey $key,
        array $options = []
    ): array {

        $wants = function ($key) use ($options) {
            return $this->getAttr($key, $options["attributes"] ?? []);
        };

        $envelope = $item->getEnvelope();
        $flags    = $item->getFlags();

        $messageKey = new MessageKey($key->getMailAccountId(), $key->getId(), (string)$item->getUid());

        $data = [];

        $wants("from")    && $data["from"]    = $this->getAddress($envelope, "from");
        $wants("to")      && $data["to"]      = $this->getAddress($envelope, "to");
        $wants("cc")      && $data["cc"]      = $this->getAddress($envelope, "cc");
        $wants("bcc")     && $data["bcc"]     = $this->getAddress($envelope, "bcc");
        $wants("replyTo") && $data["replyTo"] = $this->getAddress($envelope, "replyTo");

        $wants("seen")     && $data["seen"]     = in_array(Horde_Imap_Client::FLAG_SEEN, $flags);
        $wants("answered") && $data["answered"] = in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags);
        $wants("draft")    && $data["draft"]    = in_array(Horde_Imap_Client::FLAG_DRAFT, $flags);
        $wants("flagged")  && $data["flagged"]  = in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags);
        $wants("recent")   && $data["recent"]   = in_array(Horde_Imap_Client::FLAG_RECENT, $flags);


        $wants("subject") && $data["subject"] = $envelope->subject;
        $wants("date")    && $data["date"]    = $envelope->date ?? new DateTime("1970-01-01 +0000");

        $wants("messageId") && $data["messageId"] = $envelope->message_id;
        $wants("size")      && $data["size"]      = $item->getSize();

        ($wants("charset") || $wants("subject")) &&
        $data["charset"] = $this->getCharsetFromContentTypeHeaderValue(
            $item->getHeaders("ContentType")
        );
        $wants("references") && $data["references"] = $this->getMessageIdStringFromReferencesHeaderValue(
            $item->getHeaders("References")
        );


        $contentData = [];
        if ($wants("hasAttachments") || $wants("plain") || $wants("html")) {
            $messageStructure = $item->getStructure();
            $contentData      = $this->getContents($client, $messageStructure, $messageKey, $options);

            if ($wants("hasAttachments") && array_key_exists("hasAttachments", $contentData)) {
                $data["hasAttachments"] = $contentData["hasAttachments"];
            }
        }

        return [
            "data"        => $data,
            "contentData" => $contentData,
            "messageKey"  => $messageKey
        ];
    }


    /**
     * Sends a query against the currently connected IMAP server for retrieving
     * a list of messages based on the specified $options.
     *
     * @param Horde_Imap_Client_Socket $client
     * @param FolderKey $key The key of the folder to query
     * @param array|null $options An array with the following options
     * - start (integer) The start position from where to return the results
     * - limit (integer) The number of items to return.
     * - ids (array) an array of message ids to consider and exclusively include in the return array
     * - attributes (array) an assoc array of attributes that should be queried. Ignored by this method.
     *
     * @example
     *      $this->queryItems($client, $key, [
     *          "sort" => [["property" => "date", "direction" => "DESC"]]
     *      ]);
     *
     *
     * @return array
     * @throws Horde_Imap_Client_Exception
     */
    protected function queryItems(Horde_Imap_Client_Socket $client, FolderKey $key, array $options = null): array
    {
        $searchOptions = [];
        if ($options !== null) {
            $sort = $options["sort"] ?? [["property" => "date", "direction" => "DESC"]];
            $sort = $sort[0];

            $sortInfo = [];

            if ($sort["direction"] === "DESC") {
                $sortInfo[] = Horde_Imap_Client::SORT_REVERSE;
            }

            switch ($sort["property"]) {
                case 'subject':
                    $sortInfo[] = Horde_Imap_Client::SORT_SUBJECT;
                    break;
                case 'to':
                    $sortInfo[] = Horde_Imap_Client::SORT_TO;
                    break;
                case 'from':
                    $sortInfo[] = Horde_Imap_Client::SORT_FROM;
                    break;
                case 'date':
                    $sortInfo[] = Horde_Imap_Client::SORT_DATE;
                    break;
                case 'size':
                    $sortInfo[] = Horde_Imap_Client::SORT_SIZE;
                    break;
            }

            $searchOptions = ["sort" => $sortInfo];
        }


        $searchQuery = new Horde_Imap_Client_Search_Query();
        if (isset($options["ids"]) && is_array($options["ids"])) {
            $searchQuery->ids(new Horde_Imap_Client_Ids($options["ids"]));
        }

        // search and narrow down list
        return $client->search($key->getId(), $searchQuery, $searchOptions);
    }


    /**
     * Returns contents of the mail. Possible return keys are based on the passed
     * $options "attributes": "html" (string), "plain" (string) and/or "hasAttachments" (bool)
     *
     * @param Horde_Imap_Client_Socket $client
     * @param $messageStructure
     * @param MessageKey $key
     * @param array $options = array (
     *      "attributes" => [] // an array of attributes this method should consider. Possible
     *                         // keys are html, plain, hasAttachments. The values are configuration
     *                        // objects this method should considered.
     *                        // Both "html" and "plain" allow for specifying a "length"-option
     *                        // that tells this method to only return the specified number
     *                        // of characters for this message part.
     * );
     *
     * @example
     *   $this->getContents($client, $messageStructure, $key, [
     *      "attributes" => ["html" => [], "plain" => [$length => 200]]
     *   ]); // returns full html, but only first 200 characters of plain.
     *
     *
     * @return array
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function getContents(
        Horde_Imap_Client_Socket $client,
        $messageStructure,
        MessageKey $key,
        array $options
    ): array {

        $attributes = $options["attributes"] ?? [];

        $ret = [];
        $findHtml        = $this->getAttr("html", $attributes);
        $findPlain       = $this->getAttr("plain", $attributes);
        $findAttachments = $this->getAttr("hasAttachments", $attributes);

        $typeMap = $messageStructure->contentTypeMap();
        $bodyQuery = new Horde_Imap_Client_Fetch_Query();

        if ($findAttachments) {
            $ret["hasAttachments"] = false;
            foreach ($typeMap as $part => $type) {
                if (
                    in_array($type, ["text/plain", "text/html"]) === false &&
                    $messageStructure->getPart($part)->isAttachment()
                ) {
                    $ret["hasAttachments"] = true;
                }
            }
        }

        if (!$findHtml && !$findPlain) {
            return $ret;
        }

        foreach ($typeMap as $part => $type) {
            $length = null;
            if (
                ($type === "text/html" && $findHtml) ||
                ($type === "text/plain" && $findPlain)
            ) {
                $conf = ["peek" => true];

                $type === "text/html" && isset($findHtml["length"]) && ($length = $findHtml["length"]);
                $type === "text/plain" && isset($findPlain["length"]) && ($length = $findPlain["length"]);

                if ($length) {
                    $conf["length"] = $length;
                }

                $bodyQuery->bodyPart($part, $conf);
            }
        }

        $messageData = $client->fetch(
            $key->getMailFolderId(),
            $bodyQuery,
            ['ids' => new Horde_Imap_Client_Ids($key->getId())]
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
    protected function getTextContent($type, $messageStructure, $messageData, $typeMap): array
    {

        $partId = $messageStructure->findBody($type);

        foreach ($typeMap as $part => $type) {
            if ((string)$part === $partId) {
                $body = $messageStructure->getPart($part);
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
     * @param string $value
     *
     * @return string
     */
    protected function getMessageIdStringFromReferencesHeaderValue(string $value): string
    {

        if (strpos($value, "References:") !== 0) {
            return "";
        }

        return trim(substr($value, 11));
    }

    /**
     * @param $value
     * @return string
     */
    protected function getCharsetFromContentTypeHeaderValue($value): string
    {
        $parts = explode(";", $value);
        foreach ($parts as $part) {
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
    protected function isMailboxSelectable(array $mailbox): bool
    {
        return !in_array("\\noselect", $mailbox["attributes"]) &&
            !in_array("\\nonexistent", $mailbox["attributes"]);
    }


    /**
     * Helper function to return the MailAddress or MailAddressList based on the
     * specified argument from the $envelope.
     *
     * @param $envelope
     * @param string $type to, from, replyTo, cc or bcc are valid $types
     *
     * @return MailAddress|MailAddressList|null
     */
    protected function getAddress($envelope, string $type)
    {

        $type = $type === "replyTo" ? "reply-to" : $type;

        switch ($type) {
            case "from":
            case "reply-to":
                $mailAddress = null;
                if (!$envelope->{$type}) {
                    return null;
                }

                foreach ($envelope->{$type} as $add) {
                    if ($add->bare_address) {
                        $mailAddress = new MailAddress(
                            $add->bare_address,
                            $add->personal ?: $add->bare_address
                        );
                    }
                }
                return $mailAddress;

            default:
                $mailAddressList = new MailAddressList();
                if (!$envelope->{$type}) {
                    return $mailAddressList;
                }
                foreach ($envelope->{$type} as $add) {
                    if ($add->bare_address) {
                        $mailAddressList[] = new MailAddress($add->bare_address, $add->personal ?: $add->bare_address);
                    }
                }
                return $mailAddressList;
        }
    }

    /**
     * Returns a list of default attributes to return with a MessageItem.
     *
     * @return string[]
     */
    protected function getDefAttr($additional = [], $exclude = []): array
    {

        $def = $this->getDefaultAttributes();
        $ret = [];
        foreach ($def as $attr) {
            if (in_array($attr, $exclude)) {
                continue;
            }
            $ret[$attr] = true; // true or array
        }

        foreach ($additional as $key => $fieldConf) {
            $chk = $this->getAttr($key, $additional);
            $chk && $ret[$key] = $chk;// true or array
        }

        return $ret;
    }


    /**
     * @return string[]
     */
    public function getSupportedAttributes(): array
    {
        return [
            "hasAttachments",
            "size",
            "plain", // \ __Preview
            "html",  // /   Text
            "cc",
            "bcc",
            "replyTo",
            "from",
            "to",
            "subject",
            "date",
            "seen",
            "answered",
            "draft",
            "flagged",
            "recent",
            "charset",
            "references",
            "messageId"
        ];
    }


    /**
     * @inheritdoc
     */
    public function getDefaultAttributes(): array
    {
        return [
            "from",
            "to",
            "subject",
            "date",
            "seen",
            "answered",
            "draft",
            "flagged",
            "recent",
            "charset",
            "references",
            "messageId",
            "plain"
        ];
    }


    /**
     * Returns the target's value at "$key" if the value is truthy, otherwise
     * null or $default (if !null) is returned.
     *
     * @param $key
     * @param $target
     * @param null $default
     * @return mixed|null
     * @noinspection PhpSameParameterValueInspection
     */
    private function getAttr($key, $target, $default = null)
    {
        $valid = array_key_exists($key, $target) && ($target[$key] === true || is_array($target[$key]));

        if ($valid && empty($target[$key])) {
            $target[$key] = true;
        }

        return $valid ? $target[$key] : $default;
    }
}
