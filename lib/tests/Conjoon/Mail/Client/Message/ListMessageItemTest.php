<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Mail\Client\Message;

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\Message\DraftTrait;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessagePart;
use Tests\TestCase;

/**
 * Class ListMessageItemTest
 * @package Tests\Conjoon\Mail\Client\Message
 */
class ListMessageItemTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor()
    {
        $uses = class_uses(ListMessageItem::class);
        $this->assertContains(DraftTrait::class, $uses);

        $messageKey = $this->createMessageKey();
        $messageItem = new ListMessageItem($messageKey, null, $this->createMessagePart());
        $this->assertInstanceOf(MessageItem::class, $messageItem);
    }


    /**
     * Test class.
     */
    public function testClass()
    {

        $previewText = "foobar";

        $messageKey = $this->createMessageKey();

        $messageItem = new ListMessageItem(
            $messageKey,
            ["subject" => "YO!"],
            $this->createMessagePart($previewText, "UTF-8", "text/plain")
        );

        $this->assertNull($messageItem->getDraft());
        $messageItem->setDraft(true);

        $this->assertSame($previewText, $messageItem->getMessagePart()->getContents());

        $mp = $messageItem->getMessagePart();

        $mp->setContents("snafu", "UTF-8");
        $this->assertSame("snafu", $messageItem->getMessagePart()->getContents());

        $this->assertSame($messageItem, $messageItem->setMessagePart(null));
        $this->assertNull($messageItem->getMessagePart());
        $this->assertSame($messageItem, $messageItem->setMessagePart($mp));
        $this->assertSame($mp, $messageItem->getMessagePart());

        $cc = $this->createAddresses(1);
        $messageItem->setCc($cc);
        $bcc = $this->createAddresses(2);
        $messageItem->setBcc($bcc);
        $replyTo = $this->createAddress(3);
        $messageItem->setReplyTo($replyTo);

        $arr = $messageItem->toJson();
        $this->assertSame("YO!", $arr["subject"]);
        $this->assertTrue($arr["draft"]);

        $this->assertEquals($cc->toJson(), $arr["cc"]);
        $this->assertEquals($bcc->toJson(), $arr["bcc"]);
        $this->assertEquals($replyTo->toJson(), $arr["replyTo"]);

        $this->assertSame("snafu", $arr["previewText"]);
    }



// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @return MailAddressList
     */
    protected function createAddresses($id): MailAddressList
    {

        $list = new MailAddressList();

        $list[] = $this->createAddress($id);

        return $list;
    }


    /**
     * @return MailAddress
     */
    protected function createAddress($id): MailAddress
    {
        return new MailAddress("name{$id}", "name{$id}@address.testcomdomaindev");
    }


    /**
     * Returns a MessageKey.
     *
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $id
     *
     * @return MessageKey
     */
    protected function createMessageKey(
        string $mailAccountId = "dev",
        string $mailFolderId = "INBOX",
        string $id = "232"
    ): MessageKey {
        return new MessageKey($mailAccountId, $mailFolderId, $id);
    }


    protected function createMessagePart($text = "a", $charset = "b", $mimeType = "c"): MessagePart
    {
        return new MessagePart($text, $charset, $mimeType);
    }
}
