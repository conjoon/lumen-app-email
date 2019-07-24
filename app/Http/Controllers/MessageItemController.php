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
     */
    public function index(Request $request, $mailAccountId, $mailFolderId) {

        $user = Auth::user();

        $sort = $request->input('sort');
        if ($sort) {
            $sort = json_decode($sort, true);
        } else {
            $sort = [["property" => "date", "direction" => "DESC"]];
        }

        $start = (int)$request->input('start');
        $limit = (int)$request->input('limit');

        $mailFolderId = urldecode($mailFolderId);

        $data = $this->messageItemService->getMessageItemsFor(
            $user->getMailAccount($mailAccountId), $mailFolderId, [
                "start" => $start,
                "limit" => $limit,
                "sort"  => $sort
            ]
        );

        return response()->json([
            "success" => true,
            "meta" => $data["meta"],
            "total" => $data["total"],
            "data" => $data["data"]
        ]);

    }


    /**
     * Returns a single MessageItem or MessageBody according to the specified arguments.
     * The entity to return is specified in the parameter "target". If that is missing or does not
     * default to "MessageBody" or "MessageItem", a "400 - Bad Request" is returned.
     *
     * @return ResponseJson
     */
    public function get(Request $request, $mailAccountId, $mailFolderId, $messageItemId) {

        $user = Auth::user();

        // possible targets: MessageItem, MessageBody
        $target = $request->input('target');

        $mailFolderId = urldecode($mailFolderId);

        if ($target === "MessageBody") {
            $data = $this->messageItemService->getMessageBodyFor(
                $user->getMailAccount($mailAccountId), $mailFolderId, $messageItemId
            );
        } else if ($target === "MessageItem") {
            $data = $this->messageItemService->getMessageItemFor(
                $user->getMailAccount($mailAccountId), $mailFolderId, $messageItemId
            );
        } else {
            return response()->json([
                "success" => false,
                "msg" =>  "\"target\" must be specified with either \"MessageBody\" or \"MessageItem\"."
            ], 400);

        }

        return response()->json([
            "success" => true,
            "data" => $data
        ]);

    }

}
