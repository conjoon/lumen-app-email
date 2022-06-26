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

namespace Tests\App\Http\V0\Controllers;

use App\ControllerUtil;
use App\Http\V0\Controllers\Controller;
use App\Http\V0\Controllers\MessageItemController;
use App\Http\V0\Query\MessageItem\GetRequestQueryTranslator;
use App\Http\V0\Query\MessageItem\IndexRequestQueryTranslator;
use Conjoon\Core\JsonStrategy;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Exception\MailFolderNotFoundException;
use Conjoon\Mail\Client\Folder\MailFolder;
use Conjoon\Mail\Client\Folder\MailFolderChildList;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlaggedFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\SeenFlag;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Query\MailFolderListResourceQuery;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Service\DefaultMailFolderService;
use Conjoon\Mail\Client\Service\DefaultMessageItemService;
use Conjoon\Mail\Client\Service\MailFolderService;
use Conjoon\Mail\Client\Service\MessageItemService;
use Conjoon\Util\ArrayUtil;
use Exception;
use App\Util;
use Illuminate\Http\Request;
use Conjoon\Core\ParameterBag;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Class MessageItemControllerTest
 * @package Tests\App\Http\V0\Controllers
 */
class MessageItemControllerTest extends TestCase
{
    use TestTrait;

    protected string $messageItemsUrl = "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311";

    /**
     * Inits the server stubs to make sure service stubs intercept calls to not accidently
     * authorize with the application.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $serviceStub = $this->initServiceStub();
        $mailFolderService = $this->initMailFolderServiceStub();
    }


    /**
     * @return void
     */
    public function testClass()
    {
        $ctrl = $this->getMockBuilder(MessageItemController::class)->disableOriginalConstructor()->getMock();

        $this->assertInstanceOf(Controller::class, $ctrl);
    }


    /**
     * Tests index() to make sure method returns list of available MessageItems associated with
     * the current signed-in user.
     *
     *
     * @return void
     */
    public function testIndexSuccess()
    {
        $serviceStub = $this->serviceStub;
        $mailFolderService = $this->mailFolderServiceStub;

        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $unreadCmp = 5;
        $totalCmp = 100;

        $account = $this->getTestMailAccount("dev_sys_conjoon_org");

        $folderKey = new FolderKey($account, "INBOX");

        $query = (new IndexRequestQueryTranslator())->translate(new Request(
            [
                "fields[MailFolder]" => "unreadMessages,totalMessages",
                "include" => "MailFolder",
                "start" => 0,
                "limit" => 25,
                "sort" => [
                    ["property" => "date", "direction" => "DESC"]
                ]
            ]
        ));

        $mailFolderResourceQuery = new MailFolderListResourceQuery(new ParameterBag(
            ["fields" => ["MailFolder" => ["unreadMessages" => true, "totalMessages" => true]]]
        ));

        $messageItemList = new MessageItemList();
        $messageItemList[] = new ListMessageItem(
            new MessageKey($folderKey, "232"),
            [],
            new MessagePart("", "", "")
        );
        $messageItemList[] = new ListMessageItem(
            new MessageKey($folderKey, "233"),
            [],
            new MessagePart("", "", "")
        );

        $resultList   = new MailFolderChildList();
        $resultList[] = new MailFolder(
            $folderKey,
            ["unreadMessages" => $unreadCmp, "totalMessages" => $totalCmp, "data" => null]
        );


        $serviceStub->expects($this->once())
            ->method("getMessageItemList")
            ->with(
                $folderKey,
                $this->callback(
                    function ($rq) use ($query) {
                        $this->assertEquals($query->toJson(), $rq->toJson());
                        return true;
                    }
                )
            )
            ->willReturn($messageItemList);

        $mailFolderService->expects($this->once())
            ->method("getMailFolderChildList")
            ->with(
                $account,
                $this->callback(
                    function ($rq) use ($mailFolderResourceQuery) {
                        $this->assertEquals($mailFolderResourceQuery->toJson(), $rq->toJson());
                        return true;
                    }
                )
            )
            ->willReturn($resultList);

        $endpoint = $this->getImapEndpoint(
            "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems",
            "v0"
        );
        $client = $this->actingAs($this->getTestUserStub());

        $response = $client->call(
            "GET",
            $endpoint,
            ["start" => 0, "limit" => 25, "sort" => [["property" => "date", "direction" => "DESC"]],
            "fields[MailFolder]" => "unreadMessages,totalMessages",
            "include" => "MailFolder"]
        );


        $this->assertSame($response->status(), 200);

        $this->seeJsonEquals([
            "data" => $messageItemList->toJson($this->app->get(JsonStrategy::class)),
            "included" => $resultList->toJson($this->app->get(JsonStrategy::class))
        ]);
    }

