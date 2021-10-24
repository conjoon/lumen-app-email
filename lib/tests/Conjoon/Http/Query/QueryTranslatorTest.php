<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Http\Query;

use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\InvalidQueryParameterException;
use Conjoon\Http\Query\QueryTranslator;
use Conjoon\Core\ResourceQuery;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Class ResourceQueryTest
 * @package Tests\Conjoon\Http\Query
 */
class QueryTranslatorTest extends TestCase
{

    /**
     * Class functionality
     */
    public function testClass()
    {
        $translator        = $this->getQueryTranslator();
        $parameterResource = $this->getParameterResource();
        $paramBag          = new ParameterBag($parameterResource->getParameters());
        $resourceQuery     = $this->getResourceQuery($paramBag);

        $translator->expects($this->once())
                   ->method("extractParameters")
                   ->with($parameterResource)
                   ->willReturn($parameterResource->getParameters());

        $translator->expects($this->once())
                    ->method("getExpectedParameters")
                    ->willReturn(["foo", "bar"]);

        $translator->expects($this->once())
                    ->method("translateParameters")
                    ->with(
                        $this->callback(
                            function ($bag) use ($paramBag, $parameterResource) {
                                $this->assertSame($paramBag->toJson(), $bag->toJson());
                                $this->assertSame($parameterResource->getParameters(), $bag->toJson());
                                return true;
                            }
                        )
                    )
                    ->willReturn($resourceQuery);

        $this->assertSame($resourceQuery, $translator->translate($parameterResource));
    }


    /**
     *
     */
    public function testValidateParametersThrows()
    {
        $this->expectException(InvalidQueryParameterException::class);

        $translator        = $this->getQueryTranslator();
        $parameterResource = $this->getParameterResource();

        $translator->expects($this->once())
            ->method("extractParameters")
            ->with($parameterResource)
            ->willReturn($parameterResource->getParameters());

        $translator->expects($this->once())
            ->method("getExpectedParameters")
            ->willReturn(["nono", "bar"]);

        $translator->translate($parameterResource);
    }


    /**
     * @param ParameterBag $bag
     * @return ResourceQuery
     */
    protected function getResourceQuery(ParameterBag $bag): ResourceQuery
    {
        return new class ($bag) extends ResourceQuery {

        };
    }


    /**
     * @return object
     */
    protected function getParameterResource(): object
    {
        return new class {

            public function getParameters(): array
            {
                return [
                    "foo" => 1,
                    "bar" => 2
                ];
            }
        };
    }


    /**
     *
     * @return QueryTranslator|MockObject
     */
    protected function getQueryTranslator(): MockObject
    {
        return $this->getMockForAbstractClass(
            QueryTranslator::class
        );
    }
}
