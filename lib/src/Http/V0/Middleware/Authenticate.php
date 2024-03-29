<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App\Http\V0\Middleware;

use Closure;
use Conjoon\Mail\Client\Service\AuthService;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var Auth
     */
    protected Auth $auth;


    /**
     * @var AuthService $authService
     */
    protected AuthService $authService;


    /**
     * Create a new middleware instance.
     *
     * @param Auth $auth
     * @return void
     */
    public function __construct(Auth $auth, AuthService $authService)
    {
        $this->auth = $auth;
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     * Checks if the user might access the resource. Also checks if the currently signed in
     * user can access the mailAccountId specified in the request. This will fail if
     * the mailAccountId is not the id of the MailAccount associated with the user.
     *
     * @param Request $request
     * @param Closure $next
     * @param  string|null  $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            return response()->json(["success" => false, "msg" => "Unauthorized.", "status" => 401], 401);
        }

        // check if the mailAccountId exists in the request and verify
        // that the currently signed in user can access it
        $mailAccountId = $request->route("mailAccountId");

        if ($mailAccountId) {
            $account = $this->auth->user()->getMailAccount($mailAccountId);

            if (!$account || !$this->authService->authenticate($account)) {
                return response()->json(["success" => false, "msg" => "Unauthorized.", "status" => 401], 401);
            }
        }

        return $next($request);
    }
}
