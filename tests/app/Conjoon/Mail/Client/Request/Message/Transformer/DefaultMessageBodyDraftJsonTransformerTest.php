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

use Conjoon\Mail\Client\Request\JsonTransformer,
    Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer,
    Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Request\JsonTransformerException,
    Conjoon\Mail\Client\Message\MessageBodyDraft;


class DefaultMessageBodyDraftJsonTransformerTest extends TestCase {


    public function testClass() {

        $writer = new DefaultMessageBodyDraftJsonTransformer();
        $this->assertInstanceOf(MessageBodyDraftJsonTransformer::class, $writer);
        $this->assertInstanceOf(JsonTransformer::class, $writer);
    }


    public function testTransform_exception() {

        $this->expectException(JsonTransformerException::class);
        $writer = new DefaultMessageBodyDraftJsonTransformer();
        $writer->transform([]);
    }


    public function testTransform() {

        $writer = new DefaultMessageBodyDraftJsonTransformer();

        $data = [
            "mailAccountId" => "a",
            "mailFolderId"  => "b",
            "id"            => "c",
            "textHtml"      => "foo",
            "textPlain"     => "bar"
        ];

        $draft = $writer->transform($data);

        $this->assertInstanceOf(MessageBodyDraft::class, $draft);

        $this->assertSame($data["mailAccountId"], $draft->getMessageKey()->getMailAccountId());
        $this->assertSame($data["mailFolderId"], $draft->getMessageKey()->getMailFolderId());
        $this->assertSame($data["id"], $draft->getMessageKey()->getId());
        $this->assertSame($data["textHtml"], $draft->getTextHtml()->getContents());
        $this->assertSame($data["textPlain"], $draft->getTextPlain()->getContents());

        $this->assertSame("UTF-8", $draft->getTextPlain()->getCharset());
        $this->assertSame("UTF-8", $draft->getTextHtml()->getCharset());

        $this->assertSame("text/plain", $draft->getTextPlain()->getMimeType());
        $this->assertSame("text/html", $draft->getTextHtml()->getMimeType());

    }


    public function testTransform_allDataMissing() {

        $writer = new DefaultMessageBodyDraftJsonTransformer();

        $data = [
            "mailAccountId" => "a",
            "mailFolderId"  => "b",
            "id"            => "c"
        ];

        $draft = $writer->transform($data);

        $this->assertInstanceOf(MessageBodyDraft::class, $draft);

        $this->assertNull($draft->getTextHtml());
        $this->assertNull($draft->getTextPlain());
    }

}