    /**
     * Http 400
     */
    public function testIndex400()
    {
        $serviceStub = $this->serviceStub;
        $mailFolderService = $this->mailFolderServiceStub;

        $response = $this->actingAs($this->getTestUserStub())
            ->call(
                "GET",
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/_missing_/MessageItems?limit=-1&include=mail",
                    "v0"
                )
            );

        $this->assertEquals(400, $response->status());
    }


    /**
     * Http 401
     */
    public function testIndex401()
    {
        $serviceStub = $this->serviceStub;
        $mailFolderService = $this->mailFolderServiceStub;

        $this->runTestForUnauthorizedAccessTo(
            "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems",
            "GET"
        );
    }


    /**
     * Http 404
     */
    public function testIndex404()
    {
        $serviceStub = $this->serviceStub;
        $mailFolderService = $this->mailFolderServiceStub;


        $serviceStub->expects($this->once())
            ->method("getMessageItemList")
            ->with(
                new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "_missing_"),
                $this->anything()
            )
            ->willThrowException(new MailFolderNotFoundException("The MailFolder with the id \"_missing_\" was not found"));

        $response = $this->actingAs($this->getTestUserStub())
            ->call(
                "GET",
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/_missing_/MessageItems?limit=-1",
                    "v0"
                )
            );

        $this->assertEquals(404, $response->status());
    }


    /**
     * Tests post() to make sure creating a Message with a MessageItemDraft
     * works as expected
     *
     *
     * @return void
     */
    public function testPostMessageItem()
    {
        $serviceStub = $this->initServiceStub();
        $transformer = $this->initMessageItemDraftJsonTransformer();
        $folderKey = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $messageKey = new MessageKey($folderKey, "101");

        $attributes = [
            "subject" => "new subject"
        ];
        $requestData = [
            "data" => [
                "type" => "MessageItem",
                "attributes" => $attributes
            ]
        ];

        $messageDraft = new MessageItemDraft($attributes);
        $transformer->returnDraftForData($attributes, $messageDraft);

        $createdMessage = $messageDraft->setMessageKey($messageKey);

        $serviceStub->expects($this->once())
            ->method("createMessageDraft")
            ->with($folderKey, $messageDraft)
            ->will($this->returnCallback(function ($folderKey, $messageDraft) use ($createdMessage) {
                return $createdMessage;
            }));

        $utilMock = $this->getMockBuilder(ControllerUtil::class)->onlyMethods(["getResourceUrl"])->getMock();
        $this->app->when(MessageItemController::class)
            ->needs(ControllerUtil::class)
            ->give(function () use ($utilMock) {
                return $utilMock;
            });

        $utilMock->expects($this->once())->method("getResourceUrl")->with(
            "MessageItem",
            $messageKey,
            $this->callback(function ($url) {
                $this->assertSame(
                    "http://localhost/rest-api-email/api/v0/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems",
                    $url
                );
                return true;
            })
        )->willReturn("location value");


        $response = $this->actingAs($this->getTestUserStub())
            ->post(
                $this->getImapEndpoint("MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems", "v0"),
                $requestData
            );

        $response->assertResponseStatus(201);
        $response->seeHeader("Location", "location value");


        $response->seeJsonEquals([
           "data" => $createdMessage->toJson($this->app->get(JsonStrategy::class))
        ]);
    }


    /**
     * Tests post() to make sure response is okay when no MessageBody as created.
     *
     * @return void
     */
    public function testPostMessageBodyNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $transformer = $this->initMessageBodyDraftJsonTransformer();

        $folderKey = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $textHtml = "HTML";
        $textPlain = "PLAIN";

        $data = ["textHtml" => $textHtml, "textPlain" => $textPlain];

        $requestData = [
            "data" => [
                "mailAccountId" => "dev_sys_conjoon_org",
                "mailFolderId" => "INBOX",
                "attributes" => $data
            ]
        ];


        $messageBody = new MessageBodyDraft();
        $messageBody->setTextHtml(new MessagePart($textHtml, "UTF-8", "text/html"));
        $messageBody->setTextPlain(new MessagePart($textPlain, "UTF-8", "text/plain"));

        $transformer->returnDraftForData($data, $messageBody);

        $serviceStub->expects($this->once())
            ->method("createMessageBodyDraft")
            ->with($folderKey, $messageBody)
            ->willReturn(null);

        $this->actingAs($this->getTestUserStub())
            ->post(
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems",
                    "v0"
                ),
                $requestData
            );

        $this->assertResponseStatus(400);

        $this->seeJsonContains([
            "success" => false,
            "msg" => "Creating the MessageBody failed."
        ]);
    }


