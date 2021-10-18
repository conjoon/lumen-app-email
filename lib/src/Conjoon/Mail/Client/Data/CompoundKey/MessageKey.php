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

namespace Conjoon\Mail\Client\Data\CompoundKey;

use Conjoon\Mail\Client\Data\MailAccount;
use InvalidArgumentException;

/**
 * Class MessageKey models a class for compound keys for identifying (IMAP) Messages.
 *
 * @example
 *
 *    // plain constructor arguments
 *    // the examples below all create a MessageKey with the same
 *    // data
 *    new MessageKey("dev", "INBOX", "123!);
 *
 *    // passing a MailAccount
 *    new MessageKey(
 *        new \Conjoon\Mail\Client\Data\MailAccount(["id" => "dev"]),
 *        "INBOX",
 *        "123"
 *    );
 *
 *    // passing a FolderKey
 *    new MessageKey(
 *        new FolderKey("dev", "INBOX"),
 *        "123"
 *    );
 *
 * @package Conjoon\Mail\Client\Data\CompoundKey
 */
class MessageKey extends CompoundKey
{


    /**
     * @var string
     */
    protected string $mailFolderId;


    /**
     * MessageKey constructor.
     *
     * @param string|MailAccount|FolderKey $keyPart1
     * @param string $keyPart2
     * @param string|null $keyPart3
     *
     * @see constructFromFolderKey
     * @see constructFromMailAccount
     * @see constructFromMessageKeyParts
     */
    public function __construct($keyPart1, string $keyPart2, string $keyPart3 = null)
    {

        if ($keyPart1 instanceof FolderKey) {
            $this->constructFromFolderKey($keyPart1, $keyPart2);
            return;
        }

        if ($keyPart3 === null) {
            throw new InvalidArgumentException("\"$keyPart3\" representing the \"id\" must not be null.");
        }

        if ($keyPart1 instanceof MailAccount) {
            $this->constructFromMailAccount($keyPart1, $keyPart2, $keyPart3);
            return;
        }

        $this->constructFromMessageKeyParts($keyPart1, $keyPart2, $keyPart3);
    }


    /**
     * Constructs from the passed arguments.
     *
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $id
     */
    private function constructFromMessageKeyParts(string $mailAccountId, string $mailFolderId, string $id)
    {
        parent::__construct($mailAccountId, $id);
        $this->mailFolderId = $mailFolderId;
    }


    /**
     * Constructs given the information from the submitted MaiLAccount.
     *
     * @param MailAccount $mailAccount
     * @param string $folderId
     * @param string $id
     */
    private function constructFromMailAccount(MaiLAccount $mailAccount, string $folderId, string $id)
    {
        parent::__construct($mailAccount, $id);
        $this->mailFolderId = $folderId;
    }


    /**
     * Constructs given the information from the submitted FolderKey.
     *
     * @param FolderKey $folderKey
     * @param string $id
     */
    private function constructFromFolderKey(FolderKey $folderKey, string $id)
    {
        parent::__construct($folderKey->getMailAccountId(), $id);
        $this->mailFolderId = $folderKey->getId();
    }


    /**
     * @return string
     */
    public function getMailFolderId(): string
    {
        return $this->mailFolderId;
    }

    /**
     * Returns a FolderKey representing MailAccount and Folder for this MessageKey.
     * Returned instances will never share the same reference.
     *
     * @return FolderKey
     */
    public function getFolderKey(): FolderKey
    {
        return new FolderKey($this->mailAccountId, $this->mailFolderId);
    }


    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toJson(): array
    {
        $json = parent::toJson();
        $json["mailFolderId"] = $this->getMailFolderId();

        return $json;
    }
}
