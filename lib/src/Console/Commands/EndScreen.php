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

namespace App\Console\Commands;

use Composer\InstalledVersions;

/**
 *
 */
class EndScreen
{
    public static function toString(): string
    {
        $version = InstalledVersions::getVersion('conjoon/lumen-app-email');
        // @codingStandardsIgnoreStart
        $txt = <<<OUTRO

        Installation  of <fg=blue>lumen-app-email</><fg=yellow>@$version</> finished!

        You can always restart the installation-process by typing

            <fg=green>php artisan install</>

        or use any of the available configuration-commands:

            <fg=green>php artisan configure:url</> (configure the URL where this instance is located)
            <fg=green>php artisan configure:api</> (configure the paths to the API-endpoints)
            <fg=green>php artisan configure:env</> (configure the environment this instance runs in)
            <fg=green>php artisan configure:debug</> (configure the debug mode for this instance)

        If you do not have a working frontend for this instance yet,
        we recommend that you install <fg=blue>conjoon</>, a JavaScript
        frontend application: https://conjoon.org

            Have a nice day! ☕

OUTRO;
        // @codingStandardsIgnoreEnd


        return $txt;
    }
}
