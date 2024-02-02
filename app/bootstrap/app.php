<?php

/**
 * This file is part of the conjoon/lumen-app-email project.
 *
 * (c) 2019-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Fruitcake\Cors\CorsServiceProvider;
use Fruitcake\Cors\HandleCors;
use Laravel\Lumen\Bootstrap\LoadEnvironmentVariables;


(new LoadEnvironmentVariables(
    dirname(__DIR__, 2)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->configure('app');

$versions = config("app.api.service.email.versions");
$requested = config("app.api.service.email.latest");

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/
require __DIR__ . "/bindings_{$requested}.php";

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/
$app->register(CorsServiceProvider::class);
$app->configure('cors');
$app->middleware([HandleCors::class]);

$authMiddleware = [];
foreach ($versions as $version) {
    $version = ucfirst($version);
    $authMiddleware['auth_' . $version] = "App\Http\\" . $version . "\Middleware\Authenticate";
}
$app->routeMiddleware($authMiddleware);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->configure('auth');
$provider = config("auth.defaults.provider");
$providers =  config("auth.providers");
$authProviderClass = $providers[$provider]["providerClass"];
$app->register($authProviderClass);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

require __DIR__ . '/../routes/web.php';

return $app;
