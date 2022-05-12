<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App\Http\V0\Controllers;

use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use Conjoon\Mail\Client\Service\DefaultAttachmentService;
use App\Http\V0\Controllers\AttachmentController;
use Conjoon\Mail\Client\Attachment\FileAttachmentItem;
use Conjoon\Mail\Client\Service\AttachmentService;
use Conjoon\Mail\Client\Attachment\FileAttachmentItemList;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Exception;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Tests for AttachmentController.
 */
class AttachmentControllerTest extends TestCase
{
    use TestTrait;

    /**
     * Tests index() to make sure method returns list of available Attachments
     * for the specified keys.
     *
     * @return void
     */
    public function testIndexSuccess()
    {
        $service = $this->getMockBuilder(DefaultAttachmentService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(AttachmentController::class)
            ->needs(AttachmentService::class)
            ->give(function () use ($service) {

                return $service;
            });

        $urlencodedMaiLFolderId = "INBOX.Sent%20Messages";

        $messageKey = new MessageKey("dev", $urlencodedMaiLFolderId, "123");

        $resultList   = new FileAttachmentItemList();
        $resultList[] = new FileAttachmentItem(
            new AttachmentKey("dev", $urlencodedMaiLFolderId, "123", "1"),
            [
                "size" => 0,
                "type" => "text/plain",
                "text"  => "file",
                "downloadUrl" => "",
                "previewImgSrc" => ""
            ]
        );

        $service->expects($this->once())
            ->method("getFileAttachmentItemList")
            ->with(
                $this->callback(function ($key) use ($messageKey) {
                    return $key->getMailFolderId() === urldecode($messageKey->getMailFolderId());
                })
            )
            ->willReturn($resultList);


        $actor = $this->actingAs($this->getTestUserStub());
        $endpoint = $this->getImapEndpoint(
            "MailAccounts/dev/MailFolders/$urlencodedMaiLFolderId/MessageItems/123/Attachments",
            "v0"
        );
        $response = $actor->call("GET", $endpoint);

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $resultList->toJson()
        ]);
    }


    /**
     * Tests posts for creating attachments.
     *
     * @return void
     */
    public function testPostSuccess()
    {
        $service = $this->getMockBuilder(DefaultAttachmentService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $messageKey = new MessageKey("dev", "INBOX", "123");

        $fakeFiles = [
            ["blob" => UploadedFile::fake()->image("new file.txt(1)")]
        ];

        $orgFiles = [
            [
                "mailAccountId" => "dev",
                "mailFolderId" => "INBOX",
                "parentMessageItemId" => "123",
                "type" => "text/plain",
                "text" => "new file.txt(1)"
            ]
        ];

        $files = [];
        foreach ($orgFiles as $index => $file) {
            $files[$index] = $file;
            $files[$index]["blob"] = $fakeFiles[$index]["blob"];
        }


        $fileAttachmentList = new FileAttachmentList();
        $fileAttachmentItemList = new FileAttachmentItemList();
        $fileAttachmentItem = new FileAttachmentItem(new AttachmentKey($messageKey, "1"), [
            "downloadUrl" => "url",
            "previewImgSrc" => "src",
            "type" => "text/plain",
            "text" => "new file.txt(1)",
            "size" => 0
        ]);
        $fileAttachmentItemList[] = $fileAttachmentItem;

        $attachmentTransformer = $this->initAttachmentListJsonTransformer();
        $transformedAttachmentData = $attachmentTransformer->returnAttachmentListForData(
            $files,
            $fileAttachmentList
        );

        $this->app->when(AttachmentController::class)
            ->needs(AttachmentService::class)
            ->give(function () use ($service) {
                return $service;
            });

        $service->expects($this->once())
            ->method("createAttachments")
            ->with($messageKey, $transformedAttachmentData)
            ->willReturn($fileAttachmentItemList);


        $this->actingAs($this->getTestUserStub())
            ->call(
                "POST",
                $this->getImapEndpoint(
                    "MailAccounts/dev/MailFolders/INBOX/MessageItems/123/Attachments",
                    "v0"
                ),
                ["files" => $orgFiles],
                [],
                ["files" => $fakeFiles]
            );

        $this->assertResponseOk();

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $fileAttachmentItemList->toJson()
        ]);
    }


    /**
     * Tests delete for deleting attachments.
     *
     * @return void
     */
    public function testDeleteSuccess()
    {
        $service = $this->getMockBuilder(DefaultAttachmentService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $parentMessageItemId = "123";
        $id = "1";

        $attachmentKey = new AttachmentKey($mailAccountId, $mailFolderId, $parentMessageItemId, $id);
        $newMessageKey = new MessageKey($mailAccountId, $mailFolderId, "1234");

        $this->app->when(AttachmentController::class)
            ->needs(AttachmentService::class)
            ->give(function () use ($service) {
                return $service;
            });

        $service->expects($this->once())
            ->method("deleteAttachment")
            ->with($attachmentKey)
            ->willReturn($newMessageKey);


        $this->actingAs($this->getTestUserStub())
            ->call(
                "DELETE",
                $this->getImapEndpoint(
                    "MailAccounts/${mailAccountId}/MailFolders/" .
                    "${mailFolderId}/MessageItems/${parentMessageItemId}/Attachments/${id}",
                    "v0"
                )
            );

        $this->assertResponseOk();

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $newMessageKey->toJson()
        ]);
    }


    /**
     * Tests delete for deleting attachments.
     *
     * @return void
     */
    public function testDeleteFailure()
    {
        $service = $this->getMockBuilder(DefaultAttachmentService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $parentMessageItemId = "123";
        $id = "1";

        $attachmentKey = new AttachmentKey($mailAccountId, $mailFolderId, $parentMessageItemId, $id);
        $newMessageKey = null;

        $this->app->when(AttachmentController::class)
            ->needs(AttachmentService::class)
            ->give(function () use ($service) {
                return $service;
            });

        $service->expects($this->once())
            ->method("deleteAttachment")
            ->with($attachmentKey)
            ->willThrowException(new Exception());


        $this->actingAs($this->getTestUserStub())
            ->call(
                "DELETE",
                $this->getImapEndpoint(
                    "MailAccounts/${mailAccountId}/MailFolders/" .
                    "${mailFolderId}/MessageItems/${parentMessageItemId}/Attachments/${id}",
                    "v0"
                )
            );

        $this->assertResponseStatus(500);
    }


    /**
     * Will return an anonymous class since staticExpects is deprecated.
     * Allows for specifying the object to return in fromArray
     * Registers the anonymous class with the AttachmentController.
     *
     * @return AttachmentListJsonTransformer
     */
    protected function initAttachmentListJsonTransformer()
    {
        $transformer = new class implements AttachmentListJsonTransformer {
            /**
             * @var FileAttachmentList
             */
            protected static FileAttachmentList $attachmentList;

            /**
             * @var int
             */
            protected static int $callCounts = 0;

            /**
             * @var array
             */
            protected static array $expectedData;

            /**
             * @param array $data
             * @param FileAttachmentList $attachmentList
             *
             * @return FileAttachmentList
             */
            public function returnAttachmentListForData(array $data, FileAttachmentList $attachmentList)
            {
                self::$expectedData = $data;
                self::$attachmentList = $attachmentList;

                return $attachmentList;
            }

            /**
             * @param array $arr
             * @return FileAttachmentList|null
             */
            public static function fromString(string $value): FileAttachmentList
            {
                throw new Exception("not implemented");
            }

            /**
             * @param array $arr
             * @return FileAttachmentList|null
             */
            public static function fromArray(array $arr): FileAttachmentList
            {
                if ($arr === self::$expectedData) {
                    return self::$attachmentList;
                }
                throw new Exception("data does not match passed argument");
            }
        };

        $this->app->when(AttachmentController::class)
            ->needs(AttachmentListJsonTransformer::class)
            ->give(function () use ($transformer) {
                return $transformer;
            });

        return $transformer;
    }
}
