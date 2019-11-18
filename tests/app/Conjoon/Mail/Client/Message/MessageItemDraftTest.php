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

use Conjoon\Mail\Client\Message\AbstractMessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\MailClientException;


class MessageItemDraftTest extends TestCase
{



// ---------------------
//    Tests
// ---------------------

    /**
     * Tests instance
     */
    public function testConstructor() {

        $messageItem = $this->createMessageItem();
        $this->assertInstanceOf(AbstractMessageItem::class, $messageItem);


    }

    /**
     * Tests setMessageKey
     */
    public function testSetMessageKey() {

        $messageKey = $this->createMessageKey();
        $messageItem = $this->createMessageItem();

        $messageItem->setMessageKey($messageKey);
        $this->assertSame($messageKey, $messageItem->getMessageKey());

        $exc = null;
        try {
            $messageItem->setMessageKey($messageKey);
        } catch (MailClientException $e) {
            $exc = $e;
        }

        $this->assertSame("\"messageKey\" was already set.", $exc->getMessage());

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
                $this->createMessageItem($item);
            } catch (\TypeError $e) {
                if (in_array($e->getMessage(), $caught)) {
                    return;
                }
                $caught[] = $e->getMessage();
            }

        };

        $testException("draft", "string");

        $this->assertSame(1, count($caught));
    }


    /**
     * Test toJson
     */
    public function testToJson() {
        $item = $this->getItemConfig();

        $messageItem = $this->createMessageItem($item);

        $messageKey = $this->createMessageKey();
        $messageItem->setMessageKey($messageKey);
        $keys = array_keys($item);


        foreach ($keys as $key) {
            if ($key === "replyTo" || $key === "cc"  || $key === "bcc") {
                $this->assertEquals($item[$key]->toJson(), $messageItem->toJson()[$key]);
            } else{
                $this->assertSame($item[$key], $messageItem->toJson()[$key]);
            }
        }


        $messageItem = $this->createMessageItem($item);
        $messageKey = $this->createMessageKey();
        $messageItem->setMessageKey($messageKey);

        $json    = $messageItem->toJson();
        $keyJson = $messageKey->toJson();

        $this->assertSame($json["id"], $keyJson["id"]);
        $this->assertSame($json["mailAccountId"], $keyJson["mailAccountId"]);
        $this->assertSame($json["mailFolderId"], $keyJson["mailFolderId"]);

        $messageItem = $this->createMessageItem();
        $json        = $messageItem->toJson();

        $this->assertSame([], $json["replyTo"]);
        $this->assertSame([], $json["cc"]);
        $this->assertSame([], $json["bcc"]);

    }

// ---------------------
//    Helper Functions
// ---------------------

    /**
     * Returns an MessageItem as array.
     */
    protected function getItemConfig() {

        return [
            'cc'      => $this->createCc(),
            'bcc'     => $this->createBcc(),
            'replyTo' => $this->createReplyTo(),
            'draft'   => true
        ];

    }


    /**
     * Returns a MessageItemDraft.
     *
     * @param array|null $data
     * @return AbstractMessageItem
     */
    protected function createMessageItem(array $data = null) :MessageItemDraft {
        // Create a new instance from the Abstract Class
       return new MessageItemDraft($data);
    }


    /**
     * Returns a MessageKey.
     *
     * @param string $mailFolderId
     * @param string $id
     *
     * @return MessageKey
     */
    protected function createMessageKey($mailAccountId = "dev", $mailFolderId = "INBOX", $id = "232") :MessageKey {
        return new MessageKey($mailAccountId, $mailFolderId, $id);
    }

    /**
     * Returns a MailAddress to be used with the "replyTo" property of the MessageItem
     * to test.
     *
     * @return MailAddress
     */
    protected function createReplyTo() :MailAddress {
        return new MailAddress("peterParker@newyork.com", "Peter Parker");
    }

    /**
     * Returns a MailAddressList to be used with the "cc" property of the MessageItem
     * @return MailAddressList
     */
    protected function createCc() : MailAddressList {

        $list = new MailAddressList;

        $list[] = new MailAddress("name1", "name1@address.testcomdomaindev");
        $list[] = new MailAddress("name2", "name2@address.testcomdomaindev");

        return $list;
    }

    /**
     * Returns a MailAddressList to be used with the "bcc" property of the MessageItem
     * @return MailAddressList
     */
    protected function createBcc() : MailAddressList {

        $list = new MailAddressList;

        $list[] = new MailAddress("name1", "name1@address.testcomdomaindev");
        $list[] = new MailAddress("name2", "name2@address.testcomdomaindev");

        return $list;
    }

}