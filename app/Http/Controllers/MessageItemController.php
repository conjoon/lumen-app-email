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

use Conjoon\Mail\Client\Service\MessageItemService,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag,
    Auth;

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

        $messageItemService = $this->messageItemService;
        $mailAccount        = $user->getMailAccount($mailAccountId);

        $sort = $request->input('sort');
        if ($sort) {
            $sort = json_decode($sort, true);
        } else {
            $sort = [["property" => "date", "direction" => "DESC"]];
        }

        $start = (int)$request->input('start');
        $limit = (int)$request->input('limit');

        $mailFolderId = urldecode($mailFolderId);

        $folderKey = new FolderKey($mailAccount, $mailFolderId);

        $options = [
            "start" => $start,
            "limit" => $limit,
            "sort"  => $sort
        ];

        $data = $messageItemService->getMessageItemList($folderKey, $options)->toJson();

        return response()->json([
            "success" => true,
            "meta" => [
                 "cn_unreadCount" => $messageItemService->getUnreadMessageCount($folderKey),
                 "mailFolderId"  =>  $mailFolderId,
                 "mailAccountId" =>  $mailAccount->getId()
            ],
            "total" => $messageItemService->getTotalMessageCount($folderKey),
            "data" => $data
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

        $messageItemService = $this->messageItemService;
        $mailAccount        = $user->getMailAccount($mailAccountId);

        // possible targets: MessageItem, MessageBody
        $target = $request->input('target');

        $mailFolderId = urldecode($mailFolderId);

        $messageKey = new MessageKey($mailAccount, $mailFolderId, $messageItemId);

        if ($target === "MessageBody") {
            $item = $messageItemService->getMessageBody($messageKey);
        } else if ($target === "MessageItem") {
            $item = $messageItemService->getMessageItem($messageKey);
        } else {
            return response()->json([
                "success" => false,
                "msg" =>  "\"target\" must be specified with either \"MessageBody\" or \"MessageItem\"."
            ], 400);

        }

        return response()->json([
            "success" => true,
            "data" => $item->toJson()
        ]);

    }


    /**
     * Changes data of a single MessageItem.
     * Allows for specifying target=MessageItem or target=MessageDraft.
     * If the target MessageItem is specified, the flag-properties
     * seen=true/false and/or flagged=true/false can be set.
     * If the target is MessageDraft, the following parameters are expected:
     *
     * - id - compound key information
     * - mailAccountId - compound key information
     * - mailFolderId - compound key information
     * - bcc
     * - cc
     * - date
     * - from
     * - subject
     * - to
     *
     * Everything else returns a 405.
     *
     * @return ResponseJson
     */
    public function put(Request $request, $mailAccountId, $mailFolderId, $messageItemId) {

        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        $mailAccount        = $user->getMailAccount($mailAccountId);

        // possible targets: MessageItem
        $target = $request->input('target');

        $mailFolderId = urldecode($mailFolderId);
        $messageKey = new MessageKey($mailAccount, $mailFolderId, $messageItemId);

        switch ($target) {

            case "MessageDraft":

                $keys = ["subject", "date", "from", "to", "cc", "bcc", "seen", "flagged", "replyTo"];
                $data = $request->only($keys);

                $updatedMessageItemDraft = $messageItemService->updateMessageDraft($messageKey, $data);

                $resp = [
                    "success" => !!$updatedMessageItemDraft
                ];
                if ($updatedMessageItemDraft) {
                    $resp["data"] = $updatedMessageItemDraft->toJson();
                } else {
                    $resp["msg"] = "Updating the MessageDraft failed.";
                }
                return response()->json($resp, 200);

                break;

            case "MessageItem":

                $seen    = $request->input('seen');
                $flagged = $request->input('flagged');

                if (!is_bool($seen) && !is_bool($flagged)) {
                    return response()->json([
                        "success" => false,
                        "msg"     =>  "Invalid request payload.",
                        "flagged" => $flagged,
                        "seen"    => $seen
                    ], 400);
                }

                $flagList   = new FlagList();
                $response   = [];
                if ($seen !== null) {
                    $flagList[] = new SeenFlag($seen);
                    $response["seen"] = $seen;
                }
                if ($flagged !== null) {
                    $flagList[] = new FlaggedFlag($flagged);
                    $response["flagged"] = $flagged;
                }

                $result = $messageItemService->setFlags($messageKey, $flagList);

                return response()->json([
                    "success" => $result,
                    "data"    => array_merge(
                        $messageKey ->toJson(),
                        $response
                    )
                ], 200);

                break;

            default:
                return response()->json([
                    "success" => false,
                    "msg" =>  "\"target\" must be specified with \"MessageDraft\" or \"MessageItem\"."
                ], 400);
                break;
        }

    }


    /**
     * Posts new MessageBody data to the specified $mailFolderId for the account identified by
     * $mailAccountId, creating an entirely new Message
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     *
     * @return ResponseJson
     */
    public function post(Request $request, $mailAccountId, $mailFolderId) {

        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        $mailAccount        = $user->getMailAccount($mailAccountId);

        // possible targets: MessageBody
        $target = $request->input('target');
        // possible parameters: textHtml, textPlain
        $textPlain = $request->input('textPlain');
        $textHtml  = $request->input('textHtml');

        $mailFolderId = urldecode($mailFolderId);
        $folderKey = new FolderKey($mailAccount, $mailFolderId);

        if ($target !== "MessageBody") {
            return response()->json([
                "success" => false,
                "msg" =>  "\"target\" must be specified with \"MessageBody\"."
            ], 400);
        }

        $messageBody = $messageItemService->createMessageBody(
            $folderKey, $textPlain, $textHtml, true);

        if (!$messageBody) {
            return response()->json([
                "success" => false,
                "msg"     => "Creating the MessageBody failed."
            ], 400);
        }

        return response()->json([
            "success" => !!$messageBody ,
            "data"    => $messageBody->toJson()
        ], 200);

    }


}
