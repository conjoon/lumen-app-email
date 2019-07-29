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
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList;


class MessageItemTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor() {

        $messageKey = $this->createMessageKey();
        $messageItem = new MessageItem($messageKey);
        $this->assertInstanceOf(MessageItem::class, $messageItem);
    }


    /**
     * Test class.
     */
    public function testClass() {

        $item = $this->getItemConfig();

        $messageKey = $this->createMessageKey();

        $messageItem = new MessageItem($messageKey, $item);

        $this->assertInstanceOf(MessageItem::class, $messageItem);

        $this->assertSame($messageKey, $messageItem->getMessageKey());

        foreach ($item as $key => $value) {

            $method = "get" . ucfirst($key);

            switch ($key) {
                case 'date':
                    $this->assertNotSame($item["date"], $messageItem->getDate());
                    $this->assertEquals($item["date"], $messageItem->getDate());
                    break;
                case 'from':
                    $this->assertNotSame($item["from"], $messageItem->getFrom());
                    $this->assertEquals($item["from"], $messageItem->getFrom());
                    break;
                case 'to':
                    $this->assertNotSame($item["to"], $messageItem->getTo());
                    $this->assertEquals($item["to"], $messageItem->getTo());
                    break;
                default :
                    $this->assertSame($messageItem->{$method}(), $item[$key], $key);
            }
        }
    }


    /**
     * Test type exceptions.
     */
    public function testTypeException() {

        $caught = [];

        $testException = function($key, $type) use (&$caught) {

            $item = $this->getItemConfig();

            switch ($type) {
                case "int":
                    $item[$key] = (int)$item[$key];
                    break;
                case "string":
                    $item[$key] = (string)$item[$key];
                    break;

                default:
                    $item[$key] = $type;
                    break;
            }

            try {
                new MessageItem($this->createMessageKey(), $item);
            } catch (\TypeError $e) {
                if (in_array($e->getMessage(), $caught)) {
                    return;
                }
                $caught[] = $e->getMessage();
            }

        };

        $testException("subject", "int");
        $testException("size", "string");
        $testException("seen", "string");
        $testException("answered", "string");
        $testException("recent", "string");
        $testException("draft", "string");
        $testException("hasAttachments", "string");
        $testException("flagged", "string");
        $testException("from", "");
        $testException("to", "");
        $testException("date", "");

        $this->assertSame(11, count($caught));
    }


    /**
     * Test \BadMethodCallException for setMessageKey
     */
    public function testSetMessageKey() {

        $this->expectException(\BadMethodCallException::class);

        $messageKey = $this->createMessageKey();

        $messageItem = new MessageItem($messageKey);

        $messageKey2 = $this->createMessageKey();

        $messageItem->setMessageKey($messageKey2);
    }


    /**
     * Test toArray
     */
    public function testToArray() {
        $item = $this->getItemConfig();

        $messageKey = $this->createMessageKey();

        $messageItem = new MessageItem($messageKey, $item);

        $keys = array_keys($item);

        $this->assertSame($messageKey, $messageItem->toArray()['messageKey']);

        foreach ($keys as $key) {
            if ($key === "date" || $key === "from" || $key === "to") {
                $this->assertEquals($item[$key], $messageItem->toArray()[$key]);
            } else {
                $this->assertSame($item[$key], $messageItem->toArray()[$key]);
            }
        }
    }


// ---------------------
//    Helper Functions
// ---------------------

    /**
     * Returns an MessageItem as array.
     */
    protected function getItemConfig() {

        return [
            'from'           => $this->createFrom(),
            'to'             => $this->createTo(),
            'size'           => 23,
            'subject'        => "SUBJECT",
            'date'           => new \DateTime(),
            'seen'           => false,
            'answered'       => true,
            'draft'          => false,
            'flagged'        => true,
            'recent'         => false,
            'hasAttachments' => true
        ];

    }


    /**
     * Returns a MessageKey.
     *
     * @param string $mailFolderId
     * @param string $id
     *
     * @return MessageKey
     */
    protected function createMessageKey($mailFolderId = "INBOX", $id = "232") :MessageKey {
        return new MessageKey($mailFolderId, $id);
    }


    /**
     * Returns a MailAddress to be used with the "from" property of the MessageItem
     * to test.
     *
     * @return MailAddress
     */
    protected function createFrom() :MailAddress {
        return new MailAddress("peterParker@newyork.com", "Peter Parker");
    }

    /**
     * Returns a MailAddressList to be used with the "to" property of the MessageItem
     * @return MailAddressList
     */
    protected function createTo() : MailAddressList {

        $list = new MailAddressList;

        $list[] = new MailAddress("name1", "name1@address.testcomdomaindev");
        $list[] = new MailAddress("name2", "name2@address.testcomdomaindev");

        return $list;
    }

}