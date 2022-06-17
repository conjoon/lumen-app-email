<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App\Http\V0\Query\MessageItem;

use App\Http\V0\Query\MessageItem\AbstractMessageItemQueryTranslator;
use App\Http\V0\Query\MessageItem\IndexRequestQueryTranslator;
use Conjoon\Http\Query\InvalidParameterResourceException;
use Conjoon\Http\Query\QueryTranslator;
use ReflectionClass;
use Tests\TestCase;

/**
 * Class AbstractMessageItemQueryTranslatorTest
 * @package Tests\App\Http\V0\Query\MessageItem
 */
class AbstractMessageItemQueryTranslatorTest extends TestCase
{
    /**
     *
     */
    public function testClass()
    {
        $inst = $this->getMockForAbstractClass(AbstractMessageItemQueryTranslator::class);

        $this->assertInstanceOf(QueryTranslator::class, $inst);
    }


    /**
     * Extract parameters not Request
     * @throws ReflectionException
     */
    public function testExtractParametersException()
    {
        $this->expectException(InvalidParameterResourceException::class);

        $translator = $this->getMockForAbstractClass(AbstractMessageItemQueryTranslator::class);
        $reflection = new ReflectionClass($translator);

        $extractParametersReflection = $reflection->getMethod("extractParameters");
        $extractParametersReflection->setAccessible(true);

        $extractParametersReflection->invokeArgs($translator, ["foo"]);
    }


    /**
     * getDefaultFields()
     * @throws ReflectionException
     */
    public function testGetDefaultFields()
    {
        $translator = $this->getMockForAbstractClass(AbstractMessageItemQueryTranslator::class);
        $reflection = new ReflectionClass($translator);

        $getDefaultFieldsReflection = $reflection->getMethod("getDefaultFields");
        $getDefaultFieldsReflection->setAccessible(true);

        foreach (["MessageItem", "MailFolder"] as $type) {
            $attr = $getDefaultFieldsReflection->invokeArgs($translator, [$type]);
            $this->assertEquals($this->getDefaultFields($type), $attr);
        }
    }


    protected function getDefaultFields($type = null): array
    {
        $fieldsets = [
            "MessageItem" => [
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
            ],
            "MailFolder" => [
                "name" => true,
                "folderType" => true,
                "unreadMessages" => true,
                "totalMessages" => true,
                "data" => true
            ]
        ];

        return !$type ? $fieldsets : $fieldsets[$type];
    }
}
