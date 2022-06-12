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

namespace Tests\App\Http\V0\Middleware;

use App\Http\V0\Middleware\Authenticate;
use Conjoon\Http\Exception\NotFoundException;
use Conjoon\Http\Exception\UnauthorizedException;
use Conjoon\Mail\Client\Service\DefaultAuthService;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Class AuthenticateTest
 * @package Tests\App\Http\V0\Middleware
 */
class AuthenticateTest extends TestCase
{
    use TestTrait;

    /**
     * Tests handle() to make sure either 401 is called or next is chained.
     *
     * @return void
     */
    public function testHandle()
    {
        $authService = $this->getMockBuilder(DefaultAuthService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authStub = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedStub = new class {
            public static bool $isGuest = true;

            public function guest(): bool
            {
                return self::$isGuest;
            }
        };

        $authStub->method("guard")
            ->willReturn($stubbedStub);

        $authenticate = new Authenticate($authStub, $authService);

        // test for is guest
        $stubbedStub::$isGuest = true;
        $response = $authenticate->handle(new Request(), function () {
        });
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($response->getStatusCode(), 401);

        // test for authenticated
        $stubbedStub::$isGuest = false;
        $newRequest = new Request();
        $called = false;
        $response = $authenticate->handle($newRequest, function ($request) use ($newRequest, &$called) {
            $this->assertSame($newRequest, $request);
            $called = true;
        });
        $this->assertNull($response);
        $this->assertTrue($called);
    }


    /**
     * Tests handle() to make sure 401 is called if users accountId does not
     * match the request mailAccountId
     *
     * @return void
     * @noinspection PhpMissingFieldTypeInspection
     */
    public function testHandleAccountCompare()
    {
        $authService = $this->getMockBuilder(DefaultAuthService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authService->expects($this->exactly(1))
            ->method("authenticate")
            ->will(
                $this->onConsecutiveCalls(
                    true
                )
            );

        $authStub = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedStub = new class {
            public function guest(): bool
            {
                return false;
            }
        };

        $user = $this->getTestUserStub();

        $authStub->method("guard")
            ->willReturn($stubbedStub);

        // we just need the test user here in the __call to
        // guard->user()
        $authStub->method("__call")
            ->willReturn($user);

        $authenticate = new Authenticate($authStub, $authService);

        // test for authenticated
        $newRequest = new Request();
        $newRequest->setRouteResolver(function () {
            return new class {
                public function parameter($param): ?string
                {
                    if ($param === "mailAccountId") {
                        return "testFail";
                    }
                    return null;
                }
            };
        });

        try {
            $authenticate->handle($newRequest, function() {});
            $this->fail("Exception was never thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
        }

        // OKAY
        $newRequest->setRouteResolver(function () use ($user) {
            return new class ($user) {
                protected $user;

                public function __construct($user)
                {
                    $this->user = $user;
                }

                public function parameter($param): ?string
                {
                    if ($param === "mailAccountId") {
                        return $this->user->getMailAccount("someId")->getId();
                    }
                    return null;
                }
            };
        });

        $called = false;
        $response = $authenticate->handle($newRequest, function ($request) use ($newRequest, &$called) {
            $this->assertSame($newRequest, $request);
            $called = true;
        });
        $this->assertNull($response);
        $this->assertTrue($called);
    }


    /**
     * Tests handle() to make sure 401 is returned if authenticate() with
     * AuthService failed.
     *
     * @return void
     * @noinspection PhpMissingFieldTypeInspection
     */
    public function testAuthenticateFailed()
    {
        $authService = $this->getMockBuilder(DefaultAuthService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authService->expects($this->exactly(1))
            ->method("authenticate")
            ->will(
                $this->onConsecutiveCalls(
                    false
                )
            );

        $authStub = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedStub = new class {
            public function guest(): bool
            {
                return false;
            }
        };

        $user = $this->getTestUserStub();

        $authStub->method("guard")
            ->willReturn($stubbedStub);

        // we just need the test user here in the __call to
        // guard->user()
        $authStub->method("__call")
            ->willReturn($user);

        $authenticate = new Authenticate($authStub, $authService);

        // test for authenticated
        $newRequest = new Request();
        $newRequest->setRouteResolver(function () use ($user) {
            return new class ($user) {
                public function __construct($user)
                {
                    $this->user = $user;
                }
                public function parameter($param): ?string
                {
                    if ($param === "mailAccountId") {
                        return $this->user->getMailAccount("someId")->getId();
                    }
                    return null;
                }
            };
        });

        try {
            $authenticate->handle($newRequest, function() {});
            $this->fail("Exception was never thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf(UnauthorizedException::class, $e);
        }
    }
}
