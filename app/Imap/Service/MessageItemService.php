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

namespace App\Imap\Service;

use App\Imap\ImapAccount;

/**
 * Interface MessageItemService
 *
 * @package App\Imap\Service
 */
interface MessageItemService {

    
    /**
     * Returns a list of MessageItems for the specified $mailFolderId for the specified
     * $account. If the folder does not exist, an empty array will be returned.
     * Each entry in the returning array must consist of the following key/value-pairs:
     * 
     * - subject (string) The subject of the message
     * - date (string) The date of the message in the format "Y-m-d H:i"
     * - from (array) An array containing "name" and "address"
     * - seen (bool) Whether the message was flagged as "seen"
     * - answered (bool) Whether the message was flagged as answered
     * - flagged (bool) Whether the message was "flagged"
     * - draft (bool) Whether the message is a draft
     * - recent (bool) Whether the message has the "recent" state
     * - hasAttachments (bool) Whether the message has attachments
     * - to (array) An array containing a list of entries with "name"/"address" key/value-pairs
     * - previewText (string) A 200 character long preview of the MessageBody's text
     * - size (integer) The size of the message in bytes
     * - mailFolderId (string) The mailFolderId (global name) this message belongs to
     * - mailAccountId (string) The id of the MailAccount of this message
     * - id (string) A unique id of the message, if not globally unique, then unique for the
     * mailbox this message was found in
     * 
     * @param ImapAccount $account
     * @param string $mailFolderId
     * @param array $options An array with the following query options:
     *  - start (integer) The position from where the items should be returned
     *  - limit (integer) The number of items to return
     *
     * @return array An array with the following keys:
     *  - total: The number of total messages found in the mailbox
     *  - data: The list of items retrieve according to the specification above
     *  - meta: An array provifing the following keys:
     *       - cn_unreadCount: The number of unread messages in this mailbox
     *       - mailFolderId: the global name of the mailbox that was just queried
     *       - mailAccountId: the id of the ImapAccount that was queried
     */
    public function getMessageItemsFor(ImapAccount $account, string $mailFolderId, array $options) :array;


    /**
     * Returns a single MessageItem with the following informations:
     *
     * - subject (string)
     * - date (string)
     * - from (array)
     * - seen (bool)
     * - answered (bool)
     * - flagged (bool)
     * - draft (bool)
     * - recent (bool)
     * - hasAttachments (bool)
     * - to (array)
     * - size (integer)
     * - mailFolderId (string)
     * - mailAccountId (string)
     * - id (string)
     *
     * Note how no previewText is returned for a single MessageItem.
     *
     * @param ImapAccount $account
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return array
     */
    public function getMessageItemFor(ImapAccount $account, string $mailFolderId, string $messageItemId) :array;


    /**
     * Returns an array with informations about the MessageBody of a MessageItem.
     *
     * - mailFolderId (string)
     * - mailAccountId (string)
     * - id (string)
     * - textPlain (string) A renderable, displayable, fully decoded and properly formatted text representation
     * of the Message
     * - textHtml (string) A renderable, displayable, fully decoded and properly formatted html representation
     * of the Message
     *
     * @param ImapAccount $account
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return array
     */
    public function getMessageBodyFor(ImapAccount $account, string $mailFolderId, string $messageItemId) :array;

}