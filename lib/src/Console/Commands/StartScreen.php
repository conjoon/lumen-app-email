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
class StartScreen
{
    public static function toString(): string
    {

        $version = InstalledVersions::getVersion('conjoon/lumen-app-email');
        // @codingStandardsIgnoreStart
        $logo = <<<SCREEN
<fg=yellow>


    __ https://conjoon.org                                                          _ __
   / /_  ______ ___  ___  ____        ____ _____  ____        ___  ____ ___  ____ _(_) /
  / / / / / __ `__ \/ _ \/ __ \______/ __ `/ __ \/ __ \______/ _ \/ __ `__ \/ __ `/ / /
 / / /_/ / / / / / /  __/ / / /_____/ /_/ / /_/ / /_/ /_____/  __/ / / / / / /_/ / / /
/_/\__,_/_/ /_/ /_/\___/_/ /_/      \__,_/ .___/ .___/      \___/_/ /_/ /_/\__,_/_/_/
                                        /_/   /_/   <href=https://www.conjoon.org/docs/api/backends/@conjoon/lumen-app-email>conjoon/lumen-app-email</> <fg=red;bg=white>$version</>
</>

<fg=green>Welcome!</>
This guide will help you to set up your instance of <fg=blue>lumen-app-email</>.

SCREEN;
        // @codingStandardsIgnoreEnd
        return $logo;
    }
}
