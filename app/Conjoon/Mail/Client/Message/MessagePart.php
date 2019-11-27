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

use Conjoon\Util\Copyable;


/**
 * Class MessagePart models a simplified representation of a Mail Message Part,
 * containing content and charset information.
 *
 * @example
 *
 *    $part = new MessagePart();
 *
 *    $body->setContents("foo");
 *    $item->setCharset("ISO-8859-1");
 *
 *    $body->getContents();// "foo"
 *    $item->getCharset(); // "ISO-8859-1"
 *
 * @package Conjoon\Mail\Client\Message
 */
class MessagePart implements Copyable  {

    /**
     * @vr string
     */
    protected $mimeType = "";

    /**
     * @var string
     */
    protected $contents = "";

    /**
     * @var string
     */
    protected $charset = "";


    /**
     * MessagePart constructor.
     *
     * @param string $contents
     * @param string $charset
     */
    public function __construct(string $contents, string $charset, string $mimeType) {
        $this->setContents($contents, $charset);
        $this->setMimeType($mimeType);
    }


    /**
     * Sets the "contents" for this part.
     *
     * @param String $contents
     * @param String $charset
     *
     * @return $this
     */
    public function setContents(string $contents, string $charset) {
        $this->contents = $contents;
        $this->setCharset($charset);
        return $this;
    }


    /**
     * Returns the contents of this part.
     *
     * @return string
     */
    public function getContents() {
        return $this->contents;
    }


    /**
     * Sets the "$charset" for this part.
     *
     * @param String $contents
     *
     * @return $this
     */
    protected function setCharset(string $charset) {
        $this->charset = $charset;
        return $this;
    }


    /**
     * Returns the $charset of this part.
     *
     * @return string
     */
    public function getCharset() {
        return $this->charset;
    }


    /**
     * Sets the "$mimeType" for this part.
     *
     * @param String $mimeType
     *
     * @return $this
     */
    protected function setMimeType(string $mimeType) {
        $this->mimeType = $mimeType;
        return $this;
    }


    /**
     * Returns the $mimeType of this part.
     *
     * @return string
     */
    public function getMimeType() {
        return $this->mimeType;
    }


// +----------------------------
// | Interface Copyable
// +----------------------------

    /**
     * @inheritdoc
     */
    public function copy() :MessagePart {

        return new MessagePart($this->getContents(), $this->getCharset(), $this->getMimeType());

    }


}