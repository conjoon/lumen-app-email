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
 * Class MessageBodyDraft represents a draft of a MessageBody, that may have a MessageKey
 * if it is a draft physically associated with a message. This class allows for changing
 * the MessageKey, ultimately returning a new instance of a MessageBodyDraft if the
 * key was changed.
 *
 * @example
 *
 *    $body = new MessageBodyDraft(new MessageKey("a", "b", "c"));
 *
 *    *
 *    $keyedDraft = $body->setMessageKey(new MessageKey("x", "y", "z"));
 *
 *    $keyedDraft !== $body; // true
 *
 * @package Conjoon\Mail\Client\Message
 */
class MessageBodyDraft extends AbstractMessageBody
{


    /**
     * Sets the "messageKey" by creating a new MessageBodyDraft with the specified
     * key and returning a new instance with this data.
     * No references to any data of the original instance will be available.
     *
     * @param MessageKey $messageKey
     *
     * @return $this
     */
    public function setMessageKey(MessageKey $messageKey): MessageBodyDraft
    {

        $plain = $this->getTextPlain() ? $this->getTextPlain()->copy() : null;
        $html = $this->getTextHtml() ? $this->getTextHtml()->copy() : null;

        $copy = new self($messageKey);

        $html && $copy->setTextHtml($html);
        $plain && $copy->setTextPlain($plain);

        return $copy;
    }
}
