<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

use App\Imap\Service\MessageItemService;

use Auth;

use Illuminate\Http\Request;


/**
 * Class MessageItemController
 * @package App\Http\Controllers
 */
class MessageItemController extends Controller {


    /**
     * @var messageItemService
     */
    protected $messageItemService;


    /**
     * MessageItemController constructor.
     *
     * @param MessageItemService $messageItemService
     */
    public function __construct(MessageItemService $messageItemService) {

        $this->messageItemService = $messageItemService;

    }


    /**
     * Returns all available MessageItems for the user that is currently
     * authenticated for the specified $mailAccountId and the specified $mailFolderId.
     *
     * @return ResponseJson
     *
     * @throws if $mailAccountId is not the id of the ImapAccount associated
     * with the user
     */
    public function get(Request $request, $mailAccountId, $mailFolderId) {

        $user = Auth::user();

        $account = $user->getImapAccount();

        if ($account->getId() !== $mailAccountId) {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        }

        $start = (int)$request->input('start');
        $limit = (int)$request->input('limit');

        $mailFolderId = urldecode($mailFolderId);

        $data = $this->messageItemService->getMessageItemsFor(
            $user->getImapAccount(), $mailFolderId, [
                "start" => $start,
                "limit" => $limit
            ]
        );

        return response()->json([
            "success" => true,
            "meta" => $data["meta"],
            "total" => $data["total"],
            "data" => $data["data"]
        ]);

    }

}
