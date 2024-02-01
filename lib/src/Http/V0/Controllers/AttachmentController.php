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

namespace App\Http\V0\Controllers;

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use Conjoon\Mail\Client\Service\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AttachmentController
 * @package App\Http\Controllers
 */
class AttachmentController extends Controller
{
    /**
     * @var attachmentService
     */
    protected AttachmentService $attachmentService;

    /**
     * @var AttachmentListJsonTransformer
     */
    protected AttachmentListJsonTransformer $attachmentListJsonTransformer;


    /**
     * AttachmentController constructor.
     *
     * @param AttachmentService $attachmentService
     * @param AttachmentListJsonTransformer $attachmentListJsonTransformer
     */
    public function __construct(
        AttachmentService $attachmentService,
        AttachmentListJsonTransformer $attachmentListJsonTransformer
    ) {

        $this->attachmentService = $attachmentService;
        $this->attachmentListJsonTransformer = $attachmentListJsonTransformer;
    }


    /**
     * Returns all available Attachments for $mailAccountId, the specified
     * $mailFolderId, and the specified $parentMessageItemId
     *
     * @param Request $request
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $messageItemId
     * @return JsonResponse
     */
    public function index(Request $request, $mailAccountId, $mailFolderId, $messageItemId): JsonResponse
    {
        $user = Auth::user();

        $attachmentService = $this->attachmentService;
        $mailAccount       = $user->getMailAccount($mailAccountId);
        $key               = new MessageKey($mailAccount, urldecode($mailFolderId), $messageItemId);

        return response()->json([
            "success" => true,
            "data"    => $attachmentService->getFileAttachmentItemList($key)->toJson()
        ]);
    }


    /**
     * Posts new attachment data to this controller for adding this attachment to the resource uniquely identified
     * by mailAccountId, maiLFolderId and parentMessageItemId.
     *
     * @param Request $request
     * @param {String} $mailAccountId
     * @param {String} $mailFolderId
     * @param {String} $messageItemId
     *
     * @return JsonResponse
     */
    public function post(Request $request, $mailAccountId, $mailFolderId, $messageItemId): JsonResponse
    {
        $user = Auth::user();
        $attachmentService = $this->attachmentService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount       = $user->getMailAccount($mailAccountId);
        $key               = new MessageKey($mailAccount, $mailFolderId, $messageItemId);

        $postedData = $request->get("files");
        foreach ($postedData as $index => $req) {
            $postedData[$index] += $request->file("files")[$index];
        }

        $attachments = $this->attachmentListJsonTransformer->fromArray($postedData);
        $attachmentItemList = $attachmentService->createAttachments($key, $attachments);

        return response()->json([
            "success" => true,
            "data"    => $attachmentItemList->toJson()
        ]);
    }


    /**
     * Deletes the attachment uniquely identified by $mailAccountId, $mailFolderId, $messageItemId
     * and $attachmentId.
     *
     * @param Request $request
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $messageItemId
     * @param $id
     *
     * @return JsonResponse
     */
    public function delete(
        Request $request,
        $mailAccountId,
        $mailFolderId,
        $messageItemId,
        $id
    ): JsonResponse {

        $user = Auth::user();

        $attachmentService = $this->attachmentService;
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $mailAccount = $user->getMailAccount($mailAccountId);

        $mailFolderId = urldecode($mailFolderId);

        $attachmentKey = new AttachmentKey($mailAccount, $mailFolderId, $messageItemId, $id);

        $messageKey = $attachmentService->deleteAttachment($attachmentKey);


        return response()->json([
            "success" => !!$messageKey,
            "data" => $messageKey ?  $messageKey->toJson() : null
        ], $messageKey ? 200 : 500);
    }
}
