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

use App\Http\Middleware\Authenticate;

class AuthenticateTest extends TestCase
{
    /**
     * Tests handle() to make sure either 401 is called or next is chained.
     *
     * @return void
     */
    public function testHandle()
    {
        $authStub = $this->getMockBuilder('\Illuminate\Auth\AuthManager')
                     ->disableOriginalConstructor()
                     ->getMock();

        $stubbedStub = new class {

            public static $ISGUEST = true;

            public function guest() {
                return self::$ISGUEST;
            }
        };

        $authStub->method('guard')
                 ->willReturn($stubbedStub);

        $authenticate = new Authenticate($authStub);

        // test for is guest
        $stubbedStub::$ISGUEST = true;
        $response = $authenticate->handle(new \Illuminate\Http\Request(), function(){});
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertSame($response->getStatusCode(), 401);

        // test for authenticated
        $stubbedStub::$ISGUEST = false;
        $newRequest = new \Illuminate\Http\Request();
        $cmpRequest = null;
        $called = false;
        $response = $authenticate->handle($newRequest, function ($request) use ($newRequest, &$called){
            $this->assertSame($newRequest, $request);
            $called = true;
        });
        $this->assertNull($response);
        $this->assertTrue($called);



    }
}
