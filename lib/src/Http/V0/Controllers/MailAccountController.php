<?php

/**
 * This file is part of the conjoon/php-lib-conjoon project.
 *
 * (c) 2019-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace App\Http\V0\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

/**
 * Class MailAccountController
 * @package App\Http\Controllers
 */
class MailAccountController extends Controller
{
    /**
     * Returns all available MailAccounts for the user that is currently
     * authenticated with this application in the json response.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {

        $user = Auth::user();

        $accounts = $user->getMailAccounts();

        return response()->json([
            "success" => true,
            "data" => $accounts->toJson()
        ]);
    }
}
