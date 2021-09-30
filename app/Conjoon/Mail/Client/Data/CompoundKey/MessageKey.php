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

namespace Conjoon\Mail\Client\Data\CompoundKey;


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
class MessageKey extends CompoundKey {


    /**
     * @var string
     */
    protected $mailFolderId;


    /**
     * MessageKey constructor.
     *
     * @param string|MailAccount|FolderKey $mailAccountId
     * @param string $mailFolderId
     * @param string $id
     *
     * @throws \InvalidArgumentException if $mailAccountId is not a FolderKey and $id is null
     */
    public function __construct($mailAccountId, string $mailFolderId, string $id = null) {

        if ($mailAccountId instanceof FolderKey) {
            $id            = $mailFolderId;
            $mailFolderId  = $mailAccountId->getId();
            $mailAccountId = $mailAccountId->getMailAccountId();
        } else if ($id === null) {
            throw new \InvalidArgumentException("\"id\" must not be null.");
        }

        parent::__construct($mailAccountId, $id);

        $this->mailFolderId = $mailFolderId;
    }


    /**
     * @return string
     */
    public function getMailFolderId() :string {
        return $this->mailFolderId;
    }

    /**
     * Returns a FolderKey representing MailAccount and Folder for this MessageKey.
     * Returned instances will never share the same reference.
     *
     * @return FolderKey
     */
    public function getFolderKey() :FolderKey {
        return new FolderKey($this->mailAccountId, $this->mailFolderId);
    }


    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toJson() :array {
        $json = parent::toJson();
        $json["mailFolderId"] = $this->getMailFolderId();

        return $json;
    }

}
