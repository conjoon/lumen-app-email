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
    Conjoon\Mail\Client\Data\DataException;


class MessageItemTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------
    /**
     * Test constructor exception with missing keys.
     */
    public function testConstructorException() {

        $caught = [];

        $testException = function($key) use (&$caught) {
            try {
                $item = $this->getItemConfig();
                unset($item[$key]);
                new MessageItem($item);
            } catch (DataException $e) {
                if (in_array($e->getMessage(), $caught)) {
                    return;
                }
                $caught[] = $e->getMessage();
            }

        };

        $testException("id");
        $testException("mailFolderId");
        $testException("mailAccountId");

        $this->assertSame(3, count($caught));
    }


    /**
     * Test constructor.
     */
    public function testClass() {

        $item = $this->getItemConfig();

        $messageItem = new MessageItem($item);

        $this->assertInstanceOf(MessageItem::class, $messageItem);

        //$this->assertInstanceOf(\DateTime::class, $item["date"]);

        foreach ($item as $key => $value) {

            $method = "get" . ucfirst($key);

            switch ($key) {
                case 'id':
                case 'mailFolderId':
                case 'mailAccountId':
                $this->assertIsString($messageItem->{$method}());

                break;
                case 'date':
                    $this->assertNotSame($item["date"], $messageItem->getDate());
                    $this->assertEquals($item["date"], $messageItem->getDate());
                    break;
                case 'from':
                case 'to':
                    $this->assertEquals($item[$key], $messageItem->{$method}(), "\"$key\" not equal");
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
                new MessageItem($item);
            } catch (\TypeError $e) {
                if (in_array($e->getMessage(), $caught)) {
                    return;
                }
                $caught[] = $e->getMessage();
            }

        };

        $testException("id", "int");
        $testException("mailFolderId", "int");
        $testException("mailAccountId", "int");
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

        $this->assertSame(14, count($caught));
    }


    /**
     * Test toArray
     */
    public function testToArray() {
        $item = $this->getItemConfig();

        $messageItem = new MessageItem($item);

        $keys = array_keys($item);

        foreach ($keys as $key) {
            if ($key === "date" || $key === "from" || $key === "date") {
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
            'id'             => "233",
            'mailAccountId'  => "2332",
            'mailFolderId'   => "INBOX",
            'from'           => ["name" => "name", "address" => "name@address.testcomdomaindev"],
            'to'             => [["name" => "name1", "address" => "name1@address.testcomdomaindev"],
                                 ["name" => "name2", "address" => "name2@address.testcomdomaindev"]],
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


}