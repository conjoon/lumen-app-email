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

use Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey;


class ListMessageItemTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor() {

        $messageKey = $this->createMessageKey();
        $messageItem = new ListMessageItem($messageKey, null, $this->createMessagePart());
        $this->assertInstanceOf(MessageItem::class, $messageItem);
    }


    /**
     * Test class.
     */
    public function testClass() {

        $previewText = "foobar";

        $messageKey = $this->createMessageKey();

        $messageItem = new ListMessageItem(
            $messageKey, ["subject" => "YO!"],
            $this->createMessagePart($previewText, "UTF-8", "text/plain")
        );

        $this->assertSame($previewText, $messageItem->getMessagePart()->getContents());

        $messageItem->getMessagePart()->setContents("snafu", "UTF-8");
        $this->assertSame("snafu", $messageItem->getMessagePart()->getContents());

        $arr = $messageItem->toJson();
        $this->assertArraySubset([
            "subject" => "YO!",
            "previewText" => "snafu"
        ], $arr);

    }



// ---------------------
//    Helper Functions
// ---------------------
    /**
     * Returns a MessageKey.
     *
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $id
     *
     * @return MessageKey
     */
    protected function createMessageKey($mailAccountId = "dev", $mailFolderId = "INBOX", $id = "232") :MessageKey {
        return new MessageKey($mailAccountId, $mailFolderId, $id);
    }


    protected function createMessagePart($text = "a", $charset = "b", $mimeType = "c") {
        return new MessagePart($text, $charset, $mimeType);
    }
}
