<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Mail\Client\Service;

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\PreviewTextProcessor;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor;

/**
 * Interface MessageItemService
 *
 * Provides a contract for MessageItem(List)/MessageBody related operations.
 *
 * @package Conjoon\Mail\Client\Service
 */
interface MessageItemService
{


    /**
     * Returns a MessageItemList containing the MessageItems for the
     * specified MailAccount and the MailFolder.
     *
     * @param FolderKey $folderKey
     * @param MessageItemListResourceQuery $query The resource query for the
     * MessageItemLi
     *
     * @return MessageItemList
     */
    public function getMessageItemList(FolderKey $folderKey, MessageItemListResourceQuery $query): MessageItemList;


    /**
     * Returns a single MessageItem.
     *
     * @param MessageKey $messageKey
     * @return MessageItem
     */
    public function getMessageItem(MessageKey $messageKey): MessageItem;


    /**
     * Deletes a single Message permanently.
     *
     * @param MessageKey $messageKey
     * @return bool true if successful, otherwise false
     */
    public function deleteMessage(MessageKey $messageKey): bool;


    /**
     * Returns a single ListMessageItem.
     *
     * @param MessageKey $messageKey
     * @return ListMessageItem
     */
    public function getListMessageItem(MessageKey $messageKey): ListMessageItem;


    /**
     * Returns a single MessageItemDraft.
     *
     * @param MessageKey $messageKey
     * @return MessageItemDraft or null if no entity for the key was found
     */
    public function getMessageItemDraft(MessageKey $messageKey): ?MessageItemDraft;


    /**
     * Returns a single MessageBody.
     *
     * @param MessageKey $messageKey
     * @return MessageBody
     */
    public function getMessageBody(MessageKey $messageKey): MessageBody;


    /**
     * Creates a single MessageBodyDraft and returns it along with the generated MessageKey.
     * Returns null if the MessageBodyDraft could not be created.
     * The created message will be marked as a draft.
     *
     * @param FolderKey $folderKey
     * @param MessageBodyDraft $draft The draft to create
     *
     * @return MessageBodyDraft
     *
     * @throws ServiceException if $draft already has a MessageKey
     */
    public function createMessageBodyDraft(FolderKey $folderKey, MessageBodyDraft $draft): ?MessageBodyDraft;


    /**
     * Updates the MessageBodyDraft with the data.
     * Implementing APIs should be aware of different protocol support and that some server implementations (IMAP)
     * need to create an entirely new Message if data needs to be adjusted, so the MessageKey  of the returned
     * MessageItemDraft might not equal to the MessageKey in $messageItemDraft.
     * The MessageBodyDraft will explicitly get flagged as a "draft".
     *
     * @param MessageBodyDraft $draft The draft to create
     *
     * @return MessageBodyDraft
     *
     * @throws ServiceException if $draft has no messageKey
     */
    public function updateMessageBodyDraft(MessageBodyDraft $draft): ?MessageBodyDraft;


    /**
     * Updated the Message with the specified MessageItemDraft (if the message is flagged as "draft") and returns the
     * updated MessageItemDraft.
     * Implementing APIs should be aware of different protocol support and that some server implementations (IMAP)
     * need to create an entirely new Message if data needs to be adjusted, so the MessageKey  of the returned
     * MessageItemDraft might not equal to the MessageKey in $messageItemDraft.
     * The MessageBodyDraft will explicitly get flagged as a "draft".
     *
     * @param MessageItemDraft $messageItemDraft
     *
     * @return MessageItemDraft|null
     */
    public function updateMessageDraft(MessageItemDraft $messageItemDraft): ?MessageItemDraft;


    /**
     * Sends the MessageItemDraft with the specified $messageKey. The message will not
     * be send if it is not a DRAFT message.
     *
     * @param MessageKey $messageKey
     * @return bool true if sending was successfully, otherwise false
     */
    public function sendMessageDraft(MessageKey $messageKey): bool;


    /**
     * Returns the total number of messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $folderKey
     * @return int
     */
    public function getTotalMessageCount(FolderKey $folderKey): int;


    /**
     * Returns the total number of UNREAD messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $folderKey
     * @return int
     */
    public function getUnreadMessageCount(FolderKey $folderKey): int;


    /**
     * Sets the flags in $flagList for the Message identified with MessageKey.
     *
     * @param MessageKey $messageKey
     * @param FlagList $flagList
     *
     * @return boolean
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList): bool;

    /**
     * Moves the message identified by $messageKey to the destination folder specified with
     * $folderKey. Returns null if the operation failed.
     *
     * @param MessageKey $messageKey
     * @param FolderKey $folderKey
     *
     * @return null|MessageKey
     */
    public function moveMessage(MessageKey $messageKey, FolderKey $folderKey): ?MessageKey;


    /**
     * Returns the MessageItemFieldsProcessor used by this MessageService.
     *
     * @return MessageItemFieldsProcessor
     */
    public function getMessageItemFieldsProcessor(): MessageItemFieldsProcessor;


    /**
     * Returns the ReadableMessagePartContentProcessor used by this MessageService.
     *
     * @return ReadableMessagePartContentProcessor
     */
    public function getReadableMessagePartContentProcessor(): ReadableMessagePartContentProcessor;


    /**
     * Returns the WritableMessagePartContentProcessor used by this MessageService.
     *
     * @return WritableMessagePartContentProcessor
     */
    public function getWritableMessagePartContentProcessor(): WritableMessagePartContentProcessor;


    /**
     * Returns the PreviewTextProcessor used by this MessageService.
     *
     * @return PreviewTextProcessor
     */
    public function getPreviewTextProcessor(): PreviewTextProcessor;


    /**
     * Returns the MailClient used by this MessageService.
     *
     * @return MailClient
     */
    public function getMailClient(): MailClient;
}
