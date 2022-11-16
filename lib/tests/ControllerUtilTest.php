<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App;

use App\ControllerUtil;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Tests\TestCase;

/**
 * Class ControllerUtilTest
 * @package Tests\App\
 */
class ControllerUtilTest extends TestCase
{
    /**
     * Tests getResourceUrl()
     * @return void
     */
    public function testGetResourceUrl()
    {
        $ctrlUtil = new ControllerUtil();

        $callerUri = "https://localhost:8080/api/v4/path?query=value";
        $key = new MessageKey("A", "B", "C");

        $this->assertSame(
            config("app.url") . "/" . config("app.api.service.email") . "/v4/" .
            "MailAccounts/A/MailFolders/B/MessageItems/C",
            $ctrlUtil->getResourceUrl("MessageItem", $key, $callerUri)
        );

        // w/o version
        $callerUri = "https://localhost:8080/api/path?query=value";

        $this->assertSame(
            config("app.url") . "/" . config("app.api.service.email") . "/" .
            config("app.api.latest") . "/" .
            "MailAccounts/A/MailFolders/B/MessageItems/C",
            $ctrlUtil->getResourceUrl("MessageItem", $key, $callerUri)
        );
    }
}
