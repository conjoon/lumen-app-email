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

namespace Conjoon\Mail\Client\Service;

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor,
    Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
    Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\Flag\FlagList;

/**
 * Interface MessageItemService
 *
 * Provides a contract for MessageItem(List)/MessageBody related operations.
 *
 * @package Conjoon\Mail\Client\Service
 */
interface MessageItemService {

    
    /**
     * Returns a MessageItemList containing the MessageItems for the
     * specified MailAccount and the MailFolder.
     * 
     * @param FolderKey $key
     * @param array $options An array with the following query options:
     *  - start (integer) The position from where the items should be returned
     *  - limit (integer) The number of items to return
     *
     * @return MessageItemList
     */
    public function getMessageItemList(FolderKey $key, array $options) :MessageItemList;


    /**
     * Returns a single MessageItem.
     *
     * @param MessageKey $key
     *
     * @return MessageItem
     */
    public function getMessageItem(MessageKey $key) :MessageItem;


    /**
     * Returns a single MessageBody.
     *
     * @param MessageKey $key
     *
     * @return MessageBody
     */
    public function getMessageBody(MessageKey $key) :MessageBody;


    /**
     * Creates a single MessageBody and returns it along with the generated MessageKey.
     * Returns null if the MessageBody could not be created.
     *
     * @param FolderKey $key
     * @param string $textPlain
     * @param string $textHtml
     * @param boolean $createDraftFlag true to make sure the created message is flagged as a draft.
     * Defaults to false.
     *
     * @return MessageBody
     */
    public function createMessageBody(FolderKey $key, string $textPlain = null, string $textHtml = null, $createDraftFlag = false) :?MessageBody;


    /**
     * Updated the Message with the specified MessageItemDraft (if the message is flagged as "draft") and returns the
     * updated MessageItemDraft.
     * Implementing APIs should be aware of different protocol support and that some server implementations (IMAP)
     * need to create an entirely new Message if data needs to be adjusted, so the MessageKey  of the returned
     * MessageItemDraft might not equal to the specified MessageKey.
     *
     * @param MessageKey $key
     * @param MessageItemDraft $messageItemDraft
     *
     * @return MessageItemDraft|null
     */
    public function updateMessageDraft(MessageKey $key, MessageItemDraft $messageItemDraft) :?MessageItemDraft;


    /**
     * Returns the total number of messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $key
     *
     * @return int
     */
    public function getTotalMessageCount(FolderKey $key) : int;


    /**
     * Returns the total number of UNREAD messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $key
     *
     * @return int
     */
    public function getUnreadMessageCount(FolderKey $key) : int;


    /**
     * Sets the flags in $flagList for the Message identified with MessageKey.
     *
     * @param MessageKey $messageKey
     * @param FlagList $flagList
     *
     * @return boolean
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList) :bool;


    /**
     * Returns the MessageItemFieldsProcessor used by this MessageService.
     *
     * @return MessageItemFieldsProcessor
     */
    public function getMessageItemFieldsProcessor() :MessageItemFieldsProcessor;


    /**
     * Returns the ReadableMessagePartContentProcessor used by this MessageService.
     *
     * @return ReadableMessagePartContentProcessor
     */
    public function getReadableMessagePartContentProcessor() :ReadableMessagePartContentProcessor;


    /**
     * Returns the WritableMessagePartContentProcessor used by this MessageService.
     *
     * @return WritableMessagePartContentProcessor
     */
    public function getWritableMessagePartContentProcessor() :WritableMessagePartContentProcessor;


    /**
     * Returns the PreviewTextProcessor used by this MessageService.
     *
     * @return PreviewTextProcessor
     */
    public function getPreviewTextProcessor() :PreviewTextProcessor;


    /**
     * Returns the MailClient used by this MessageService.
     *
     * @return MailClient
     */
    public function getMailClient() :MailClient;

}