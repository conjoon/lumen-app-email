<?php
/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Imap\ImapUserRepository;


/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller
{

    /**
     * @var ImapUserRepository
     */
    protected $repository;


    /**
     * UserController constructor.
     *
     * @param ImapUserRepository $repository
     */
    public function __construct(ImapUserRepository $repository) {

        $this->repository = $repository;
    }

    /**
     * Action for validating a set of username/password against this ImapUserRepository.
     *
     */
    public function authenticate(Request $request)
    {
        $username = (string)$request->input("username");
        $password = (string)$request->input("password");

        $user = $this->repository->getUser($username, $password);

        if ($user) {
             return response()->json([
                 'success' => true,
                 'data'    => $user->toArray()
             ]);
        }

        return response()->json(['success' => false, "msg" => "Unauthorized.", "status" => 401], 401);
    }

}
