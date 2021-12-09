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

namespace Tests\Conjoon\Mail\Client\Attachment;

use Conjoon\Mail\Client\Attachment\AbstractAttachment;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Util\Jsonable;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class AbstractAttachmentTest
 * @package Tests\Conjoon\Mail\Client\Attachment
 */
class AbstractAttachmentTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass()
    {

        $type = "image/jpg";
        $text = "Text";
        $size = 123;

        $attachment = $this->createAbstractAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            [
                "type" => $type,
                "text" => $text,
                "size" => $size
            ]
        );

        $this->assertInstanceOf(Jsonable::class, $attachment);

        $this->assertSame($type, $attachment->getType());
        $this->assertSame($text, $attachment->getText());
        $this->assertSame($size, $attachment->getSize());
    }


    /**
     * Tests constructor with exception for missing type
     */
    public function testConstructorExceptionType()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("type");

        $this->createAbstractAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            [
                "text" => "2",
                "size" => 3
            ]
        );
    }


    /**
     * Tests constructor with exception for missing text
     */
    public function testConstructorExceptionText()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("text");

        $this->createAbstractAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            [
                "type" => "1",
                "size" => 3
            ]
        );
    }


    /**
     * Tests constructor with exception for missing size
     */
    public function testConstructorExceptionSize()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("size");

        $this->createAbstractAttachment(
            new AttachmentKey("dev", "INBOX", "123", "1"),
            [
                "type" => "1",
                "text" => "2"
            ]
        );
    }



// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @param $key
     * @param $data
     * @return AbstractAttachment
     */
    protected function createAbstractAttachment($key, $data): AbstractAttachment
    {
        // Create a new instance from the Abstract Class
        return new class ($key, $data) extends AbstractAttachment {

            public function toJson() : array
            {
                return [];
            }
        };
    }
}
