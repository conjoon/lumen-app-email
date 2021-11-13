<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Mail\Client\Imap\Util;

use Conjoon\Mail\Client\Folder\FolderIdToTypeMapper;
use Conjoon\Mail\Client\Folder\ListMailFolder;
use Conjoon\Mail\Client\Folder\MailFolder;

/**
 * Class DefaultFolderIdToTypeMapper.
 * Default implementation of a FolderIdToTypeMapper.
 *
 * @package Conjoon\Mail\Client\Imap\Util
 */
class DefaultFolderIdToTypeMapper implements FolderIdToTypeMapper
{


// +-------------------------------
// | FolderIdToTypeMapper
// +-------------------------------

    /**
     * Function to map a folder-id (global name) to a Folder-Type.
     * Names will be split given the specified delimiter and a lookup on the
     * first two parts (if available) will be made to guess the type this id should represent.
     * For example, "INBOX" will always be an INBOX-type, whereas INBOX.Drafts
     * will be of type "DRAFT". Global names consisting of more than 2 parts will
     * automatically have the type "FOLDER".
     *
     * @example
     *
     *   $listMailFolder = new ListMailFolder(new FolderKey("dev", "INBOX"),
     *                                       ["name"        => "INBOX",
     *                                        "delimiter"   => "."
     *                                        "unreadCount" => 4]);
     *   $this->getFolderType($listMailFolder); // returns "INBOX"
     *
     *   $listMailFolder = new ListMailFolder(new FolderKey("dev", "INBOX.SomeFolder.Deep.Drafts"),
     *                                       ["name"        => "Drafts",
     *                                        "delimiter"   => "."
     *                                        "unreadCount" => 4]);
     *   $this->getFolderType($listMailFolder); // returns "FOLDER"
     *
     *   $listMailFolder = new ListMailFolder(new FolderKey("dev", "INBOX.Drafts"),
     *                                       ["name"        => "Drafts",
     *                                        "delimiter"   => "."
     *                                        "unreadCount" => 4]);
     *   $this->getFolderType($listMailFolder); // returns "DRAFT"
     *
     *   $listMailFolder = new ListMailFolder(new FolderKey("dev", "INBOX.Trash.Deep.Deeper.Folder"),
     *                                       ["name"        => "Folder",
     *                                        "delimiter"   => "."
     *                                        "unreadCount" => 4]);
     *   $this->getFolderType($listMailFolder); // returns "FOLDER"
     *
     * @param ListMailFolder $listMailFolder
     *
     * @return string MailFolder::TYPE_FOLDER|MailFolder::TYPE_TRASH|MailFolder::TYPE_SENT|
     *                MailFolder::TYPE_DRAFT|MailFolder::TYPE_JUNK|MailFolder::TYPE_FOLDER
     */
    public function getFolderType(ListMailFolder $listMailFolder): string
    {

        $name = $listMailFolder->getFolderKey()->getId();
        $delimiter = $listMailFolder->getDelimiter();

        $path = explode($delimiter, $name);

        $type = MailFolder::TYPE_FOLDER;

        if (count($path) > 2) {
            return $type;
        }

        switch (mb_strtoupper($name)) {
// +------------------
// | INBOX
// +------------------
            case "[GOOGLE MAIL]/ALLE NACHRICHTEN":
            case "INBOX":
                $type = MailFolder::TYPE_INBOX;
                break;

// +------------------
// | TRASH
// +------------------
            case "[GOOGLE MAIL]/PAPIERKORB":
            case "TRASH":
            case "JUNK-E-MAIL":
            case "GELÖSCHTE ELEMENTE":
            case "INBOX" . $delimiter . "TRASH":
            case "INBOX" . $delimiter . "PAPIERKORB":
            case "INBOX" . $delimiter . "DELETED ITEMS":
                $type = MailFolder::TYPE_TRASH;
                break;

// +------------------
// | DRAFT
// +------------------
            case "[GOOGLE MAIL]/ENTWÜRFE":
            case "DRAFTS":
            case "ENTWÜRFE":
            case "INBOX" . $delimiter . "DRAFTS":
                $type = MailFolder::TYPE_DRAFT;
                break;

// +------------------
// | SENT
// +------------------
            case "[GOOGLE MAIL]/GESENDET":
            case "GESENDETE ELEMENTE":
            case "SENT":
            case "INBOX" . $delimiter . "SENT":
            case "INBOX" . $delimiter . "SENT MESSAGES":
            case "INBOX" . $delimiter . "SENT ITEMS":
                $type = MailFolder::TYPE_SENT;
                break;

// +------------------
// | JUNK
// +------------------
            case "[GOOGLE MAIL]/SPAM":
            case "JUNK":
            case "INBOX" . $delimiter . "JUNK":
            case "INBOX" . $delimiter . "SPAM":
                $type = MailFolder::TYPE_JUNK;
                break;
        }

        return $type;
    }
}
