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

namespace Tests\Conjoon\Http\Json\Problem;

use Conjoon\Http\Json\Problem\InternalServerErrorProblem;
use Conjoon\Http\Json\Problem\Problem;
use Conjoon\Http\Json\Problem\BadRequestProblem;
use Conjoon\Http\Json\Problem\MethodNotAllowedProblem;
use Conjoon\Http\Json\Problem\ProblemFactory;
use Tests\TestCase;

/**
 * Class ProblemFactoryTest
 * @package Tests\Conjoon\Http\Json\Problem
 */
class ProblemFactoryTest extends TestCase
{

    /**
     * test make
     */
    public function testMake()
    {
        $problem = ProblemFactory::make(400, "title", "detail");
        $this->assertInstanceOf(BadRequestProblem::class, $problem);
        $this->assertSame("title", $problem->getTitle());
        $this->assertSame("detail", $problem->getDetail());

        $problem = ProblemFactory::make(405, "title", "detail");
        $this->assertInstanceOf(MethodNotAllowedProblem::class, $problem);
        $this->assertSame("title", $problem->getTitle());
        $this->assertSame("detail", $problem->getDetail());

        $problem = ProblemFactory::make(500, "title", "detail");
        $this->assertInstanceOf(InternalServerErrorProblem::class, $problem);
        $this->assertSame("title", $problem->getTitle());
        $this->assertSame("detail", $problem->getDetail());

        $problem = ProblemFactory::make(123, "title", "detail");
        $this->assertInstanceOf(Problem::class, $problem);
        $this->assertSame("title", $problem->getTitle());
        $this->assertSame("detail", $problem->getDetail());
    }

    /**
     * test makeJson()
     */
    public function testMakeJson()
    {
        $statuses = [400, 405, 500, 123442];

        foreach ($statuses as $status) {
            $problem = ProblemFactory::makeJson($status, "title", "detail");
            $this->assertIsArray($problem[0]);
            $this->assertSame(
                ProblemFactory::make($status, "title", "detail")->toJson(),
                $problem[0]
            );
            $this->assertSame(
                $status,
                $problem[1]
            );
        }
    }
}
