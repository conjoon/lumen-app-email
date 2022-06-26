<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\Exceptions;

use App\Exceptions\Handler;
use Conjoon\Core\JsonStrategy;
use Conjoon\Http\Exception\BadRequestException;
use Conjoon\Http\Exception\ForbiddenException;
use Conjoon\Http\Exception\InternalServerErrorException;
use Conjoon\Http\Exception\MethodNotAllowedException;
use Conjoon\Http\Exception\NotFoundException;
use Conjoon\Http\Exception\UnauthorizedException;
use Conjoon\Http\Json\Problem\ProblemFactory;
use Conjoon\Mail\Client\Exception\ResourceNotFoundException;
use Conjoon\Mail\Client\Service\ServiceException;
use Illuminate\Http\Request;
use Tests\Conjoon\Http\Exception\InternalServerErrorExceptionTest;
use Tests\TestCase;

/**
 * Class HandlerTest
 * @package Tests\Exceptions
 */
class HandlerTest extends TestCase
{
    /**
     * tests render()
     */
    public function testRender()
    {
        $exceptions = [
            "400" => [["exc" => new BadRequestException()]],
            "401" => [["exc" => new UnauthorizedException()]],
            "403" => [["exc" => new ForbiddenException()]],
            "404" => [["exc" => new NotFoundException()]],
            "405" => [["exc" => new MethodNotAllowedException()]],
            "500" => [[
                "exc" => new InternalServerErrorException()
            ], [
                "exc" => new ServiceException(), "code" => 500
            ]],
        ];


        foreach ($exceptions as $code => $testConfig) {
            foreach ($testConfig as $config) {
                $exc     = $config["exc"];
                $excCode = $config["code"] ?? $exc->getCode();

                $problem = ProblemFactory::make($excCode, null, $exc->getMessage());

                // re-init, otherwise use onConsecutiveCalls
                $strategy = $this->getMockForAbstractClass(JsonStrategy::class);
                $handler = new Handler($strategy);
                $strategy->expects($this->any())->method("toJson")->with($problem)->willReturn($problem->toJson());

                $resp = $handler->render(new Request(), $exc);

                $this->assertEquals(
                    $problem->toJson(),
                    json_decode($resp->getContent(), true)["errors"][0]
                );

                $this->assertSame($code, $resp->getStatusCode());
            }
        }
    }


    /**
     * tests render()
     */
    public function testRenderRegular()
    {
        $handler = new Handler($this->app->get(JsonStrategy::class));
        $exc = new \Exception();
        $resp = $handler->render(new Request(), $exc);

        $this->assertSame(
            500,
            $resp->getStatusCode()
        );
    }
}
