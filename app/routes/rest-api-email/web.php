<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2020-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
|API Versioning groups, loading specific route configurations based on the prefix.
|
*/

$router = $app->router;
$versions = config("app.api.service.email.versions");
$latest = config("app.api.service.email.latest");

$prefix = config("app.api.service.email.path");

foreach ($versions as $version) {
    $router->group([
        "middleware" => "auth_" . ucfirst($version),
        'namespace' => "App\Http\\" . ucfirst($version) . "\Controllers",
        'prefix' => "$prefix/" . $version
    ], function () use ($router, $version) {

        require base_path("routes/rest-api-email/api_" . $version . ".php");
    });
}

// config for latest
$router->group([
    "middleware" => "auth_" . ucfirst($latest),
    'namespace' => "App\Http\\" . ucfirst($latest) . "\Controllers",
    'prefix' => $prefix
], function () use ($router, $latest) {
    require base_path("routes/rest-api-email/api_" . $latest . ".php");
});
