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
 * Class MessageItemList organizes a list of MessageItems.
 *
 * @example
 *
 *    $list = new MessageItemList();
 *
 *    $item = new MessageItem(new MessageKey("INBOX", "232");
 *    $list[] = $item;
 *
 *    foreach ($list as $key => $mItem) {
 *        // iterating over the item
 *    }
 *
 * @package Conjoon\Mail\Client\Data
 */
class MessageItemList implements \ArrayAccess, \Iterator  {


    /**
     * @var array
     */
    protected $data = [];

    /**
     * \Iterator Interface
     * @var int
     */
    protected $position = 0;


// -------------------------
//  ArrayAccess Interface
// -------------------------

    /**
     * @inheritdoc
     *
     * @throws \TypeException if $value is not of the type MessageItem
     */
    public function offsetSet($offset, $value) {

        if (!$value instanceof MessageItem) {
            throw new \TypeError(
            "Expected type \"".MessageItem::class."\" for value-argument"
            );
        }

        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }


    /**
     * @inheritdoc
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }


    /**
     * @inheritdoc
     */
    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }


// --------------------------
//  Iterator Interface
// --------------------------

    /**
     * @inheritdoc
     */
    public function rewind() {

        $this->position = 0;
    }

    /**
     * @inheritdoc
     */
    public function key() {
        return $this->position;
    }

    /**
     * @inheritdoc
     */
    public function current() {
        return $this->data[$this->position];
    }

    /**
     * @inheritdoc
     */
    public function next() {
        $this->position++;
    }

    /**
     * @inheritdoc
     */
    public function valid() {
        return isset($this->data[$this->position]);
    }


}