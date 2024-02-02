<?php

/**
 * This file is part of the conjoon/lumen-app-email project.
 *
 * (c) 2020-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace Tests;

use Closure;
use Conjoon\Horde\Mail\Client\Imap\HordeClient;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeAttachmentComposer;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeBodyComposer;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeHeaderComposer;
use Conjoon\Illuminate\Auth\Imap\DefaultImapUserProvider;
use Conjoon\Illuminate\Auth\ImapUserProvider;
use Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer\LaravelAttachmentListJsonTransformer;
use Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Folder\Tree\DefaultMailFolderTreeBuilder;
use Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper;
use Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Service\AttachmentService;
use Conjoon\Mail\Client\Service\AuthService;
use Conjoon\Mail\Client\Service\DefaultAttachmentService;
use Conjoon\Mail\Client\Service\DefaultAuthService;
use Conjoon\Mail\Client\Service\DefaultMailFolderService;
use Conjoon\Mail\Client\Service\DefaultMessageItemService;
use Conjoon\Mail\Client\Service\MailFolderService;
use Conjoon\Mail\Client\Service\MessageItemService;
use Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionException;
use Tests\V0\RoutesTrait as V0Routes;
use Tests\V1\RoutesTrait as V1Routes;

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
    use V0Routes;
    use V1Routes;

    protected bool $useFakeAuth = false;

    /**
     * We are currently on...
     *
     * @return void
     */
    public function testApi()
    {
        $this->assertGreaterThan(
            -1,
            array_search(env("AUTH_PROVIDER"), ["single-imap-user", "local-mail-account"])
        );


        $this->assertEquals(["v0", "v1"], config("app.api.service.email.versions"));
        $this->assertSame(env("APP_EMAIL_API"), config("app.api.service.email.latest"));
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

        $versions = config("app.api.service.email.versions");
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
        $this->{"apiRoutes" . strtoupper(env("APP_EMAIL_API"))}();
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


        if (env("AUTH_PROVIDER") === "single-imap-user") {
            $this->assertInstanceOf(
                DefaultImapUserProvider::class,
                $this->app->build($property->invokeArgs($this->app, [ImapUserProvider::class]))
            );
        } else {
            $this->assertSame("local-mail-account", env("AUTH_PROVIDER"));
        }

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
