<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App\Http\V0\Resource;

use App\Http\V0\Resource\MailFolderDescription;
use App\Http\V0\Resource\MessageItemDescription;
use Conjoon\Http\Resource\ResourceObjectDescription;
use Tests\TestCase;

/**
 * Class MessageItemDescriptionTest
 * @package Tests\App\Http\V0\Resource
 */
class MessageItemDescriptionTest extends TestCase
{
    /**
     * test class
     */
    public function testClass()
    {
        $inst = new MessageItemDescription();
        $this->assertInstanceOf(ResourceObjectDescription::class, $inst);
    }


    /**
     * @return string
     */
    public function testGetType(): void
    {
        $this->assertSame("MessageItem", $this->createDescription()->getType());
    }


    /**
     * Tests getRelationships()
     */
    public function testGetRelationships(): void
    {
        $list = $this->createDescription()->getRelationships();
        $this->assertSame(1, count($list));

        $this->assertInstanceOf(MailFolderDescription::class, $list[0]);
    }


    /**
     * Tests getFields()
     */
    public function testGetFields(): void
    {
        $this->assertEquals([
            "from",
            "to",
            "subject",
            "date",
            "seen",
            "answered",
            "draft",
            "flagged",
            "recent",
            "charset",
            "references",
            "messageId",
            "previewText",
            "size",
            "hasAttachments",
            "cc",
            "bcc",
            "replyTo"
        ], $this->createDescription()->getFields());
    }


    /**
     * tests getDefaultFields()
     */
    public function testGetDefaultFields(): void
    {
        $this->assertEquals([
            "from" => true,
            "to" => true,
            "subject" => true,
            "date" => true,
            "seen" => true,
            "answered" => true,
            "draft" => true,
            "flagged" => true,
            "recent" => true,
            "charset" => true,
            "references" => true,
            "messageId" => true,
            "size" => true,
            "hasAttachments" => true,
            "cc" => true,
            "bcc" => true,
            "replyTo" => true,
            "html" =>  ["length" => 200, "trimApi" => true, "precedence" => true],
            "plain" => ["length" => 200, "trimApi" => true]
        ], $this->createDescription()->getDefaultFields());
    }


    /**
     * @return MessageItemDescription
     */
    protected function createDescription(): MessageItemDescription
    {
        return new MessageItemDescription();
    }
}
