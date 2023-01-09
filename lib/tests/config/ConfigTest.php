<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2023 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\config;

use Tests\TestCase;
use App\Providers\ImapAuthServiceProvider;
use App\Providers\LocalAccountServiceProvider;

class ConfigTest extends TestCase
{
    public function testImapUserConfig()
    {
        $config = include(__DIR__ . "../../../../app/config/auth.php");

        $this->assertEquals([
            "providerClass" => LocalAccountServiceProvider::class,
            "driver" => "LocalAccountProviderDriver"
        ], $config["providers"]["local-mail-account"]);

        $this->assertEquals([
            "providerClass" => ImapAuthServiceProvider::class,
            "driver" => "ImapUserProviderDriver"
        ], $config["providers"]["single-imap-user"]);
    }


    public function testAppConfig()
    {
        $config = include(__DIR__ . "../../../../app/config/app.php");
        $this->assertEquals([
            "service" => [
                "email" => [
                    "path" => "rest-api-email",
                    "versions" => ["v0"],
                    "latest" => "v0"
                ]
            ]
        ], $config["api"]);
    }
}
