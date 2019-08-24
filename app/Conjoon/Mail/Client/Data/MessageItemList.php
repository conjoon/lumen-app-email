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

use Conjoon\Util\Jsonable,
    Conjoon\Util\AbstractList;

/**
 * Class MessageItemList organizes a list of ListMessageItems.
 *
 * @example
 *
 *    $list = new MessageItemList();
 *
 *    $item = new ListMessageItem(new MessageKey("INBOX", "232"), null, new MessagePart("foo", "UTF-8", "text/plain));
 *    $list[] = $item;
 *
 *    foreach ($list as $key => $mItem) {
 *        // iterating over the item
 *    }
 *
 * @package Conjoon\Mail\Client\Data
 */
class MessageItemList extends AbstractList implements Jsonable {



// -------------------------
//  AbstractList
// -------------------------

    /**
     * @inheritdoc
     */
    public function getEntityType() :string{
        return ListMessageItem::class;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MessageItemList.
     *
     * Each entry in the returning array holds a JSON representation of
     * a MessageItem.
     *
     * @return array
     */
    public function toJson() :array{

        $data = [];

        foreach ($this->data as $key => $messageItem) {
            $data[] = $messageItem->toJson();
        }

        return $data;
    }


}