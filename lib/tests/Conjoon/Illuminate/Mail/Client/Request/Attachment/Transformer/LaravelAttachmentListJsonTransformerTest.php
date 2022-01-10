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

namespace Tests\Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer;

use Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer\LaravelAttachmentListJsonTransformer;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use Conjoon\Util\JsonDecodable;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

/**
 * Class LaravelAttachmentListJsonTransformerTest
 * @package Tests\Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer
 */
class LaravelAttachmentListJsonTransformerTest extends TestCase
{


    /**
     * Test inheritance
     */
    public function testClass()
    {

        $transformer = new LaravelAttachmentListJsonTransformer();
        $this->assertInstanceOf(AttachmentListJsonTransformer::class, $transformer);
        $this->assertInstanceOf(JsonDecodable::class, $transformer);
    }


    /**
     * Test fromArray
     */
    public function testFromArray()
    {
        $transformer = new LaravelAttachmentListJsonTransformer();

        $testFiles = [
            __DIR__ . "/Fixtures/testFile.txt",
            __DIR__ . "/Fixtures/image.png"
        ];

        $files = [[
            "text" => "file1",
            "type" => "client/type",
            "blob" => new UploadedFile($testFiles[0], "clientFilename", null, null, true)
        ], [
            "text" => "file2",
            "type" => "client/type",
            "blob" => new UploadedFile($testFiles[1], "clientFilename", null, null, true)
        ]];

        $result = [[
            "text" => $files[0]["text"],
            "type" => "text/plain",
            "encoding" => "base64",
            "size" => mb_strlen(base64_encode(file_get_contents($testFiles[0])), "8bit"),
            "content" => base64_encode(file_get_contents($testFiles[0]))
        ], [
            "text" => $files[1]["text"],
            "type" => "image/png",
            "encoding" => "base64",
            "size" => mb_strlen(base64_encode(file_get_contents($testFiles[1])), "8bit"),
            "content" => base64_encode(file_get_contents($testFiles[1]))
        ]];

        $fileList = $transformer::fromArray($files);

        $this->assertSame(count($fileList), 2);

        $i = 0;
        foreach ($fileList as $file) {
            $this->assertSame($result[$i]["size"], $file->getSize());
            $this->assertSame($result[$i]["type"], $file->getType());
            $this->assertSame($result[$i]["text"], $file->getText());
            $this->assertSame($result[$i]["encoding"], $file->getEncoding());
            $this->assertSame($result[$i]["content"], $file->getContent());
            $i++;
        }

        $this->assertInstanceOf(FileAttachmentList::class, $fileList);
    }


    /**
     * Test fromString
     */
    public function testFromString()
    {
        $this->expectException(RuntimeException::class);

        $transformer = new LaravelAttachmentListJsonTransformer();

        $transformer::fromString(json_encode([]));
    }
}
