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

namespace Conjoon\Mail\Client;

use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Folder\MailFolderList;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;

/**
 * Interface MailClient
 * @package Conjoon\Mail\Client
 */
interface MailClient
{


    /**
     * Returns a MailFolderList with ListMailFolders representing all
     * mailboxes available for the specified MailAccount.
     *
     * @param MailAccount $mailAccount
     *
     * @return MailFolderList
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMailFolderList(MailAccount $mailAccount): MailFolderList;


    /**
     * Returns the specified MessageItem for the submitted arguments.
     *
     * @param MessageKey $messageKey
     *
     * @return MessageItem|null The MessageItem or null if none found.
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageItem(MessageKey $messageKey): ?MessageItem;


    /**
     * Deletes the specified MessageItem permanently.
     *
     * @param MessageKey $messageKey
     *
     * @return bool true if deleting the message was successful, otherwise false.
     *
     * @throws MailClientException if any exception occurs
     */
    public function deleteMessage(MessageKey $messageKey): bool;


    /**
     * Returns the specified MessageItemDraft for the submitted arguments.
     *
     * @param MessageKey $messageKey
     *
     * @return MessageItemDraft|null The MessageItemDraft or null if none found.
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageItemDraft(MessageKey $messageKey): ?MessageItemDraft;


    /**
     * Tries to send the specified MessageItemDraft found under $key.
     *
     * @param MessageKey $messageKey
     * @return bool true if sending was successful, otherwise false.
     *
     * @throws MailClientException if any exception occurs, or if the message found
     * is not a Draft-Message.
     */
    public function sendMessageDraft(MessageKey $messageKey): bool;


    /**
     * Returns the specified MessageBody for the submitted arguments.
     *
     * @param MessageKey $messageKey
     *
     * @return MessageBody The MessageBody or null if none found.
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageBody(MessageKey $messageKey): ?MessageBody;


    /**
     * Appends a new Message to the specified Folder with the data found in MessageBodyDraft.
     * Will mark the newly created Message as a draft.
     *
     * @param FolderKey $folderKey
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return MessageBodyDraft the created MessageBodyDraft
     *
     * @throws MailClientException if any exception occurs, or of the MessageBodyDraft already has
     * a MessageKey
     */
    public function createMessageBodyDraft(FolderKey $folderKey, MessageBodyDraft $messageBodyDraft): MessageBodyDraft;


    /**
     * Updates the MessageBody of the specified message.
     *
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return MessageBodyDraft the created MessageBodyDraft
     *
     * @throws MailClientException if any exception occurs, or of the MessageBodyDraft does not have a
     * MessageKey
     */
    public function updateMessageBodyDraft(MessageBodyDraft $messageBodyDraft): MessageBodyDraft;


    /**
     * Updates envelope information of a Message, if the Message is a draft Message.
     *
     * @param MessageItemDraft $messageItemDraft
     *
     * @return MessageItemDraft The MessageItemDraft updated, along with its MessageKey, which
     * might not equal to the MessageKey in $messageItemDraft.
     *
     * @throws MailClientException if any exception occurs
     */
    public function updateMessageDraft(MessageItemDraft $messageItemDraft): ?MessageItemDraft;


    /**
     * Returns the specified MessageList for the submitted arguments.
     *
     * @param FolderKey $folderKey
     * @param array|null $options An additional set of options for querying the MessageList, such
     * as sort-direction, start/limit values and the ids of the messageItems to return.
     * Options may include an "attributes" configuration specifying the attributes of a message
     * that should be queried and returned, wheres the keys of this array are the attributes, and the
     * values are further configuration options this client may implement. Clients need to return a
     * set of attributes if no attributes are defined.
     *
     * @return MessageItemList
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageItemList(FolderKey $folderKey, array $options = []): MessageItemList;


    /**
     * Returns the total number of messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $folderKey
     *
     * @return int
     *
     * @throws MailClientException if any exception occurs
     */
    public function getTotalMessageCount(FolderKey $folderKey): int;


    /**
     * Returns the total number of UNREAD messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $folderKey
     *
     * @return int
     *
     * @throws MailClientException if any exception occurs
     */
    public function getUnreadMessageCount(FolderKey $folderKey): int;


    /**
     * Returns the FileAttachments in an FileAttachmentList for the specified message.
     *
     * @param MessageKey $messageKey
     *
     * @return FileAttachmentList
     *
     * @throws MailClientException if any exception occurs
     */
    public function getFileAttachmentList(MessageKey $messageKey): FileAttachmentList;


    /**
     * Sets the flags specified in FlagList for the message represented by MessageKey.
     * Existing flags will not be removed if they do not appear in the $flagList.
     *
     * @param MessageKey $messageKey
     * @param FlagList $flagList
     *
     * @return bool if the operation succeeded, otherwise false
     *
     * @throws MailClientException if any exception occurs
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList): bool;


    /**
     * Moves the message identified by $messageKey to the folder specified with $folderKey.
     * Does nothing if both mailFolderIds are the same.
     *
     * @param MessageKey $messageKey
     * @param FolderKey $folderKey
     *
     * @return MessageKey The new MessageKey for the moved Message
     *
     * @throws MailClientException if the MailAccount-id found in $messageKey and $folderKey are
     * not the same, or if any other error occurs
     */
    public function moveMessage(MessageKey $messageKey, FolderKey $folderKey): MessageKey;


    /**
     * Returns an array with the attribute-names the client supports.
     *
     * @return array
     */
    public function getSupportedAttributes(): array;


    /**
     * Returns an array with the attributes the client guarantees to query if no attributes
     * where specified for methods that require them.
     *
     * @return array
     */
    public function getDefaultAttributes(): array;
}
