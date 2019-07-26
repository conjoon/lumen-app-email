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

namespace Conjoon\Mail\Client\Data;


/**
 * Class MessageKey models a compound key for identifying a (IMAP) Message.
 *
 * @example
 *
 *    $key = new MessageKey("INBOX", "3323");
 *
 *    $key->getMailFolderId(); // "INBOX"
 *    $key->getId(); // "3323"
 *
 * @package Conjoon\Mail\Client\Data
 */
class MessageKey  {


    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $mailFolderId;


    /**
     * MessageKey constructor.
     *
     * @param string $mailFolderId
     * @param string $id
     */
    public function __construct(string $mailFolderId, string $id) {
        $this->mailFolderId = $mailFolderId;
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getMailFolderId() :string {
        return $this->mailFolderId;
    }


    /**
     * @return string
     */
    public function getId() :string {
        return $this->id;
    }


    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toArray() :array {
        return [
            'id'             => $this->getId(),
            'mailFolderId'   => $this->getMailFolderId()
        ];
    }

}