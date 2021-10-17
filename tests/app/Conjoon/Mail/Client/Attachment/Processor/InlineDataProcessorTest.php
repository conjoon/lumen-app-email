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

namespace Tests\Conjoon\Mail\Client\Atatchment\Processor;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\Processor\FileAttachmentProcessor;
use Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Tests\TestCase;

/**
 * Class ProcessorExceptionTest
 *
 */
class InlineDataProcessorTest extends TestCase
{


    /**
     * Test class
     */
    public function testInstance()
    {

        $processor = new InlineDataProcessor();

        $this->assertInstanceOf(FileAttachmentProcessor::class, $processor);

        $attachmentKey = new AttachmentKey("dev", "INBOX", "123", "1");
        $fileAttachment = new FileAttachment($attachmentKey, [
            "size" => 123,
            "content" => base64_encode("CONTENT"),
            "encoding" => "base64",
            "type" => "image/jpeg",
            "text" => "file1.jpg"
        ]);

        $item = $processor->process($fileAttachment);

        $this->assertSame($attachmentKey, $item->getAttachmentKey());

        $this->assertSame(123, $item->getSize());
        $this->assertSame("file1.jpg", $item->getText());
        $this->assertSame("image/jpeg", $item->getType());
        $this->assertSame(
            "data:application/octet-stream;base64,{$fileAttachment->getContent()}",
            $item->getDownloadUrl()
        );
        $this->assertSame(
            "data:image/jpeg;base64,{$fileAttachment->getContent()}",
            $item->getPreviewImgSrc()
        );
    }


    /**
     * Test missing previewImgSrc
     */
    public function testPreviewImgSrcMissing()
    {

        $processor = new InlineDataProcessor();

        $this->assertInstanceOf(FileAttachmentProcessor::class, $processor);

        $attachmentKey = new AttachmentKey("dev", "INBOX", "123", "1");
        $fileAttachment = new FileAttachment($attachmentKey, [
            "size" => 123,
            "content" => base64_encode("CONTENT"),
            "encoding" => "base64",
            "type" => "text/html",
            "text" => "file1.jpg"
        ]);

        $item = $processor->process($fileAttachment);

        $this->assertSame("", $item->getPreviewImgSrc());
    }
}
