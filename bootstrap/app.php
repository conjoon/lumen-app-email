<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

require_once __DIR__ . '/../vendor/autoload.php';

use App\Console\Kernel as ConsoleKernel;
use App\Exceptions\Handler;
use App\Imap\DefaultImapUserRepository;
use App\Imap\ImapUserRepository;
use App\Providers\AuthServiceProvider;
use Conjoon\Horde\Mail\Client\Imap\HordeClient;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeBodyComposer;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeHeaderComposer;
use Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Folder\Tree\DefaultMailFolderTreeBuilder;
use Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper;
use Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\Mail\Client\Reader\DefaultPlainReadableStrategy;
use Conjoon\Mail\Client\Reader\PurifiedHtmlStrategy;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\Mail\Client\Service\AttachmentService;
use Conjoon\Mail\Client\Service\DefaultAttachmentService;
use Conjoon\Mail\Client\Service\DefaultMailFolderService;
use Conjoon\Mail\Client\Service\DefaultMessageItemService;
use Conjoon\Mail\Client\Service\MailFolderService;
use Conjoon\Mail\Client\Service\MessageItemService;
use Conjoon\Mail\Client\Writer\DefaultHtmlWritableStrategy;
use Conjoon\Mail\Client\Writer\DefaultPlainWritableStrategy;
use Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor;
use Conjoon\Text\CharsetConverter;
use Fruitcake\Cors\CorsServiceProvider;
use Fruitcake\Cors\HandleCors;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Lumen\Bootstrap\LoadEnvironmentVariables;

(new LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->configure('app');
$app->configure('imapserver');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

// helper function to make sure Services can share HordeClients for the same account
$mailClients = [];
$hordeBodyComposer   = new HordeBodyComposer();
$hordeHeaderComposer = new HordeHeaderComposer();

$getMailClient = function (MailAccount $account) use (&$mailClients, &$hordeBodyComposer, &$hordeHeaderComposer) {

    $accountId = $account->getId();

    if (isset($mailClients[$accountId])) {
        return $mailClients[$accountId];
    }


    $mailClient = new HordeClient($account, $hordeBodyComposer, $hordeHeaderComposer);
    $mailClients[$accountId] = $mailClient;
    return $mailClient;
};

$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

$app->singleton(
    Kernel::class,
    ConsoleKernel::class
);

$app->singleton(ImapUserRepository::class, function () {
    return new DefaultImapUserRepository(config('imapserver'));
});

$app->singleton(MailFolderService::class, function ($app) use ($getMailClient) {
    $mailClient = $getMailClient($app->auth->user()->getMailAccount($app->request->route('mailAccountId')));
    return new DefaultMailFolderService(
        $mailClient,
        new DefaultMailFolderTreeBuilder(
            new DefaultFolderIdToTypeMapper()
        )
    );
});

$app->singleton(AttachmentService::class, function ($app) use ($getMailClient) {
    $mailClient = $getMailClient($app->auth->user()->getMailAccount($app->request->route('mailAccountId')));
    return new DefaultAttachmentService(
        $mailClient,
        new InlineDataProcessor()
    );
});


$app->singleton(MessageItemDraftJsonTransformer::class, function () {
    return new DefaultMessageItemDraftJsonTransformer();
});

$app->singleton(MessageBodyDraftJsonTransformer::class, function () {
    return new DefaultMessageBodyDraftJsonTransformer();
});

$app->singleton(MessageItemService::class, function ($app) use ($getMailClient) {

    // if mailAccountId not part of the routing url, but request parameters, use those
    $mailAccountId = $app->request->route('mailAccountId') ?? $app->request->input('mailAccountId');

    $mailClient = $getMailClient($app->auth->user()->getMailAccount($mailAccountId));
    $charsetConverter = new CharsetConverter();

    $readableMessagePartContentProcessor = new ReadableMessagePartContentProcessor(
        $charsetConverter,
        new DefaultPlainReadableStrategy(),
        new PurifiedHtmlStrategy()
    );

    $writableMessagePartContentProcessor = new WritableMessagePartContentProcessor(
        $charsetConverter,
        new DefaultPlainWritableStrategy(),
        new DefaultHtmlWritableStrategy()
    );

    $defaultMessageItemFieldsProcessor = new DefaultMessageItemFieldsProcessor(
        $charsetConverter
    );

    return new DefaultMessageItemService(
        $mailClient,
        $defaultMessageItemFieldsProcessor,
        $readableMessagePartContentProcessor,
        $writableMessagePartContentProcessor,
        new DefaultPreviewTextProcessor(
            $readableMessagePartContentProcessor
        )
    );
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/
$app->register(CorsServiceProvider::class);
$app->configure('cors');
$app->middleware([HandleCors::class]);

$versions = config("app.api.versions");
$authMiddleware = [];
foreach ($versions as $version) {
    $version = ucfirst($version);
    $authMiddleware['auth_' . $version] = "App\Http\\" . $version . "\Middleware\Authenticate";
}
$app->routeMiddleware($authMiddleware);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(AuthServiceProvider::class);


/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

require __DIR__ . '/../routes/web.php';

return $app;
