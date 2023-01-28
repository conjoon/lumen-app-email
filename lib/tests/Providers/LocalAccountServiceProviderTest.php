<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2023 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App\Providers;

use App\Providers\LocalAccountServiceProvider;
use Conjoon\Illuminate\Auth\ImapUser;
use Conjoon\Illuminate\Auth\LocalMailAccount\LocalAccountProvider;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;

/**
 * Class LocalAccountServiceProvider.
 */
class LocalAccountServiceProviderTest extends TestCase
{
    /**
     * Makes sure register() registers the LocalAccountProvider.
     *
     *
     */
    public function testRegister()
    {
        $app = $this->createAppMock();
        /** @noinspection PhpParamsInspection */
        $provider = new LocalAccountServiceProvider($app);

        $app['auth'] = Mockery::mock($app['auth']);

        $app['auth']
            ->shouldReceive("provider")
            ->withArgs(
                function ($driverName, $callback) use ($app) {
                    $this->assertSame("LocalAccountProviderDriver", $driverName);
                    $this->assertIsCallable($callback);

                    $this->assertInstanceOf(
                        LocalAccountProvider::class,
                        $callback($app, [])
                    );

                    return true;
                }
            );

        $provider->register();
    }

    /**
     * Makes sure boot() calls getUser.
     *
     */
    public function testBootCallsGetUser()
    {
        $app = $this->createAppMock();

        $imapUser = $this->getMockBuilder(ImapUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider = $this->getMockBuilder(LocalAccountServiceProvider::class)
            ->setConstructorArgs([$app])
            ->onlyMethods(["getUser"])
            ->getMock();

        $cb = function () use ($imapUser) {
            return $imapUser;
        };

        $provider->method("getUser")
            ->willReturnCallback($cb);


        $app['auth'] = Mockery::mock($app['auth']);

        $app['auth']->shouldReceive("viaRequest")
            ->withArgs(function ($api, $callback) use ($app, $imapUser) {
                $this->assertSame("api", $api);
                $this->assertIsCallable($callback);

                /** @noinspection PhpUndefinedMethodInspection */
                $this->assertSame($imapUser, $callback(
                    new Request(),
                    $app->make(LocalAccountProvider::class)
                ));

                return true;
            });

        $provider->boot();
    }


    /**
     * Should delegate call to UserProvider's retrieveByCredentials
     *
     * @throws ReflectionException
     */
    public function testgetUserCallRetrieveByCredentials()
    {
        $app = $this->createAppMock();

        /** @noinspection PhpParamsInspection */
        $authProvider = new LocalAccountServiceProvider($app);

        $request = Mockery::mock(new Request());
        $request->shouldReceive("getUser")
            ->andReturn("someUser");
        $request->shouldReceive("getPassword")
            ->andReturn("somePassword");

        /** @noinspection PhpUndefinedMethodInspection */
        $userProvider = Mockery::mock($app->make(LocalAccountProvider::class));

        $userProvider->shouldReceive("retrieveByCredentials")
                     ->withArgs([["username" => "someUser", "password" => "somePassword"]])
                     ->andReturn(
                         $this->getMockBuilder(ImapUser::class)
                              ->disableOriginalConstructor()
                              ->getMock()
                     );

        $reflection = new ReflectionClass($authProvider);
        $property = $reflection->getMethod("getUser");
        $property->setAccessible(true);

        $user = $property->invokeArgs($authProvider, [$request, $userProvider]);

        $this->assertNotNull($user);
    }


    /**
     * Mocks the application by returning a specific LocalAccountProvider-configuration.
     *
     * @return MockInterface
     */
    protected function createAppMock(): MockInterface
    {
        $app = Mockery::mock($this->app);


        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(["header", "route"])
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->once())->method("route")->with("mailAccountId")->willReturn("routeId");

        $conf = [
            "from" => ["address" => "", "name" => "name"],
            "replyTo" => ["address" => "", "name" => "name"],
            "inbox_type" => "IMAP",
            "inbox_port" => 993,
            "inbox_ssl" => true,
            "outbox_address" => "a.b.c",
            "outbox_port" => 993,
            "outbox_secure" => "ssl",
        ];

        $request->expects($this->once())->method("header")->with("x-cnmail-data")->willReturn(
            base64_encode(json_encode($conf))
        );

        $provider = new LocalAccountProvider($request);

        $app->shouldReceive("make")
            ->with(LocalAccountProvider::class)
            ->andReturn($provider);

        return $app;
    }
}
