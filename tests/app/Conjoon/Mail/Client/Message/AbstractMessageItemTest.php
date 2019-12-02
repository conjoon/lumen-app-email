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
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\DraftFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Util\Jsonable,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Util\Modifiable;


class AbstractMessageItemTest extends TestCase
{



// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor() {

        $messageItem = $this->createMessageItem();
        $this->assertInstanceOf(Jsonable::class, $messageItem);
        $this->assertInstanceOf(Modifiable::class, $messageItem);

        $this->assertNull($messageItem->getSeen());
        $this->assertNull($messageItem->getFlagged());
        $this->assertNull($messageItem->getDraft());

    }


    /**
     * Test class.
     */
    public function testClass() {

        $item = $this->getItemConfig();

        $messageItem = $this->createMessageItem(null, $item);

        $this->assertSame([], $messageItem->getModifiedFields());

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
                $this->createMessageItem(null, $item);
            } catch (\TypeError $e) {
                if (in_array($e->getMessage(), $caught)) {
                    return;
                }
                $caught[] = $e->getMessage();
            }

        };

        $testException("subject", "int");
        $testException("seen", "string");
        $testException("answered", "string");
        $testException("recent", "string");
        $testException("draft", "string");
        $testException("flagged", "string");
        $testException("from", "");
        $testException("to", "");
        $testException("date", "");

        $this->assertSame(9, count($caught));
    }


    /**
     * Test toJson
     */
    public function testToJson() {
        $item = $this->getItemConfig();

        $messageItem = $this->createMessageItem(null, $item);

        $keys = array_keys($item);

        $json = $messageItem->toJson();

        foreach ($keys as $key) {
            if ($key === "from" || $key === "to") {
                $this->assertEquals($item[$key]->toJson(), $json[$key]);
            } else if ($key == "date") {
                $this->assertEquals($item[$key]->format("Y-m-d H:i:s"), $json[$key]);
            } else{
                $this->assertSame($item[$key], $json[$key]);
            }
        }

        $this->assertSame($json["id"], $messageItem->getMessageKey()->getId());
        $this->assertSame($json["mailFolderId"], $messageItem->getMessageKey()->getMailFolderId());
        $this->assertSame($json["mailAccountId"], $messageItem->getMessageKey()->getMailAccountId());


        $messageItem = $this->createMessageItem();

        $json = $messageItem->toJson();

        $this->assertSame("1970-01-01 00:00:00", $json["date"]);
        $this->assertSame([], $json["to"]);
        $this->assertSame([], $json["from"]);

    }


    /**
     * Test setFrom /w null
     */
    public function testSetFromWithNull() {

        $messageItem = $this->createMessageItem(null, ["from" => null]);

        $this->assertSame(null, $messageItem->getFrom());

    }

    /**
     * getFlagList()
     */
    public function testGetFlagList() {

        $item = $this->createMessageItem(null, $this->getItemConfig());

        $flagList = $item->getFlagList();

        $this->assertInstanceOf(FlagList::class, $flagList);

        $caught = 0;

        foreach ($flagList as $flag) {

            switch (true) {

                case ($flag instanceof DraftFlag):
                    if ($flag->getValue() === false) {
                        $caught++;
                    }
                    break;

                case ($flag instanceof SeenFlag):
                    if ($flag->getValue() === false) {
                        $caught++;
                    }
                    break;

                case ($flag instanceof FlaggedFlag):
                    if ($flag->getValue() === true) {
                        $caught++;
                    }
                    break;

            }

        }

        $this->assertSame(3, $caught);

    }


    /**
     * getFlagList()
     */
    public function testGetFlagList_empty() {

        $item = $this->createMessageItem();

        $flagList = $item->getFlagList();

        $this->assertTrue(count($flagList) === 0);
    }


    /**
     * Tests modifiable
     */
    public function testModifiable() {

        $messageKey  = $this->createMessageKey();
        $messageItem = $this->createMessageItem($messageKey);

        $conf = $this->getItemConfig();
        $mod  = [];
        $it   = 0;

        $fieldLength = count(array_keys($conf));
        $this->assertTrue($fieldLength > 0);

        $this->assertSame($mod, $messageItem->getModifiedFields());
        foreach ($conf as $field => $value) {
            $messageItem->{"set" . ucfirst($field)}($value);
            $mod[] = $field;
            $this->assertSame($mod, $messageItem->getModifiedFields());
            $it++;
        }
        $this->assertSame($fieldLength, $it);
    }


    /**
     * Tests isHeaderField
     */
    public function testIsHeaderField() {

        $fields = ["from", "to", "subject", "date"];

        foreach ($fields as $field) {
            $this->assertTrue(AbstractMessageItem::isHeaderField($field));
        }

        $fields = ["recent", "seen", "flagged", "answered"];

        foreach ($fields as $field) {
            $this->assertFalse(AbstractMessageItem::isHeaderField($field));
        }
    }


// ---------------------
//    Helper Functions
// ---------------------


    /**
     * Returns an anonymous class extending AbstractMessageItem.
     *
     * @param array|null $data
     * @parsm MessageKey $key
     *
     * @return AbstractMessageItem
     */
    protected function createMessageItem(MessageKey $key = null, array $data = null) :AbstractMessageItem {
        // Create a new instance from the Abstract Class
        if (!$key) {
            $key = $this->createMessageKey();
        }
       return new class($key, $data) extends AbstractMessageItem {};
    }


    /**
     * Returns a MessageKey
     *
     * @return MessageKey
     */
    protected function createMessageKey() :MessageKey {
        // Create a new instance from the Abstract Class
        return new MessageKey("a", "b", "c");
    }


    /**
     * Returns an MessageItem as array.
     */
    protected function getItemConfig() {

        return [
            'from'           => $this->createFrom(),
            'to'             => $this->createTo(),
            'subject'        => "SUBJECT",
            'date'           => new \DateTime(),
            'seen'           => false,
            'answered'       => true,
            'draft'          => false,
            'flagged'        => true,
            'recent'         => false
        ];

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