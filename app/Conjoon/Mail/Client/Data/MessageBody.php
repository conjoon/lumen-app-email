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
 * Class MessageBody models a simplified representation of  mail message
 * body-content informations.
 *
 * @example
 *
 *    $body = new MessageBody(new MessageKey("INBOX", "232"));
 *
 *    $plainPart = new MessagePart("foo", "ISO-8859-1");
 *    $htmlPart = new MessagePart("<b>bar</b>", "UTF-8");
 *
 *    $body->setTextPlain($plainPart);
 *    $body->setTextHtml($htmlPart);
 *
 *    $body->getTextPlain();// $plainPart
 *    $body->getTextHtml(); // $htmlPart
 *
 * @package Conjoon\Mail\Client\Data
 */
class MessageBody  {


    /**
     * @var MessageKey
     */
    protected $messageKey;

    /**
     * @var MessagePart
     */
    protected $textHtml;

    /**
     * @var MessagePart
     */
    protected $textPlain;


    /**
     * MessageBody constructor.
     *
     * @param MessageKey $messageKey
     */
    public function __construct(MessageKey $messageKey) {

        $this->messageKey = $messageKey;
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


    /**
     * Returns the MessageKey of this MessageBody.
     *
     * @return MessageKey
     */
    public function getMessageKey() {
        return $this->messageKey;
    }


}