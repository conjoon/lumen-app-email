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
use Conjoon\Util\Jsonable;
use Conjoon\Util\Stringable;

/**
 * Class MessageKey models base class for compound keys for identifying (IMAP) Messages.
 *
 * Each key subclassing CompoundKey has a mailAccountId that represents the id of a specified
 * MailAccount, as well as an "id" field that represents the unique key with which the entity
 * using the CompoundKey can be identified.
 *
 *
 * @package Conjoon\Mail\Client\Data\CompoundKey
 */
abstract class CompoundKey implements Jsonable, Stringable
{


    /**
     * @var string
     */
    protected string $id;


    /**
     * @var string
     */
    protected string $mailAccountId;


    /**
     * CompoundKey constructor.
     *
     * @param string|MailAccount $mailAccountId
     * @param string $id
     */
    public function __construct($mailAccountId, string $id)
    {
        if ($mailAccountId instanceof MailAccount) {
            $mailAccountId = $mailAccountId->getId();
        }
        $this->mailAccountId = (string)$mailAccountId;
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getMailAccountId(): string
    {
        return $this->mailAccountId;
    }


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }


    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'id' => $this->getId(),
            'mailAccountId' => $this->getMailAccountId()
        ];
    }


    /**
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->toJson());
    }
}
