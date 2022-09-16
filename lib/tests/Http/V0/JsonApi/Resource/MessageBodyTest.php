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

namespace Tests\App\Http\V0\JsonApi\Resource;

use App\Http\V0\JsonApi\Resource\MailFolder;
use App\Http\V0\JsonApi\Resource\MessageBody;
use App\Http\V0\JsonApi\Resource\MessageItem;
use Conjoon\Mail\Client\Data\Resource\MessageBody as BaseMessageBody;
use Tests\TestCase;

/**
 * Tests MessageItem.
 */
class MessageBodyTest extends TestCase
{
    /**
     * test class
     */
    public function testClass()
    {
        $inst = new MessageBody();
        $this->assertInstanceOf(BaseMessageBody::class, $inst);
    }


    /**
     * @return void
     */
    public function testGetType(): void
    {
        $this->assertSame("MessageBody", $this->createDescription()->getType());
    }


    /**
     * Tests getRelationships()
     */
    public function testGetRelationships(): void
    {
        $list = $this->createDescription()->getRelationships();
        $this->assertSame(2, count($list));

        $this->assertInstanceOf(MailFolder::class, $list[0]);
        $this->assertInstanceOf(MessageItem::class, $list[1]);

        $this->assertSame(
            ["MessageBody", "MessageBody.MailFolder", "MessageBody.MailFolder.MailAccount", "MessageBody.MessageItem"],
            $this->createDescription()->getAllRelationshipPaths(true)
        );

        $this->assertEqualsCanonicalizing(
            ["MessageItem", "MailFolder", "MessageBody", "MailAccount"],
            $this->createDescription()->getAllRelationshipTypes(true)
        );
    }


    /**
     * Tests getFields()
     */
    public function testGetFields(): void
    {
        $this->assertEqualsCanonicalizing([
            "textPlain",
            "textHtml"
        ], $this->createDescription()->getFields());
    }


    /**
     * tests getDefaultFields()
     */
    public function testGetDefaultFields(): void
    {
        $this->assertEquals([
            "textPlain",
            "textHtml"
        ], $this->createDescription()->getDefaultFields());
    }


    /**
     * @return MessageBody
     */
    protected function createDescription(): MessageBody
    {
        return new MessageBody();
    }
}
