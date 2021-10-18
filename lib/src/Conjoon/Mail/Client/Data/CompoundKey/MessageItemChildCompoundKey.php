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

use InvalidArgumentException;

/**
 * Class MessageItemChildCompoundKey models a class for compound keys for entities belonging
 * to Messages, such as attachments.
 *
 * @package Conjoon\Mail\Client\Data\CompoundKey
 */
abstract class MessageItemChildCompoundKey extends MessageKey
{


    /**
     * @var string
     */
    protected string $parentMessageItemId;


    /**
     * MessageItemChildCompoundKey constructor.
     *
     * @param string|MessageKey $mailAccountId
     * @param string $mailFolderId
     * @param string|null $parentMessageItemId
     * @param string|null $id
     *
     */
    public function __construct(
        $mailAccountId,
        string $mailFolderId,
        string $parentMessageItemId = null,
        string $id = null
    ) {

        if ($mailAccountId instanceof MessageKey) {
            $id = $mailFolderId;
            $parentMessageItemId = $mailAccountId->getId();
            $mailFolderId = $mailAccountId->getMailFolderId();
            $mailAccountId = $mailAccountId->getMailAccountId();
        } elseif ($id === null || $parentMessageItemId === null) {
            throw new InvalidArgumentException("\"id\" and \"parentMessageItemId\" must not be null.");
        }

        parent::__construct($mailAccountId, $mailFolderId, $id);

        $this->parentMessageItemId = $parentMessageItemId;
    }


    /**
     * @return string
     */
    public function getParentMessageItemId(): string
    {
        return $this->parentMessageItemId;
    }


    /**
     * Returns an array representation of this object.
     *
     * @return array
     */
    public function toJson(): array
    {
        $json = parent::toJson();
        $json["parentMessageItemId"] = $this->getParentMessageItemId();

        return $json;
    }
}
