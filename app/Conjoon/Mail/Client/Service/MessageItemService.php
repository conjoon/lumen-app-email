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
    Conjoon\Mail\Client\Message\Text\MessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
    Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageBody;

/**
 * Interface MessageItemService
 *
 * Provides a contract for MessageItem(List)/MessageBody related operations.
 *
 * @package Conjoon\Mail\Client\Service
 */
interface MessageItemService {

    
    /**
     * Returns a MessageItemListServiceObject containing the MessageItems for the
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
     * Returns a single MessageItemServiceObject.
     *
     * @param MessageKey $key
     *
     * @return MessageItem
     */
    public function getMessageItem(MessageKey $key) :MessageItem;


    /**
     * Returns a single MessageBodyServiceObject.
     *
     * @param MessageKey $key
     *
     * @return MessageBody
     */
    public function getMessageBody(MessageKey $key) :MessageBody;


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
     * Returns the MessageItemFieldsProcessor used by this MessageService.
     *
     * @return MessageItemFieldsProcessor
     */
    public function getMessageItemFieldsProcessor() :MessageItemFieldsProcessor;


    /**
     * Returns the MessagePartContentProcessor used by this MessageService.
     *
     * @return MessagePartContentProcessor
     */
    public function getMessagePartContentProcessor() :MessagePartContentProcessor;


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