<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2023 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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
use Conjoon\Illuminate\Auth\LocalMailAccount\LocalAccountProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class LocalAccountServiceProvider extends ServiceProvider
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
            "LocalAccountProviderDriver",
            function ($app, array $config) {
                return $app->make(LocalAccountProvider::class);
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
            Closure::fromCallable([$this, "getUser"])
        );
    }


    /**
     * Returns the user for the request, if any.
     * Delegates to the ImapUserProvider registered via "ImapUserProviderDriver".
     *
     * @param Request $request
     * @param LocalAccountProvider $provider
     *
     * @return Authenticatable
     */
    protected function getUser(Request $request, LocalAccountProvider $provider): Authenticatable
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        return $provider->retrieveByCredentials([
           "username" => $username,
           "password" => $password
        ]);
    }
}