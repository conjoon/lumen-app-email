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

namespace App\Http\V0\Controllers;

use App\Http\V0\Query\MessageItem\IndexRequestQueryTranslator;
use App\Http\V0\Query\MessageItem\GetRequestQueryTranslator;
use Illuminate\Support\Facades\Auth;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlaggedFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\SeenFlag;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Service\MessageItemService;
use Conjoon\Util\ArrayUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Class MessageItemController
 * @package App\Http\Controllers
 */
class MessageItemController extends Controller
{
    /**
     * @type MessageItemService
     */
    protected MessageItemService $messageItemService;

    /**
     * @type MessageItemDraftJsonTransformer
     */
    protected MessageItemDraftJsonTransformer $messageItemDraftJsonTransformer;


    /**
     * @type MessagebodyDraftJsonTransformer
     */
    protected MessagebodyDraftJsonTransformer $messageBodyDraftJsonTransformer;

    /**
     * MessageItemController constructor.
     *
     * @param MessageItemService $messageItemService
     * @param MessageItemDraftJsonTransformer $messageItemDraftJsonTransformer
     * @param MessageBodyDraftJsonTransformer $messageBodyDraftJsonTransformer
     */
    public function __construct(
        MessageItemService $messageItemService,
        MessageItemDraftJsonTransformer $messageItemDraftJsonTransformer,
        MessagebodyDraftJsonTransformer $messageBodyDraftJsonTransformer
    ) {

        $this->messageItemService              = $messageItemService;
        $this->messageItemDraftJsonTransformer = $messageItemDraftJsonTransformer;
        $this->messageBodyDraftJsonTransformer = $messageBodyDraftJsonTransformer;
    }


    /**
     * Returns all available MessageItems for the user that is currently
     * authenticated for the specified $mailAccountId and the specified $mailFolderId.
     *
     * @param Request $request
     * @param IndexRequestQueryTranslator $translator
     * @param string $mailAccountId
     * @param string $mailFolderId
     *
     * @return JsonResponse
     */
    public function index(
        Request $request,
        IndexRequestQueryTranslator $translator,
        string $mailAccountId,
        string $mailFolderId
    ): JsonResponse {

        $user = Auth::user();

        $resourceQuery = $translator->translate($request);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount = $user->getMailAccount($mailAccountId);
        $folderKey   = new FolderKey($mailAccount, urldecode($mailFolderId));

        $messageItemService = $this->messageItemService;

        return response()->json([
            "success" => true,
            "meta" => [
                 "cn_unreadCount" => $messageItemService->getUnreadMessageCount($folderKey),
                 "mailFolderId"  =>  $folderKey->getId(),
                 "mailAccountId" =>  $mailAccount->getId()
            ],
            "total" => $messageItemService->getTotalMessageCount($folderKey),
            "data" => $messageItemService->getMessageItemList($folderKey, $resourceQuery)->toJson()
        ]);
    }


    /**
     * Returns a single MessageBody according to the specified arguments.
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse
     */
    public function getMessageBody(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $messageKey = new MessageKey(
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            Auth::user()->getMailAccount($mailAccountId),
            urldecode($mailFolderId),
            $messageItemId
        );

        $item = $this->messageItemService->getMessageBody($messageKey);

        return response()->json([
            "success" => true,
            "data" => $item->toJson()
        ]);
    }


    /**
     * Returns a single MessageItem or MessageItemDraft according to the specified arguments.
     *
     * @param Request $request
     * @param GetRequestQueryTranslator $translator
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse
     */
    public function get(
        Request $request,
        GetRequestQueryTranslator $translator,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $user = Auth::user();

        $resourceQuery = $translator->translate($request);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount = $user->getMailAccount($mailAccountId);
        $folderKey   = new FolderKey($mailAccount, urldecode($mailFolderId));

        $messageItemService = $this->messageItemService;

        return response()->json([
            "success" => true,
            "data" => $messageItemService->getMessageItemList($folderKey, $resourceQuery)[0]->toJson()
        ]);
    }


    /**
     * Deletes a single MessageItem and its associations permanently.
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse with status 200 if deleting the message succeeded, otherwise a 500
     */
    public function delete(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount  = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);

        $messageKey = new MessageKey($mailAccount, $mailFolderId, $messageItemId);

        $result = $messageItemService->deleteMessage($messageKey);

