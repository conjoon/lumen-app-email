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

namespace Tests;

use App\ControllerUtil;
use Closure;
use Conjoon\Core\Contract\JsonStrategy;
use Conjoon\Data\Resource\ObjectDescription;
use Conjoon\Horde_Imap\Client\HordeClient;
use Conjoon\Horde_Imap\Client\SortInfoStrategy;
use Conjoon\Horde_Mime\Composer\HordeAttachmentComposer;
use Conjoon\Horde_Mime\Composer\HordeBodyComposer;
use Conjoon\Horde_Mime\Composer\HordeHeaderComposer;
use Conjoon\Illuminate\Auth\Imap\DefaultImapUserProvider;
use Conjoon\Illuminate\MailClient\Data\Protocol\Http\Request\Transformer\LaravelAttachmentListJsonTransformer;
use Conjoon\JsonApi\Query\Validation\Validator;
use Conjoon\JsonApi\Request as JsonApiRequest;
use Conjoon\JsonApi\Resource\ResourceList;
use Conjoon\MailClient\Data\MailAccount;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\AttachmentListJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\DefaultMessageBodyDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Response\JsonApiStrategy;
use Conjoon\MailClient\Data\Protocol\Imap\Util\DefaultFolderIdToTypeMapper;
use Conjoon\MailClient\Data\Reader\ReadableMessagePartContentProcessor;
use Conjoon\MailClient\Data\Writer\WritableMessagePartContentProcessor;
use Conjoon\MailClient\Folder\Tree\DefaultMailFolderTreeBuilder;
use Conjoon\MailClient\Message\Attachment\Processor\InlineDataProcessor;
use Conjoon\MailClient\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\MailClient\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\MailClient\Service\AttachmentService;
use Conjoon\MailClient\Service\AuthService;
use Conjoon\MailClient\Service\DefaultAttachmentService;
use Conjoon\MailClient\Service\DefaultAuthService;
use Conjoon\MailClient\Service\DefaultMailFolderService;
use Conjoon\MailClient\Service\DefaultMessageItemService;
use Conjoon\MailClient\Service\MailFolderService;
use Conjoon\MailClient\Service\MessageItemService;
use Illuminate\Auth\ImapUserProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Router;
use ReflectionClass;
use ReflectionException;

/**
 * Class VariousTest
 * @package Tests
 *
 * @method getMockBuilder(string $string)
 * @method callback(Closure $param)
 * @method returnCallback(Closure $param)
 */
class VariousTest extends TestCase
{
    use TestTrait;

    protected bool $useFakeAuth = false;

