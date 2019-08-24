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

use Conjoon\Util\AbstractList,
    Conjoon\Util\Jsonable,
    Conjoon\Mail\Client\Data\ListMessageItem,
    Conjoon\Mail\Client\Data\MessagePart,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\MessageItemList;


class MessageItemListTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass() {

        $messageItemList = new MessageItemList();
        $this->assertInstanceOf(AbstractList::class, $messageItemList);
        $this->assertInstanceOf(Jsonable::class, $messageItemList);

        $this->assertSame(ListMessageItem::class, $messageItemList->getEntityType());

        $messageItemList[] = new ListMessageItem(
            new MessageKey("dev", "INBOX", "1"), null,
            new MessagePart("foo", "bar", "text/plain")

        );
        $messageItemList[] = new ListMessageItem(
            new MessageKey("dev", "INBOX", "2"), null,
            new MessagePart("foo", "bar", "text/plain")
        );


        $this->assertSame([
            $messageItemList[0]->toJson(),
            $messageItemList[1]->toJson()
        ], $messageItemList->toJson());
    }


}