        return response()->json([
            "success" => $result
        ], $result ? 200 : 500);
    }


    /**
     * Updates the MessageBody.
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     * @return JsonResponse
     */
    public function patchMessageBody(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount  = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);

        $data = array_merge(
            ArrayUtil::only(ArrayUtil::unchain("data.attributes", $request->all(), []), ["textHtml", "textPlain"]),
            [
                "mailAccountId" => $mailAccount->getId(),
                "mailFolderId" => $mailFolderId,
                "id" => $messageItemId,
            ]
        );

        $messageBody        = $this->messageBodyDraftJsonTransformer::fromArray($data);
        $updatedMessageBody = $messageItemService->updateMessageBodyDraft($messageBody);

        $resp = ["success" => !!$updatedMessageBody];
        if ($updatedMessageBody) {
            $resp["data"] = $updatedMessageBody->toJson();
        } else {
            $resp["msg"] = "Updating the MessageBodyDraft failed.";
        }
        return response()->json($resp);
    }

    /**
     * Changes data of a single MessageDraft
     *     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse
     */
    public function patchMessageDraft(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $user = Auth::user();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount        = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);
        $messageKey = new MessageKey($mailAccount, $mailFolderId, $messageItemId);

        $messageItemService = $this->messageItemService;

        $attributes = ArrayUtil::unchain("data.attributes", $request->all());

        $root = [
            "mailAccountId" => $mailAccount->getId(),
            "mailFolderId" => $messageKey->getMailFolderId(),
            "id" => $messageKey->getId()
        ];
        $additional = ["inReplyTo", "references", "xCnDraftInfo"];

        $data = array_merge(
            $attributes,
            $root,
            // extract additional data  ["inReplyTo", "references", "xCnDraftInfo"]
            ArrayUtil::only(ArrayUtil::unchain("data.attributes", $request->all()), $additional)
        );

        $messageItemDraft        = $this->messageItemDraftJsonTransformer::fromArray($data);
        $updatedMessageItemDraft = $messageItemService->updateMessageDraft($messageItemDraft);

        $resp = [
            "success" => !!$updatedMessageItemDraft
        ];
        if ($updatedMessageItemDraft) {
            $json = $updatedMessageItemDraft->toJson();
            $resp["data"] = ArrayUtil::only($json, array_merge(array_keys($data), ["messageId"]));
        } else {
            $resp["msg"] = "Updating the MessageDraft failed.";
        }

        return response()->json($resp);
    }


    /**
     * Changes data of a single MessageItem.
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse
     */
    public function patchMessageItem(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {

        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount        = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);
        $messageKey = new MessageKey($mailAccount, $mailFolderId, $messageItemId);


        $attributes = ArrayUtil::unchain("data.attributes", $request->all());

        $response   = [];

        $seen    = $attributes["seen"] ?? null;
        $flagged = $attributes["flagged"] ?? null;
        $draft   = $attributes["draft"] ?? null;

        $newMailFolderId = $attributes["mailFolderId"] ?? null;

        $isMove = $newMailFolderId;

        if ($isMove && $newMailFolderId === $mailFolderId) {
            return response()->json([
                "success" => false,
                "msg"     => "Cannot move message since it already belongs to this folder."
            ], 400);
        }

        if (!$isMove && !is_bool($seen) && !is_bool($flagged) && !is_bool($draft)) {
            return response()->json([
                "success" => false,
                "msg"     => "Invalid request payload.",
                "flagged" => $flagged,
                "seen"    => $seen,
                "draft"   => $draft
            ], 400);
        }

        $flagList = new FlagList();
        if ($seen !== null) {
            $flagList[] = new SeenFlag($seen);
            $response["seen"] = $seen;
        }
        if ($flagged !== null) {
            $flagList[] = new FlaggedFlag($flagged);
            $response["flagged"] = $flagged;
        }

        if ($draft !== null) {
            $flagList[] = new DraftFlag($draft);
            $response["draft"] = $draft;
        }
        if (count($flagList) !== 0) {
            $flagResult = $messageItemService->setFlags($messageKey, $flagList);

            // exit here if we do not have anything related to MailFolders
            if ($newMailFolderId === null || $newMailFolderId === $mailFolderId) {
                return response()->json([
                    "success" => $flagResult,
                    "data"    => array_merge(
                        $messageKey ->toJson(),
                        $response
                    )
                ], $flagResult ? 200 : 500);
            }
        }

        // if we are here, we require to move messages
        $newMessageKey = $messageItemService->moveMessage(
            $messageKey,
            new FolderKey($mailAccountId, $newMailFolderId)
        );

        if ($newMessageKey) {
            $item = $messageItemService->getListMessageItem($newMessageKey);

            return response()->json([
                "success" => true,
                "data"    => $item->toJson()
            ]);
        } else {
            return response()->json([
                "success" => false,
                "msg"     => "Could not move the message."
            ], 500);
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
     * @return JsonResponse
     */
    public function post(Request $request, string $mailAccountId, string $mailFolderId): JsonResponse
    {
        $user = Auth::user();

        $messageItemService = $this->messageItemService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount        = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);
        $folderKey = new FolderKey($mailAccount, $mailFolderId);

        $data = ArrayUtil::only(ArrayUtil::unchain("data.attributes", $request->all()), ["textHtml", "textPlain"]);

        $messageBody             = $this->messageBodyDraftJsonTransformer::fromArray($data);
        $createdMessageBodyDraft = $messageItemService->createMessageBodyDraft($folderKey, $messageBody);

        if (!$createdMessageBodyDraft) {
            return response()->json([
                "success" => false,
                "msg"     => "Creating the MessageBody failed."
            ], 400);
        }

        return response()->json([
            "success" => !!$createdMessageBodyDraft ,
            "data"    => $createdMessageBodyDraft->toJson()
        ]);
    }


    /**
     * Sends the Draft identified by the POST-parameters "mailAccountId",
     * "mailFolderId" and "id".
     *
     * @param Request $request
     * @param string $mailAccountId
     * @param string $mailFolderId
     * @param string $messageItemId
     *
     * @return JsonResponse
     */
    public function sendMessageDraft(
        Request $request,
        string $mailAccountId,
        string $mailFolderId,
        string $messageItemId
    ): JsonResponse {
        $user = Auth::user();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount = $user->getMailAccount($mailAccountId);

        $messageItemService = $this->messageItemService;

        $messageKey = new MessageKey($mailAccount, urldecode($mailFolderId), $messageItemId);

        $status = $messageItemService->sendMessageDraft($messageKey);

        if (!$status) {
            return response()->json([
                "success" => false,
                "msg"     => "Sending the message failed."
            ], 400);
        }

        return response()->json([
           "success" => true
        ]);
    }
}