    /**
     * We are currently on...
     *
     * @return void
     */
    public function testConfig()
    {
        $this->assertEquals(["v0"], config("app.api.versions"));
        $this->assertSame("v0", config("app.api.latest"));

        $this->assertEqualsCanonicalizing([
            ["regex" => "/(MailAccounts)(\/)?[^\/]*$/m", "nameIndex" => 1, "singleIndex" => 2],
            ["regex" => "/MailAccounts\/.+\/MailFolders\/.+\/(MessageBodies)(\/*.*$)/m", "nameIndex" => 1, "singleIndex" => 2],
            ["regex" => "/MailAccounts\/.+\/MailFolders\/.+\/(MessageItems)(\/*.*$)/m", "nameIndex" => 1, "singleIndex" => 2],
            ["regex" => "/MailAccounts\/.+\/(MailFolders)(\/)?[^\/]*$/m", "nameIndex" => 1, "singleIndex" => 2],
        ], config("app.api.resourceUrls"));

        $this->assertSame("/\/(v[0-9]+)/mi", config("app.api.versionRegex"));
        $this->assertSame(
            "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}",
            config("app.api.resourceDescriptionTpl")
        );

        $this->assertSame([
            "urlPatterns" => [
                "MessageItem" => [
                    "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItem}",
                    "collection" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems",
                ],
                "MessageBody" => [
                    "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageBodies/{messageItem}",
                    "collection" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageBodies",
                ],
                "MailFolder" => [
                    "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}",
                    "collection" => "MailAccounts/{mailAccountId}/MailFolders",
                ],
                "MailAccount" => [
                    "single" => "MailAccounts/{mailAccountId}",
                    "collection" => "MailAccounts",
                ]
            ],
            "repositoryPatterns" => [
                "validations" => [
                    "single" => "App\\Http\\{apiVersion}\\JsonApi\\Query\\Validation\\{0}Validator",
                    "collection" => "App\\Http\\{apiVersion}\\JsonApi\\Query\\Validation\\{0}CollectionValidator"
                ],
                "descriptions" =>  [
                    "single" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}",
                    "collection" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}List",
                ]
            ]
        ], config("app.api.resourceTpl"));
    }

    /**
     * Get information and validate registered middleware for the app.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testMiddleware()
    {
        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getMethod("gatherMiddlewareClassNames");
        $property->setAccessible(true);

        $versions = config("app.api.versions");
        $this->assertGreaterThan(0, $versions);
        foreach ($versions as $version) {
            $version = ucfirst($version);
            $ret = $property->invokeArgs($this->app, ["auth_" . $version]);
            $this->assertSame($ret[0], "App\Http\\" . ucfirst($version) . "\Middleware\Authenticate");
        }
    }


    /**
     * Test all routes based on the api version.
     * This test should be probably refactored later on if the resource locations change,
     * or endpoints get added in a newer version, to a "V*"-testcase ("V0Test.php, V1Test.php...).
     *
     */
    public function testRoutes()
    {
        $routes = $this->app->router->getRoutes();

        $versions = config("app.api.versions");
        $latest   = config("app.api.latest");
        $messageItemsEndpoint = "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems";
        $this->assertGreaterThan(0, $versions);

        $versions[] = "latest";
        $this->assertGreaterThan(1, $versions);


        foreach ($versions as $version) {

            $this->assertArrayHasKey("POST/" . $this->getImapUserEndpoint("auth", $version), $routes);

            $testAuthsFor = [
                "GET/" . $this->getImapEndpoint("MailAccounts", $version),
                "GET/" . $this->getImapEndpoint("MailAccounts/{mailAccountId}/MailFolders", $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint, $version),
                "POST/" . $this->getImapEndpoint($messageItemsEndpoint, $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageBody", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageBody", $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "POST/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageItem", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageDraft", $version),
                "DELETE/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "GET/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments",
                    $version
                ),
                "POST/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments",
                    $version
                ),
                "DELETE/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments/{id}",
                    $version
                )
            ];

            foreach ($testAuthsFor as $route) {
                $this->assertArrayHasKey($route, $routes);

                // "latest"-string will fall back to the current version being used
                $postfix = $version === "latest" ? ucfirst($latest) : ucfirst($version);
                $this->assertSame("auth_" . $postfix, $routes[$route]["action"]["middleware"][0]);
            }
        }
    }


    /**
     *
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function testConcretes()
    {


        $userStub = $this->getTemplateUserStub(["getMailAccount"]);
        $userStub->method("getMailAccount")
            ->with(null)
            ->willReturn($this->getTestMailAccount("dev_sys_conjoon_org"));
        $this->app["auth"]->setUser($userStub);

        config(["imapserver" => ["mock" => "default"]]);
        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getMethod("getConcrete");
        $property->setAccessible(true);


        $this->assertInstanceOf(
            DefaultImapUserProvider::class,
            $this->app->build($property->invokeArgs($this->app, [ImapUserProvider::class]))
        );

        $this->assertInstanceOf(
            DefaultMessageItemDraftJsonTransformer::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [MessageItemDraftJsonTransformer::class]
            ))
        );

        $this->assertInstanceOf(
            DefaultMessageBodyDraftJsonTransformer::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [MessageBodyDraftJsonTransformer::class]
            ))
        );

        $this->assertInstanceOf(
            DefaultAuthService::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [AuthService::class]
            ))
        );

        $this->assertInstanceOf(
            JsonApiStrategy::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [JsonStrategy::class]
            ))
        );

        $this->assertInstanceOf(
            ControllerUtil::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [ControllerUtil::class]
            ))
        );

        $this->assertInstanceOf(
            LaravelAttachmentListJsonTransformer::class,
            $this->app->build($property->invokeArgs(
                $this->app,
                [AttachmentListJsonTransformer::class]
            ))
        );

        $attachmentService = $this->app->build($property->invokeArgs(
            $this->app,
            [AttachmentService::class]
        ));
        $attachmentServiceMailClient = $attachmentService->getMailClient();
        $this->assertInstanceOf(
            DefaultAttachmentService::class,
            $attachmentService
        );
        $this->assertInstanceOf(
            InlineDataProcessor::class,
            $attachmentService->getFileAttachmentProcessor()
        );

        $mailFolderService = $this->app->build($property->invokeArgs(
            $this->app,
            [MailFolderService::class]
        ));
        $mailFolderServiceMailClient = $mailFolderService->getMailClient();
        $mailFolderTreeBuilder = $mailFolderService->getMailFolderTreeBuilder();
        $folderIdToTypeMapper = $mailFolderTreeBuilder->getFolderIdToTypeMapper();
        $this->assertInstanceOf(
            DefaultMailFolderService::class,
            $mailFolderService
        );
        $this->assertInstanceOf(HordeClient::class, $mailFolderServiceMailClient);
        $this->assertInstanceOf(DefaultMailFolderTreeBuilder::class, $mailFolderTreeBuilder);
        $this->assertInstanceOf(DefaultFolderIdToTypeMapper::class, $folderIdToTypeMapper);

        // sharing the same client
        $this->assertSame($attachmentServiceMailClient, $mailFolderServiceMailClient);

        $messageItemService = $this->app->build($property->invokeArgs(
            $this->app,
            [MessageItemService::class]
        ));
        $this->assertInstanceOf(
            DefaultMessageItemService::class,
            $messageItemService
        );

        $messageItemServiceMailClient = $messageItemService->getMailClient();

        // sharing the same client
        $this->assertSame($messageItemServiceMailClient, $mailFolderServiceMailClient);
        $this->assertInstanceOf(HordeClient::class, $messageItemServiceMailClient);

        $this->assertInstanceOf(
            HordeBodyComposer::class,
            $messageItemServiceMailClient->getBodyComposer()
        );
        $this->assertInstanceOf(
            HordeHeaderComposer::class,
            $messageItemServiceMailClient->getHeaderComposer()
        );
        $this->assertInstanceOf(
            HordeAttachmentComposer::class,
            $messageItemServiceMailClient->getAttachmentComposer()
        );
        $this->assertInstanceOf(
            SortInfoStrategy::class,
            $messageItemServiceMailClient->getSortInfoStrategy()
        );


        $this->assertInstanceOf(
            DefaultMessageItemFieldsProcessor::class,
            $messageItemService->getMessageItemFieldsProcessor()
        );
        $this->assertInstanceOf(
            ReadableMessagePartContentProcessor::class,
            $messageItemService->getReadableMessagePartContentProcessor()
        );
        $this->assertInstanceOf(
            WritableMessagePartContentProcessor::class,
            $messageItemService->getWritableMessagePartContentProcessor()
        );
        $this->assertInstanceOf(
            DefaultPreviewTextProcessor::class,
            $messageItemService->getPreviewTextProcessor()
        );


    }


    /**
     * @return void
     * @throws ReflectionException
     */
    public function testScopedRequest()
    {
        $urls = [
            "http://localhost/MailAccounts/dev/MailFolders/INBOX/MessageItems",
            "http://localhost/MailAccounts/dev/MailFolders/INBOX/MessageBodies",
            "http://localhost/MailAccounts",
            "http://localhost/MailAccounts/dev/MailFolders/INBOX"
        ];
        foreach ($urls as $testUrl) {
            $this->app = $this->createApplication();

            $reflection = new ReflectionClass($this->app);
            $scoped = $reflection->getProperty("scopedInstances");

            $request = $this->createMockForAbstract(Request::class, ["url", "route"]);
            $route = $this->createMockForAbstract(Router::class, ["getPrefix"], [$this->app]);
            $route->expects($this->any())->method("getPrefix")->willReturn("rest-api-email/api/v0");
            $request->expects($this->any())->method("route")->willReturn($route);
            $request->expects($this->any())->method("url")->willReturn(
                $testUrl
            );

            $this->app->request = $request;

            $this->assertTrue(in_array(JsonApiRequest::class, $scoped->getValue($this->app)));

            $jsonApiRequest = $this->app->make(JsonApiRequest::class);

            $this->assertInstanceOf(JsonApiRequest::class, $jsonApiRequest);

            $this->assertSame($testUrl, $jsonApiRequest->getUrl()->toString());

            $getQueryValidator = $this->makeAccessible($jsonApiRequest, "getQueryValidator");

            $this->assertInstanceOf(Validator::class, $getQueryValidator->invokeArgs($jsonApiRequest, []));
        }
    }


    /**
     * Test to retrieve the MessageItemService with configured MailAccount
     * retrieved from input
     *
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function testMessageItemServiceInput()
    {

        $cmpId = "8998";

        $userStub = $this->getTemplateUserStub(["getMailAccount"]);
        $userStub->method("getMailAccount")
            ->with($cmpId)
            ->willReturn(new MailAccount(["id" => $cmpId]));
        $this->app["auth"]->setUser($userStub);


        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getMethod("getConcrete");
        $property->setAccessible(true);

        $request = new Request();
        $request["mailAccountId"] = $cmpId;
        $this->app["request"] = $request;

        $messageItemService = $this->app->build(
            $property->invokeArgs($this->app, [MessageItemService::class])
        );

        $this->assertSame($messageItemService->getMailClient()->getMailAccount($cmpId)->getId(), $cmpId);
    }


    /**
     * Test to retrieve the MessageItemService with configured MailAccount
     * retrieved from route; route params should be given precedence to
     * input params
     *
     * @throws BindingResolutionException
     * @throws ReflectionException
     *
     */
    public function testMessageItemServiceRoute()
    {

        $cmpId = "8998";

        $userStub = $this->getTemplateUserStub(["getMailAccount"]);
        $userStub->method("getMailAccount")
            ->with($cmpId)
            ->willReturn(new MailAccount(["id" => $cmpId]));
        $this->app["auth"]->setUser($userStub);


        $reflection = new ReflectionClass($this->app);
        $property = $reflection->getMethod("getConcrete");
        $property->setAccessible(true);


        $request = Request::create("dummyurl", "GET", ["mailAccountId" => $cmpId . "ztr"]);

        // make sure routing works
        $request->setRouteResolver(function () use ($cmpId) {
            return new class ($cmpId) {
                protected string $cmpId;

                public function __construct($cmpId)
                {
                    $this->cmpId = $cmpId;
                }
                public function parameter($param): ?string
                {
                    if ($param === "mailAccountId") {
                        return $this->cmpId;
                    }

                    return null;
                }
            };
        });

        $this->app["request"] = $request;

        $messageItemService = $this->app->build(
            $property->invokeArgs($this->app, [MessageItemService::class])
        );

        $this->assertSame($messageItemService->getMailClient()->getMailAccount($cmpId)->getId(), $cmpId);
    }
}
