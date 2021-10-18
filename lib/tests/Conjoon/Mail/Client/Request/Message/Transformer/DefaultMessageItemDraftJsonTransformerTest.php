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

declare(strict_types=1);

namespace Tests\Conjoon\Mail\Client\Request\Message\Transformer;

use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\Util\JsonDecodable;
use Conjoon\Util\JsonDecodeException;
use DateTime;
use Exception;
use Tests\TestCase;

/**
 * Class DefaultMessageItemDraftJsonTransformerTest
 * @package Tests\Conjoon\Mail\Client\Request\Message\Transformer
 */
class DefaultMessageItemDraftJsonTransformerTest extends TestCase
{


    /**
     * Test type
     */
    public function testClass()
    {

        $writer = new DefaultMessageItemDraftJsonTransformer();
        $this->assertInstanceOf(MessageItemDraftJsonTransformer::class, $writer);
        $this->assertInstanceOf(JsonDecodable::class, $writer);
    }


    /**
     * Test fromArray() with Exception
     * @throws Exception
     */
    public function testFromArrayException()
    {

        $this->expectException(JsonDecodeException::class);
        $writer = new DefaultMessageItemDraftJsonTransformer();
        $writer->fromArray([]);
    }


    /**
     * @throws Exception
     */
    public function testFromArray()
    {

        $writer = new DefaultMessageItemDraftJsonTransformer();

        $data = [
            "mailAccountId" => "a",
            "mailFolderId" => "b",
            "id" => "c",
            "seen" => true,
            "flagged" => true,
            "from" => json_encode($this->createFrom()->toJson()),
            "replyTo" => json_encode($this->createReplyTo()->toJson()),
            "to" => json_encode($this->createTo()->toJson()),
            "cc" => json_encode($this->createCc()->toJson()),
            "bcc" => json_encode($this->createBcc()->toJson()),
            "subject" => "Mail Subject",
            "date" => "12332489"
        ];

        $draft = $writer::fromArray($data);

        $this->assertInstanceOf(MessageItemDraft::class, $draft);

        $this->assertSame($data["mailAccountId"], $draft->getMessageKey()->getMailAccountId());
        $this->assertSame($data["mailFolderId"], $draft->getMessageKey()->getMailFolderId());
        $this->assertSame($data["id"], $draft->getMessageKey()->getId());
        $this->assertSame($data["subject"], $draft->getSubject());
        $this->assertSame($data["flagged"], $draft->getFlagged());
        $this->assertSame($data["seen"], $draft->getSeen());
        $this->assertSame($data["from"], json_encode($draft->getFrom()->toJson()));
        $this->assertSame($data["replyTo"], json_encode($draft->getReplyTo()->toJson()));
        $this->assertSame($data["to"], json_encode($draft->getTo()->toJson()));
        $this->assertSame($data["cc"], json_encode($draft->getCc()->toJson()));
        $this->assertSame($data["bcc"], json_encode($draft->getBcc()->toJson()));
        $this->assertSame($data["date"], "" . $draft->getDate()->getTimestamp());
    }


    /**
     * @throws Exception
     */
    public function testFromArrayAllDataMissing()
    {

        $writer = new DefaultMessageItemDraftJsonTransformer();

        $data = [
            "mailAccountId" => "a",
            "mailFolderId" => "b",
            "id" => "c",
            "seen" => null,
            "flagged" => null,
            "from" => null,
            "replyTo" => null,
            "to" => null,
            "cc" => null,
            "bcc" => null,
            "subject" => null,
            "date" => "" . (new DateTime())->getTimestamp()
        ];

        $draft = $writer::fromArray([
            "mailAccountId" => "a",
            "mailFolderId" => "b",
            "id" => "c"
        ]);

        $this->assertInstanceOf(MessageItemDraft::class, $draft);

        $this->assertSame($data["mailAccountId"], $draft->getMessageKey()->getMailAccountId());
        $this->assertSame($data["mailFolderId"], $draft->getMessageKey()->getMailFolderId());
        $this->assertSame($data["id"], $draft->getMessageKey()->getId());
        $this->assertSame($data["subject"], $draft->getSubject());
        $this->assertSame($data["flagged"], $draft->getFlagged());
        $this->assertSame($data["seen"], $draft->getSeen());
        $this->assertSame($data["from"], $draft->getFrom());
        $this->assertSame($data["replyTo"], $draft->getReplyTo());
        $this->assertSame($data["to"], $draft->getTo());
        $this->assertSame($data["cc"], $draft->getCc());
        $this->assertSame($data["bcc"], $draft->getBcc());
        $this->assertSame($data["date"], "" . $draft->getDate()->getTimestamp());
    }


    /**
     * @throws Exception
     */
    public function testFromArrayNullAddress()
    {

        $writer = new DefaultMessageItemDraftJsonTransformer();

        $data = [
            "mailAccountId" => "a",
            "mailFolderId" => "b",
            "id" => "c",
            "from" => null,
            "replyTo" => null,
            "to" => null,
            "cc" => null,
            "bcc" => null
        ];

        $draft = $writer::fromArray([
            "mailAccountId" => "a",
            "mailFolderId" => "b",
            "id" => "c",
            "from" => "[]",
            "replyTo" => "[]",
            "to" => "[]",
            "cc" => "[]",
            "bcc" => "[]"
        ]);

        $this->assertInstanceOf(MessageItemDraft::class, $draft);

        $this->assertSame($data["mailAccountId"], $draft->getMessageKey()->getMailAccountId());
        $this->assertSame($data["mailFolderId"], $draft->getMessageKey()->getMailFolderId());
        $this->assertSame($data["id"], $draft->getMessageKey()->getId());
        $this->assertSame($data["from"], $draft->getFrom());
        $this->assertSame($data["replyTo"], $draft->getReplyTo());
        $this->assertSame($data["to"], $draft->getTo());
        $this->assertSame($data["cc"], $draft->getCc());
        $this->assertSame($data["bcc"], $draft->getBcc());
    }


// +--------------------------------------
// | Helper
// +--------------------------------------

    /**
     * @return MailAddress
     */
    protected function createFrom(): MailAddress
    {
        return new MailAddress("from@demo.org", "From From");
    }


    /**
     * @return MailAddress
     */
    protected function createReplyTo(): MailAddress
    {
        return new MailAddress("replyto@demo.org", "ReplyTo ReplyTo");
    }


    /**
     * @return MailAddressList
     */
    protected function createTo(): MailAddressList
    {
        $list = new MailAddressList();
        $list[] = new MailAddress("to1@demo.org", "To1 To1");
        $list[] = new MailAddress("to2@demo.org", "To2 To2");

        return $list;
    }


    /**
     * @return MailAddressList
     */
    protected function createCc(): MailAddressList
    {
        $list = new MailAddressList();
        $list[] = new MailAddress("cc1@demo.org", "CC1 CC1");
        $list[] = new MailAddress("cc2@demo.org", "CC2 CC2");

        return $list;
    }


    /**
     * @return MailAddressList
     */
    protected function createBcc(): MailAddressList
    {
        $list = new MailAddressList();
        $list[] = new MailAddress("bcc1@demo.org", "BCC1 BCC1");
        $list[] = new MailAddress("bcc2@demo.org", "BCC2 BCC2");

        return $list;
    }
}
