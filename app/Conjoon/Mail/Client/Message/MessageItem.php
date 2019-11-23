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

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;

/**
 * Class MessageItem models envelope informations of a Message.
 *
 *
 * @package Conjoon\Mail\Client\Message
 */
class MessageItem extends AbstractMessageItem {


    /**
     * @var int
     */
    protected $size;

    /**
     * @var bool
     */
    protected $hasAttachments;

    /**
     * @var string
     */
    protected $charset;


    /**
     * @inheritdoc
     */
    protected function checkType($property, $value) {

        switch ($property) {
            case "charset":
                if (!is_string($value)) {
                    return "string";
                }
                break;

            case "size":
                if (!is_int($value)) {
                    return "int";
                }
                break;

            case "hasAttachments":
                if (!is_bool($value)) {
                    return "bool";
                }
                break;
        }

        return true;
    }



// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MessageItem.
     *
     * @return array
     */
    public function toJson() :array{

        return array_merge(parent::toJson(), [
            'size'           => $this->getSize(),
            'hasAttachments' => $this->getHasAttachments()
        ]);
    }

}