// +-------------------------------
// | pre php-lib-conjoon#8
// +-------------------------------
    public function testGetMessageItemNoTargetParameter()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();


        $folderKey = new FolderKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX"
        );

        $request = new Request(["attributes" => "*"]);
        $request->setRouteResolver(function () {
            return new class {
                public function parameter()
                {
                    return "744";
                }
            };
        });

        $query = (new GetRequestQueryTranslator())->translate($request);

        $messageItemList = new MessageItemList();
        $messageItemList[] = new ListMessageItem(
            new MessageKey($folderKey, "744"),
            [],
            new MessagePart("", "", "")
        );

        $serviceStub->expects($this->once())
            ->method("getMessageItemList")
            ->with(
                $folderKey,
                $this->callback(
                    function ($rq) use ($query) {
                        $this->assertEquals($query->toJson(), $rq->toJson());
                        return true;
                    }
                )
            )
            ->willReturn($messageItemList);


        $endpoint = $this->getImapEndpoint(
            "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/744",
            "v0"
        );
        $client = $this->actingAs($this->getTestUserStub());

        $response = $client->call(
            "GET",
            $endpoint,
            ["attributes" => "*,preveiwText"]
        );


        $this->assertSame($response->status(), 200);

        $this->seeJsonEquals([
            "success" => true,
            "data" => $messageItemList[0]->toJson()
        ]);
    }


    /**
     * Tests get() to make sure method returns the MessageBody of a Message
     *
     *
     * @return void
     */
    public function testGetMessageBodySuccess()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $messageBody = new MessageBody($messageKey);

        $serviceStub->expects($this->once())
            ->method("getMessageBody")
            ->with($messageKey)
            ->willReturn($messageBody);


        $response = $this->actingAs($this->getTestUserStub())
            ->call("GET", $this->getImapEndpoint(
                "$this->messageItemsUrl/MessageBody",
                "v0"
            ));

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data" => $messageBody->toJson()
        ]);
    }


    /**
     * Tests put() to make sure method relies on proper
     * request payload.
     *
     *
     * @return void
     */
    public function testPutMessageItemBadRequestMissingFlag()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $serviceStub->expects($this->never())
            ->method("setFlags");


        $this->actingAs($this->getTestUserStub())
            ->call("PATCH", $this->getImapEndpoint(
                "$this->messageItemsUrl/MessageItem",
                "v0"
            ));

        $this->assertResponseStatus(400);

        $this->seeJsonContains([
            "success" => false,
            "msg" => "Invalid request payload."
        ]);
    }


    /**
     * Tests put() to make sure setting flag works as expected
     *
     *
     * @return void
     */
    public function testPutMessageItemFlag()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);


        $serviceStub->expects($this->once())
            ->method("setFlags")
            ->with($messageKey, $flagList)
            ->willReturn(true);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint("$this->messageItemsUrl", "v0")}/MessageItem",
                ["data" => ["attributes" => ["seen" => true]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data" => array_merge($messageKey->toJson(), [
                "seen" => true
            ])
        ]);
    }


    /**
     * Tests put() to make sure setting flag and moving works as expected
     *
     *
     * @return void
     */
    public function testPutMessageItemFlagAndMove()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $toFolderId = "ut";
        $folderKey = new FolderKey("dev_sys_conjoon_org", $toFolderId);
        $messageKey = new MessageKey("dev_sys_conjoon_org", "INBOX", "311");
        $newMessageKey = new MessageKey($folderKey, "2");

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(false);

        $listMessageItem = new ListMessageItem($newMessageKey, ["draft" => false], new MessagePart("", "", ""));

        $serviceStub->expects($this->once())
            ->method("setFlags")
            ->with($messageKey, $flagList)
            ->willReturn(true);

        $serviceStub->expects($this->once())
            ->method("moveMessage")
            ->with($messageKey, $folderKey)
            ->willReturn($newMessageKey);

        $serviceStub->expects($this->once())
            ->method("getListMessageItem")
            ->with($newMessageKey)
            ->willReturn($listMessageItem);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint("$this->messageItemsUrl", "v0")}/MessageItem",
                ["data" => ["attributes" => ["draft" => false, "mailFolderId" => $toFolderId]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data" => $listMessageItem->toJson()
        ]);
    }


    /**
     * Tests put() to make sure setting flag and moving works as expected
     *
     *
     * @return void
     */
    public function testPutMessageItemMoveSameFolderId()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $toFolderId = "ut";

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(false);


        $serviceStub->expects($this->never())
            ->method("setFlags");

        $serviceStub->expects($this->never())
            ->method("moveMessage");

        $serviceStub->expects($this->never())
            ->method("getListMessageItem");

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/$toFolderId/MessageItems/311/MessageItem",
                    "v0"
                ),
                ["data" => ["attributes" => ["draft" => false, "mailFolderId" => $toFolderId]]]
            );

        $response->assertResponseStatus(400);

        $response->seeJsonEquals([
            "success" => false,
            "msg" => "Cannot move message since it already belongs to this folder."
        ]);
    }


    /**
     * Tests put() to make sure move works (without flag)
     *
     *
     * @return void
     */
    public function testPutMessageItemMoveNoFlag()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $toFolderId = "TRASH";
        $folderKey = new FolderKey("dev_sys_conjoon_org", $toFolderId);
        $messageKey = new MessageKey("dev_sys_conjoon_org", "INBOX", "123");
        $newMessageKey = new MessageKey("dev_sys_conjoon_org", $toFolderId, "5");


        $serviceStub->expects($this->never())
            ->method("setFlags");

        $serviceStub->expects($this->once())
            ->method("moveMessage")
            ->with($messageKey, $folderKey)
            ->willReturn($newMessageKey);

        $listMessageItem = new ListMessageItem($newMessageKey, [], new MessagePart("", "", ""));
        $serviceStub->expects($this->once())
            ->method("getListMessageItem")
            ->with($newMessageKey)
            ->willReturn($listMessageItem);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/123/MessageItem",
                    "v0"
                ),
                ["data" => ["attributes" => ["mailFolderId" => $toFolderId]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data" => $listMessageItem->toJson()
        ]);
    }


    /**
     * Tests put() move with moving failed
     *
     *
     * @return void
     */
    public function testPutMessageItemMoveFail()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $toFolderId = "TRASH";
        $folderKey = new FolderKey("dev_sys_conjoon_org", $toFolderId);
        $messageKey = new MessageKey("dev_sys_conjoon_org", "INBOX", "123");

        $serviceStub->expects($this->never())
            ->method("setFlags");

        $serviceStub->expects($this->once())
            ->method("moveMessage")
            ->with($messageKey, $folderKey)
            ->willReturn(null);

        $serviceStub->expects($this->never())
            ->method("getListMessageItem");

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                $this->getImapEndpoint(
                    "MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/123/MessageItem",
                    "v0"
                ),
                ["data" => ["attributes" => ["mailFolderId" => $toFolderId]]]
            );

        $response->assertResponseStatus(500);

        $response->seeJsonEquals([
            "success" => false,
            "msg" => "Could not move the message."
        ]);
    }


    /**
     * Tests put() to make sure setting flags (seen / flagged) works as expected
     *
     *
     * @return void
     */
    public function testPutMessageItemAllFlags()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);
        $flagList[] = new FlaggedFlag(false);


        $serviceStub->expects($this->once())
            ->method("setFlags")
            ->with($messageKey, $flagList)
            ->willReturn(true);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint("$this->messageItemsUrl", "v0")}/MessageItem",
                ["data" => ["attributes" => ["seen" => true, "flagged" => false]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data" => array_merge($messageKey->toJson(), [
                "seen" => true,
                "flagged" => false
            ])
        ]);
    }


    /**
     * Tests put() with failed Service
     *
     *
     * @return void
     */
    public function testPutMessageItemServiceFail()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new FlaggedFlag(false);


        $serviceStub->expects($this->once())
            ->method("setFlags")
            ->with($messageKey, $flagList)
            ->willReturn(false);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint("$this->messageItemsUrl", "v0")}/MessageItem",
                ["data" => ["attributes" => ["flagged" => false]]]
            );

        $response->assertResponseStatus(500);

        $response->seeJsonEquals([
            "success" => false,
            "data" => array_merge($messageKey->toJson(), [
                "flagged" => false
            ])
        ]);
    }


    /**
     * Tests put() to make sure setting MessageDraft-data works as expected
     *
     * @return void
     */
    public function testPutMessageDraftData()
    {
        $serviceStub = $this->initServiceStub();
        $transformer = $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "311"
        );
        $newMessageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "abc"
        );

        $to = json_encode(["address" => "dev@conjoon.org"]);
        $data = [
            "subject" => "Hello World!",
            "to" => $to,
            "references" => "ref",
            "inReplyTo" => "irt",
            "xCnDraftInfo" => "info"
        ];


        $transformDraft = new MessageItemDraft($messageKey);
        $messageItemDraft = new MessageItemDraft($newMessageKey, ["messageId" => "foo"]);

        $transformer->returnDraftForData(array_merge($data, $messageKey->toJson()), $transformDraft);

        $serviceStub->expects($this->once())
            ->method("updateMessageDraft")
            ->with($transformDraft)
            ->willReturn($messageItemDraft);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                $this->getImapEndpoint(
                    "$this->messageItemsUrl/MessageDraft",
                    "v0"
                ),
                ["data" => ["attributes" => $data]]
            );

        $response->assertResponseOk();

        $set = array_merge(
            ["subject", "to", "messageId", "inReplyTo", "references", "xCnDraftInfo"],
            array_keys($newMessageKey->toJson())
        );

        $response->seeJsonEquals([
            "success" => true,
            "data"    => ArrayUtil::only($messageItemDraft->toJson(), $set)
        ]);
    }


    /**
     * Tests put() to make sure response is okay when no MessageDraft was created.
     *
     * @return void
     */
    public function testPutMessageDraftNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $transformer = $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "311"
        );

        $transformDraft = new MessageItemDraft($messageKey);

        $to = json_encode(["address" => "dev@conjoon.org"]);
        $data = [
            "subject" => "Hello World!",
            "to" => $to
        ];

        $transformer->returnDraftForData(array_merge($data, $messageKey->toJson()), $transformDraft);

        $serviceStub->expects($this->once())
            ->method("updateMessageDraft")
            ->with($transformDraft)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint($this->messageItemsUrl, "v0")}/MessageDraft",
                ["data" => ["attributes" => $data]]
            );


        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => false,
            "msg" => "Updating the MessageDraft failed."
        ]);
    }


    /**
     * Tests put() to make sure response is okay when no MessageBodyDraft was created.
     *
     * @return void
     */
    public function testPutMessageBodyDraftNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $transformer = $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "311"
        );

        $transformDraft = new MessageBodyDraft($messageKey);

        $data = [
            "textPlain" => "Hello World!",
            "mailAccountId" => "dev_sys_conjoon_org",
            "mailFolderId" => "INBOX",
            "id" => "311"
        ];

        $transformer->returnDraftForData($data, $transformDraft);

        $serviceStub->expects($this->once())
            ->method("updateMessageBodyDraft")
            ->with($transformDraft)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint($this->messageItemsUrl, "v0")}/MessageBody",
                ["data" => ["attributes" => ["textPlain" => "Hello World!"]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => false,
            "msg" => "Updating the MessageBodyDraft failed."
        ]);
    }


    /**
     * Tests put() to make sure setting MessageBodyDraft-data works as expected
     *
     *
     * @return void
     */
    public function testPutMessageBodyDraftData()
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $transformer = $this->initMessageBodyDraftJsonTransformer();

        $messageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "311"
        );
        $newMessageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "INBOX",
            "abc"
        );

        $data = [
            "textHtml" => "Hello World!",
            "mailAccountId" => "dev_sys_conjoon_org",
            "mailFolderId" => "INBOX",
            "id" => "311"
        ];

        $transformDraft = new MessageBodyDraft($messageKey);
        $messageBodyDraft = new MessageBodyDraft($newMessageKey);

        $transformer->returnDraftForData($data, $transformDraft);

        $serviceStub->expects($this->once())
            ->method("updateMessageBodyDraft")
            ->with($transformDraft)
            ->willReturn($messageBodyDraft);

        $response = $this->actingAs($this->getTestUserStub())
            ->patch(
                "{$this->getImapEndpoint($this->messageItemsUrl, "v0")}/MessageBody",
                ["data" => ["attributes" => ["textHtml" => "Hello World!"]]]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data" => $messageBodyDraft->toJson()
        ]);
    }


    /**
     * Tests sendMessageDraft() to make sure sending a MessageDraft works as expected
     *
     *
     * @return void
     */
    public function testPostSendMessageDraft()
    {
        $messageKey = new MessageKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"),
            "Entwürfe",
            "311"
        );

        $requestData = [
            "mailAccountId" => $messageKey->getMailAccountId(),
            "mailFolderId" => "Entw%C3%BCrfe",
            "id" => $messageKey->getId()
        ];

        $testSend = function (bool $expected) use ($messageKey, $requestData) {

            $serviceStub = $this->initServiceStub();

            $serviceStub->expects($this->once())
                ->method("sendMessageDraft")
                ->with($messageKey)
                ->willReturn($expected);

            $response = $this->actingAs($this->getTestUserStub())
                ->post(
                    $this->getImapEndpoint(
                        "MailAccounts/" .
                                $requestData["mailAccountId"] .
                                "/MailFolders/" .
                                $requestData["mailFolderId"] .
                                "/MessageItems/" .
                        $requestData["id"],
                        "v0"
                    )
                );

            if ($expected === true) {
                $response->assertResponseOk();
                $response->seeJsonEquals([
                    "success" => $expected,
                ]);
            } else {
                $this->assertResponseStatus(400);
                $response->seeJsonEquals([
                    "success" => $expected,
                    "msg" => "Sending the message failed."
                ]);
            }
        };

        $testSend(true);
        $testSend(false);
    }


    /**
     * Tests delete() w/ 500
     *
     *
     * @return void
     */
    public function testDeleteMessageItem500()
    {
        $this->deleteMessageItemTest(false);
    }


    /**
     * Tests delete() w/ 200
     *
     *
     * @return void
     */
    public function testDeleteMessageItem200()
    {
        $this->deleteMessageItemTest(true);
    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @param mixed $type bool|string=missing
     */
    protected function deleteMessageItemTest($type)
    {
        $serviceStub = $this->initServiceStub();
        $this->initMessageItemDraftJsonTransformer();
        $this->initMessageBodyDraftJsonTransformer();

        $serviceStub->expects($this->once())
            ->method("deleteMessage")
            ->with(new MessageKey("dev_sys_conjoon_org", "INBOX", "311"))
            ->willReturn($type);

        $this->actingAs($this->getTestUserStub())
            ->call(
                "DELETE",
                $this->getImapEndpoint("$this->messageItemsUrl", "v0")
            );

        $this->assertResponseStatus($type === false ? 500 : 200);

        $this->seeJsonContains([
            "success" => $type
        ]);
    }


    /**
     * Will return an anonymous class since staticExpects is deprecated.
     * Allows for specifying the object to return in fromArray
     * Registers the anonymous class with the MessageItemController.
     *
     * @return DefaultMessageItemDraftJsonTransformer
     */
    protected function initMessageItemDraftJsonTransformer()
    {
        $transformer = new class extends DefaultMessageItemDraftJsonTransformer {
            /**
             * @var MessageItemDraft
             */
            protected static MessageItemDraft $draft;

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
             * @param MessageItemDraft $draft
             */
            public function returnDraftForData(array $data, MessageItemDraft $draft)
            {
                self::$expectedData = $data;
                self::$draft = $draft;
            }

            /**
             * @param array $arr
             * @return MessageItemDraft|null
             */
            public static function fromArray(array $arr): MessageItemDraft
            {
                ksort($arr);
                ksort(self::$expectedData);

                if ($arr === self::$expectedData) {
                    return self::$draft;
                }
                throw new Exception("data does not match passed argument");
            }
        };

        $this->app->when(MessageItemController::class)
            ->needs(MessageItemDraftJsonTransformer::class)
            ->give(function () use ($transformer) {
                return $transformer;
            });

        return $transformer;
    }


    /**
     * Will return an anonymous class since staticExpects is deprecated.
     * Allows for specifying the object to return in fromArray.
     * Registers the anonymous class with the MessageItemController.
     *
     * @return DefaultMessageBodyDraftJsonTransformer
     */
    protected function initMessageBodyDraftJsonTransformer()
    {
        $transformer = new class extends DefaultMessageBodyDraftJsonTransformer {
            /**
             * @var MessageBodyDraft
             */
            protected static MessageBodyDraft $draft;

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
             * @param MessageBodyDraft $draft
             */
            public function returnDraftForData(array $data, MessageBodyDraft $draft)
            {
                self::$expectedData = $data;
                self::$draft = $draft;
            }

            /**
             * @param array $arr
             * @return MessageBodyDraft|null
             */
            public static function fromArray(array $arr): MessageBodyDraft
            {
                ksort($arr);
                ksort(self::$expectedData);

                if ($arr === self::$expectedData) {
                    return self::$draft;
                }
                throw new Exception("data does not match passed argument");
            }
        };

        $this->app->when(MessageItemController::class)
            ->needs(MessageBodyDraftJsonTransformer::class)
            ->give(function () use ($transformer) {
                return $transformer;
            });

        return $transformer;
    }


    /**
     * @return DefaultMessageItemService|MockObject
     */
    protected function initServiceStub()
    {

        $serviceStub = $this->getMockBuilder(DefaultMessageItemService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(MessageItemController::class)
            ->needs(MessageItemService::class)
            ->give(function () use ($serviceStub) {
                return $serviceStub;
            });

        $this->serviceStub = $serviceStub;

        return $serviceStub;
    }

    /**
     * @return DefaultMailFolderService|MockObject
     */
    protected function initMailFolderServiceStub()
    {

        $serviceStub = $this->getMockBuilder(DefaultMailFolderService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(MessageItemController::class)
            ->needs(MailFolderService::class)
            ->give(function () use ($serviceStub) {
                return $serviceStub;
            });

        $this->mailFolderServiceStub = $serviceStub;
        return $serviceStub;
    }

    protected function getIndexRequestQueryTranslator(): IndexRequestQueryTranslator
    {
        return new IndexRequestQueryTranslator();
    }
}
