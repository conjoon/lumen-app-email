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


class MessageItemDraftTest extends TestCase {



// ---------------------
//    Tests
// ---------------------

    /**
     * Tests instance
     */
    public function testConstructor() {

        $messageItem = $this->createMessageItem();
        $this->assertInstanceOf(AbstractMessageItem::class, $messageItem);

        $this->assertTrue($messageItem->getDraft());

    }

    /**
     * Tests setMessageKey
     */
    public function testSetMessageKey() {

        $messageKey = $this->createMessageKey("a", "b", "c");
        $messageItem = $this->createMessageItem(null, $this->getItemConfig());

        $newItem = $messageItem->setMessageKey($messageKey);
        $this->assertSame($messageKey, $newItem->getMessageKey());
        $this->assertNotSame($messageKey, $messageItem->getMessageKey());
        $this->assertNotSame($newItem, $messageItem);

        $newJson = $newItem->toJson();
        $oldJson = $messageItem->toJson();

        $expCaught = count(array_keys($oldJson)) - 3; // w/o messageKey data

        $caught = 0;

        foreach ($oldJson as $key => $value) {

            $getter = "get" . ucfirst($key);

            if (in_array($key, ["id", "mailAccountId", "mailFolderId"])) {
                continue;
            }

            if (in_array($key, ["from", "replyTo", "to", "cc", "bcc"])) {
                $this->assertNotSame($newItem->{$getter}(), $messageItem->{$getter}());
                $this->assertEquals($newItem->{$getter}(), $messageItem->{$getter}());
                $caught++;
            } else {
                $this->assertSame($newItem->{$getter}(), $messageItem->{$getter}());
                $caught++;
            }

        }

        $this->assertTrue($expCaught > 0);
        $this->assertSame($caught, $expCaught);

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

        $messageKey = $this->createMessageKey();

        $messageItem = $this->createMessageItem($messageKey, $item);

        $keys = array_keys($item);

        foreach ($keys as $key) {
            if (in_array($key, ["from", "replyTo", "to", "cc", "bcc"])) {
                $this->assertEquals($item[$key]->toJson(), $messageItem->toJson()[$key]);
            } else{
                $this->assertSame($item[$key], $messageItem->toJson()[$key]);
            }
        }


        $messageKey = $this->createMessageKey();
        $messageItem = $this->createMessageItem($messageKey, $item);


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
            'from'    => $this->createFrom(),
            'replyTo' => $this->createReplyTo(),
            'to'      => $this->createTo(),
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
    protected function createMessageItem(MessageKey $key = null, array $data = null) :MessageItemDraft {
        if (!$key) {
            $key = $this->createMessageKey();
        }

       return new MessageItemDraft($key, $data);
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
     * Returns a MailAddress to be used with the "from" property of the MessageItem
     * to test.
     *
     * @return MailAddress
     */
    protected function createFrom() :MailAddress {
        return new MailAddress("from@from.com", "From From");
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
     * Returns a MailAddressList to be used with the "to" property of the MessageItem
     * @return MailAddressList
     */
    protected function createTo() : MailAddressList {

        $list = new MailAddressList;

        $list[] = new MailAddress("to1", "to1@address.testcomdomaindev");
        $list[] = new MailAddress("to2", "to2@address.testcomdomaindev");

        return $list;
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