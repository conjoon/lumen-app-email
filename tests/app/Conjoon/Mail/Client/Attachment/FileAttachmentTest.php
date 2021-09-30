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

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey,
    Conjoon\Util\Jsonable,
    Conjoon\Mail\Client\Attachment\FileAttachment,
    Conjoon\Mail\Client\Attachment\AbstractAttachment;


class FilettachmentTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass() {

        $type     = "image/jpg";
        $text     = "Text";
        $size     = 123;
        $content  = "CONTENT";
        $encoding = "base64";

        $attachment = new FileAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            ["type" => $type,
            "text" => $text,
            "size" => $size,
            "content" => $content,
            "encoding" => $encoding]
        );

        $this->assertInstanceOf(AbstractAttachment::class, $attachment);

        $this->assertSame($content,  $attachment->getContent());
        $this->assertSame($encoding, $attachment->getEncoding());
    }


    /**
     * Tests constructor with exception for missing content
     */
    public function testConstructor_exceptionContent() {

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("content");

        new FileAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            ["type" => "1",
             "text" => "2",
             "size" => 3,
             //"content" => "CONTENT",
             "encoding" => "base64"]
        );
    }


    /**
     * Tests constructor with exception for missing encoding
     */
    public function testConstructor_exceptionEncoding() {

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("encoding");

        new FileAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            ["type" => "1",
                "text" => "2",
                "size" => 3,
                "content" => "CONTENT",
                //"encoding" => "base64"
            ]
        );
    }

}
