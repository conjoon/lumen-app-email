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
use App\Http\V0\Resource\MessageItemDescription;
use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\JsonApiQueryTranslator;
use ReflectionClass;
use Tests\TestCase;

/**
 * Class AbstractMessageItemQueryTranslatorTest
 * @package Tests\App\Http\V0\Query\MessageItem
 */
class AbstractMessageItemQueryTranslatorTest extends TestCase
{
    /**
     * tests the class
     */
    public function testClass()
    {
        $inst = $this->getMockForAbstractClass(AbstractMessageItemQueryTranslator::class);

        $this->assertInstanceOf(JsonApiQueryTranslator::class, $inst);
    }


    /**
     * Tests getResourceTarget
     * @return void
     */
    public function testGetResourceTarget()
    {
        $this->assertInstanceOf(
            MessageItemDescription::class,
            $this->createTranslatorMock()->getResourceTarget()
        );
    }


    /**
     * tests parseFields() with ["previewText,subject"]
     */
    public function testParseFieldsWithPreviewText()
    {
        $type = "entity";
        $bag = new ParameterBag();
        $bag->{"fields[entity]"} = "previewText,subject";

        $translator = $this->createTranslatorMock(["getFields", "hasOnlyAllowedFields"]);
        $reflection = new ReflectionClass($translator);

        $parseFieldsReflection = $reflection->getMethod("parseFields");
        $parseFieldsReflection->setAccessible(true);

        $this->assertEquals(
            ["subject", "html", "plain"],
            $parseFieldsReflection->invokeArgs($translator, [$bag->{"fields[entity]"}, $type])
        );
    }


    /**
     * tests mapConfigToFields() with [[], ["subject" => ["length" => 200]], "entity"]
     * @return void
     */
    public function testMapConfigToFieldsWithEmptyFields()
    {
        $type = "entity";

        $options       = ["subject" => ["length" => 200]];
        $defaultFields = ["subject" => true, "date" => true];

        $translator = $this->createTranslatorMock(["getDefaultFields"]);

        $reflection = new ReflectionClass($translator);
        $mapConfigToFields = $reflection->getMethod("mapConfigToFields");
        $mapConfigToFields->setAccessible(true);

        $translator->expects($this->once())->method("getDefaultFields")->with($type)->willReturn($defaultFields);

        $this->assertEquals([], $mapConfigToFields->invokeArgs($translator, [[], $options, $type]));
    }


    /**
     * tests mapConfigToFields() with [["subject", "date"], ["subject" => ["length" => 200]], "entity"]
     * @return void
     */
    public function testMapConfigToFieldsWithOptions()
    {
        $type = "entity";

        $options       = ["subject" => ["length" => 200]];
        $defaultFields = ["subject" => true, "date" => true];

        $translator = $this->createTranslatorMock(["getDefaultFields"]);

        $reflection = new ReflectionClass($translator);
        $mapConfigToFields = $reflection->getMethod("mapConfigToFields");
        $mapConfigToFields->setAccessible(true);

        $translator->expects($this->once())->method("getDefaultFields")->with($type)->willReturn($defaultFields);

        $this->assertEquals([
            "subject" => ["length" => 200],
            "date" => true
        ], $mapConfigToFields->invokeArgs($translator, [["subject", "date"], $options, $type]));
    }


    /**
     * tests mapConfigToFields() with [["previewText", "date"], ["previewText" => ["length" => 200]], "MessageItem"]
     * @return void
     */
    public function testMapConfigToFieldsWithMessageItem()
    {
        $type = "MessageItem";

        $options       = ["previewText" => ["length" => 200]];
        $defaultFields = ["previewText" => true, "date" => true];

        $translator = $this->createTranslatorMock(["getDefaultFields"]);

        $reflection = new ReflectionClass($translator);
        $mapConfigToFields = $reflection->getMethod("mapConfigToFields");
        $mapConfigToFields->setAccessible(true);

        $translator->expects($this->once())->method("getDefaultFields")->with($type)->willReturn($defaultFields);

        $this->assertEquals([
            "html"  => ["length" => 200],
            "plain" => ["length" => 200],
            "date" => true
        ], $mapConfigToFields->invokeArgs($translator, [["previewText", "date"], $options, $type]));
    }


    /**
     * tests mapConfigToFields() with [["previewText", "date"], ["previewText" => [
     *         "plain" => ["length" => 200, "trimApi" => true, "precedence" => false],
     *         "html" => ["length" => 200, "trimApi" => false, "precedence" => true]
     *       ]], "MessageItem"]
     * @return void
     */
    public function testMapConfigToFieldsWithMessageItemAndDetailedPreviewTextOptions()
    {
        $type = "MessageItem";

        $options = [
            "previewText" => [
                "plain" => ["length" => 200, "trimApi" => true, "precedence" => false],
                "html" => ["length" => 200, "trimApi" => false, "precedence" => true]
            ]];
        $defaultFields = ["previewText" => true, "date" => true];

        $translator = $this->createTranslatorMock(["getDefaultFields"]);

        $reflection = new ReflectionClass($translator);
        $mapConfigToFields = $reflection->getMethod("mapConfigToFields");
        $mapConfigToFields->setAccessible(true);

        $translator->expects($this->once())->method("getDefaultFields")->with($type)->willReturn($defaultFields);

        $this->assertEquals([
            "html"  => ["length" => 200, "trimApi" => false, "precedence" => true],
            "plain" => ["length" => 200, "trimApi" => true, "precedence" => false],
            "date" => true
        ], $mapConfigToFields->invokeArgs($translator, [["previewText", "date"], $options, $type]));
    }


    /**
     * @return AbstractMessageItemQueryTranslator
     */
    protected function createTranslatorMock($methods = []): AbstractMessageItemQueryTranslator
    {
        return $this->getMockForAbstractClass(
            AbstractMessageItemQueryTranslator::class,
            [],
            '',
            true,
            true,
            true,
            $methods
        );
    }
}
