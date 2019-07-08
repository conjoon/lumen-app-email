<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

class UserControllerTest extends TestCase
{
    /**
     * Tests authenticate() to make sure method either returns an imap user or not.
     *
     * @return void
     */
    public function testAuthenticate()
    {
        $repository = $this->getMockBuilder('App\Imap\DefaultImapUserRepository')
                           ->disableOriginalConstructor()
                           ->getMock();

        $this->app->when(App\Http\Controllers\UserController::class)
              ->needs(App\Imap\ImapUserRepository::class)
              ->give(function () use ($repository) {
                 return $repository;
            });

        $repository->expects($this->exactly(2))
                   ->method('getUser')
                   ->will(
                       $this->onConsecutiveCalls(
                            null,
                            new App\Imap\ImapUser('foo', new App\Imap\ImapAccount([]))
                        )
                   );

        $response = $this->call('POST', 'cn_imapuser/auth', ['username' => 'dev@conjoon.org', 'password' => 'test']);
        $this->assertEquals(401, $response->status());
        $this->assertEquals(['success' => false, "msg" => "Unauthorized.", "status" => 401], $response->getData(true));


        $response = $this->call('POST', 'cn_imapuser/auth', ['username' => 'safsafasfsa', 'password' => 'test']);
        $this->assertEquals(200, $response->status());
        $this->assertEquals(['success' => true], $response->getData(true));
    }
}
