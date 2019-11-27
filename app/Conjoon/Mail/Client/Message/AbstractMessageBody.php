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

namespace Conjoon\Mail\Client\Message;

use Conjoon\Util\Jsonable,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey;

/**
 * Class AbstractMessageBody models a simplified representation of mail message
 * body-contents.
 *
 * @package Conjoon\Mail\Client\Message
 */
abstract class AbstractMessageBody implements Jsonable {


    /**
     * @var MessagePart
     */
    protected $textHtml;

    /**
     * @var MessagePart
     */
    protected $textPlain;

    /**
     * @var MessageKey
     */
    protected $messageKey;


    /**
     * MessageBody constructor.
     *
     * @param MessageKey $messageKey
     */
    public function __construct(MessageKey $messageKey = null) {

        $this->messageKey = $messageKey;
    }


    /**
     * Returns the MessageKey of this MessageBody.
     *
     * @return MessageKey
     */
    public function getMessageKey() {
        return $this->messageKey;
    }


    /**
     * Sets the "textHtml" property of this body.
     *
     * @param MessagePart $textHtml
     * @return $this
     */
    public function setTextHtml(MessagePart $textHtml) {
        $this->textHtml = $textHtml;
        return $this;
    }


    /**
     * Returns the textHtml property of this body.
     * @return MessagePart
     */
    public function getTextHtml() {
        return $this->textHtml;
    }


    /**
     * Sets the "textPlain" property of this body.
     *
     * @param MessagePart $textPlain
     * @return $this
     */
    public function setTextPlain(MessagePart $textPlain) {
        $this->textPlain = $textPlain;
        return $this;
    }


    /**
     * Returns the textPlain property of this body.
     * @return MessagePart
     */
    public function getTextPlain() {
        return $this->textPlain;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MessageBodyDraft.
     *
     * Each entry in the returning array must consist of the following key/value-pairs:
     *
     * - textHtml (string) - this instances textHtml part's content-value
     * - textPlain (string) - this instances textPlain part's content-value
     *
     * Implementing APIs should make sure to properly encode the content of the parts
     * from the given charset to UTF-8 to prevent errors when trying to send the resulting
     * array as JSON to interested clients.
     *
     * @return array
     *
     */
    public function toJson() :array{

        $keyJson = $this->getMessageKey() ? $this->getMessageKey()->toJson() : null;

        return array_merge($keyJson ? $keyJson : [], [
            "textHtml" => $this->getTextHtml() ? $this->getTextHtml()->getContents() : "",
            "textPlain" => $this->getTextPlain() ? $this->getTextPlain()->getContents() : ""
        ]);
    }

}