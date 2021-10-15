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

namespace Tests\App\Providers;

use App\Providers\AuthServiceProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Laravel\Lumen\Application;
use Mockery;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;
use App\Imap\ImapUserRepository;
use App\Imap\DefaultImapUserRepository;
use Illuminate\Http\Request;
use App\Imap\ImapUser;

class AuthServiceProviderTest extends TestCase
{

    /**
     * makes sure boot() calls getUser
     *
     * @throws BindingResolutionException
     */
    public function testBootCallsGetUser()
    {
        $this->app = $this->createAppMock();

        $imapUser = $this->getMockBuilder(ImapUser::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $provider = $this->getMockBuilder(AuthServiceProvider::class)
                         ->setConstructorArgs([$this->app])
                         ->onlyMethods(["getUser"])
                         ->getMock();

        $cb = function () use ($imapUser) {
            return $imapUser;
        };

        $provider->method("getUser")
                 ->willReturnCallback($cb);


        $this->app['auth'] = Mockery::mock($this->app['auth']);

        $this->app['auth']->shouldReceive("viaRequest")
             ->withArgs(function ($api, $callback) use ($imapUser) {
                 $this->assertSame("api", $api);
                 $this->assertIsCallable($callback);

                 $this->assertSame($imapUser, $callback(new Request()));

                 return true;
             });

        $provider->boot();
    }


    /**
     * Should return null if user and password is null
     *
     * @throws ReflectionException
     */
    public function testGetUserUserMissingCredentials()
    {
        $this->assertNull($this->getUser());
        $this->assertNull($this->getUser("foo"));
        $this->assertNull($this->getUser(null, "bar"));
    }


    /**
     * Should return null if user/password was found
     *
     * @throws ReflectionException
     */
    public function testGetUserUserIsNotExisting()
    {
        $this->assertNull($this->getUser("foo", "bar"));
    }


    /**
     * Should return user if user is found
     *
     * @throws ReflectionException
     */
    public function testGetUserUserIsExisting()
    {
        $user = $this->getUser("conjoon", "random");
        $this->assertNotNull($user);
        $this->assertInstanceOf(ImapUser::class, $user);
    }


    /**
     * Mocks request tries to return a user from the repository.
     *
     * @param null $user
     * @param null $password
     * @return Request|Mockery\LegacyMockInterface|Mockery\MockInterface
     * @throws ReflectionException
     */
    protected function getUser($user = null, $password = null)
    {
        $request = Mockery::mock(new Request());
        $request->shouldReceive("getUser")
                ->andReturn($user);
        $request->shouldReceive("getPassword")
                ->andReturn($password);

        $provider = new AuthServiceProvider($this->createAppMock());
        $reflection = new ReflectionClass($provider);
        $property = $reflection->getMethod("getUser");
        $property->setAccessible(true);

        return $property->invokeArgs($provider, [$request]);
    }


    /**
     * Mocks the application by returning a specific ImapUserRepository-configuration.
     *
     * @return Application|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    protected function createAppMock()
    {
        $app = Mockery::mock($this->app);

        $app->shouldReceive("make")
            ->with(ImapUserRepository::class)
            ->andReturn(new DefaultImapUserRepository([
                ["id"              => "conjoon",
                    "inbox_type"      => "IMAP",
                    "inbox_port"      => 993,
                    "inbox_ssl"       => true,
                    "outbox_address"  => "a.b.c",
                    "outbox_port"     => 993,
                    "outbox_ssl"      => true,
                    "root"            => ["INBOX"],
                    "match"           => ["/conjoon$/mi"]
                ]
            ]));

        return $app;
    }
}
