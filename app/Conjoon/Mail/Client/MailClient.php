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

namespace Conjoon\Mail\Client;

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\MessageItem,
    Conjoon\Mail\Client\Data\MessageBody,
    Conjoon\Mail\Client\Data\MessageItemList;

/**
 * Interface MailClient
 * @package Conjoon\Mail\Client
 */
interface MailClient {


    /**
     * Returns the specified MessageItem for the submitted arguments.
     *
     * @param MessageKey $key
     * @param array|null $options
     *
     * @return MessageItem|null The MessageItem or null if none found.
     *
     * @throws MailClientException if any exception occurs
     */
     public function getMessageItem(MessageKey $key, array $options = null) :?MessageItem;


    /**
     * Returns the specified MessageBody for the submitted arguments.
     *
     * @param MessageKey $key
     *
     * @return MessageBody The MessageBody or null if none found.
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageBody(MessageKey $key) :?MessageBody;


    /**
     * Returns the specified MessageList for the submitted arguments.
     *
     * @param FolderKey $key
     * @param array|null $options An additional set of options for querying the MessageList, such
     * as sort-direction or start/limit values.
     *
     * @return MessageItemList
     *
     * @throws MailClientException if any exception occurs
     */
    public function getMessageItemList(FolderKey $key , array $options = null) :MessageItemList;


    /**
     * Returns the total number of messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $key
     *
     * @return int
     *
     * @throws MailClientException if any exception occurs
     */
    public function getTotalMessageCount(FolderKey $key) : int;


    /**
     * Returns the total number of UNRWAD messages in the specified $mailFolderId for the specified $account;
     *
     * @param FolderKey $key
     *
     * @return int
     *
     * @throws MailClientException if any exception occurs
     */
    public function getUnreadMessageCount(FolderKey $key) : int;


}