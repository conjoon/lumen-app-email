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

namespace Conjoon\Mail\Client\Message;

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;

/**
 * Class ListMessageItem models envelope information along with a MessagePart
 * for preview purposes.
 * It is up to the implementing client to make sure that the MessagePart's contents
 * are set to a proper readable text for the requesting client.
 *
 * @example
 *
 *    $mp = new MessagePart("foo", "UTF-8", "text/plain");
 *
 *    $item = new ListMessageItem(
 *              new MessageKey("dev", "INBOX", "232"),
 *              ["date" => new \DateTime()],
 *              $mp
 *            );
 *
 *    $item->getMessageKey();
 *    $item->setSubject("Foo");
 *    $item->getSubject(); // "Foo"
 *    $item->getMessagePart(); // instance of MessagePart
 *
 * #toJson will return an additional property "previewText" with the contents of the
 * MessagePart.
 *
 *    $item->toJson()["previewText"]; // outputs $mp->getContents();
 *
 *
 * @package Conjoon\Mail\Client\Message
 */
class ListMessageItem extends MessageItem
{

    /**
     * @var MessagePart|null
     */
    protected ?MessagePart $messagePart = null;

    /**
     * ListMessageItem constructor.
     *
     * @param MessageKey $messageKey
     * @param array|null $data
     * @param MessagePart|null $messagePart
     */
    public function __construct(MessageKey $messageKey, array $data = null, MessagePart $messagePart = null)
    {

        parent::__construct($messageKey, $data);

        $this->messagePart = $messagePart;
    }


    /**
     * Returns the MessagePart set for this ListMessageItem.
     *
     * @return MessagePart
     */
    public function getMessagePart(): ?MessagePart
    {
        return $this->messagePart;
    }

    /**
     * Sets the MessagePart set for this ListMessageItem.
     *
     * @return ListMessageItem
     */
    public function setMessagePart(?MessagePart $messagePart): ?ListMessageItem
    {
        $this->messagePart = $messagePart;
        return $this;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public function toJson(): array
    {
        $data = parent::toJson();

        if ($this->getMessagePart()) {
            $data["previewText"] = $this->getMessagePart()->getContents();
        }

        return $this->buildJson($data);
    }
}
