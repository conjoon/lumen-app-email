<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2019-2023 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App\Providers;

use Closure;
use Conjoon\Illuminate\Auth\ImapUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

/**
 * Class ImapAuthServiceProvider.
 * Uses a RequestGuard for authorization of API calls.
 *
 * @package App\Providers
 */
class ImapAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function register()
    {
        $this->app["auth"]->provider(
            "ImapUserProviderDriver",
            function ($app, array $config) {
                return $app->make(ImapUserProvider::class);
            }
        );
    }


    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->app["auth"]->viaRequest(
            "api",
            Closure::fromCallable([$this, "getImapUser"])
        );

        config(["app.api.service.imapuser" => [
            "path" => env("APP_AUTH_PATH", "rest-imapuser"),
            "versions" => ["v0"],
            "latest" => "v0"
        ]]);

        $this->app->configure('imapserver');

        $app = $this->app;
        require base_path('routes/rest-imapuser/web.php');
    }


    /**
     * Returns the user for the request, if any.
     * Delegates to the ImapUserProvider registered via "ImapUserProviderDriver".
     *
     * @param Request $request
     * @param ImapUserProvider $provider
     *
     * @return Authenticatable
     */
    protected function getImapUser(Request $request, ImapUserProvider $provider): Authenticatable
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        return $provider->retrieveByCredentials([
           "username" => $username,
           "password" => $password
        ]);
    }
}
