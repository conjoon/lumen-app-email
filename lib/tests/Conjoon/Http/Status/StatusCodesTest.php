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

namespace Tests\Conjoon\Http\Status;

use Conjoon\Http\Status\StatusCodes;
use Tests\TestCase;

/**
 * Class StatusCodesTest
 * @package Tests\Conjoon\Http\Status
 */
class StatusCodesTest extends TestCase
{

    /**
     * test class
     */
    public function testClass()
    {

        $this->assertSame(400, StatusCodes::HTTP_400);
        $this->assertSame(405, StatusCodes::HTTP_405);
        $this->assertSame(500, StatusCodes::HTTP_500);

        $this->assertIsString(StatusCodes::HTTP_STATUS[400]);
        $this->assertIsString(StatusCodes::HTTP_STATUS[405]);
        $this->assertIsString(StatusCodes::HTTP_STATUS[500]);
    }
}
