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

use Conjoon\Mail\Client\Data\MessageItem,
    Conjoon\Mail\Client\Data\MessageKey,
    Conjoon\Mail\Client\Data\MessageItemList;


class MessageItemListTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor() {

        $messageItemList = new MessageItemList();
        $this->assertInstanceOf(MessageItemList::class, $messageItemList);
        $this->assertInstanceOf(\ArrayAccess::class, $messageItemList);
        $this->assertInstanceOf(\Iterator::class, $messageItemList);
    }


    /**
     * Tests ArrayAccess /w type exception
     */
    public function testArrayAccessException() {

        $this->expectException(\TypeError::class);

        $messageItemList = new MessageItemList();
        $messageItemList[] = "foo";
    }


    /**
     * Tests ArrayAccess
     */
    public function testArrayAccess() {

        $messageItemList = new MessageItemList();

        $cmpList = [
            $this->createMessageItem(),
            $this->createMessageItem()
        ];

        $messageItemList[] = $cmpList[0];
        $messageItemList[] = $cmpList[1];

        foreach ($messageItemList as $key => $item) {
            $this->assertSame($cmpList[$key], $item);
        }
    }


// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @var int
     */
    protected static $messageItemCount = 0;


    /**
     * Returns an MessageItem
     */
    protected function createMessageItem() {
        return new MessageItem(new MessageKey("INBOX", ++self::$messageItemCount));
    }



}