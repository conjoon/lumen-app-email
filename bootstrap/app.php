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

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
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
$hordeBodyComposer   = new Conjoon\Horde\Mail\Client\Message\Composer\HordeBodyComposer;
$hordeHeaderComposer = new Conjoon\Horde\Mail\Client\Message\Composer\HordeHeaderComposer;

$getMailClient = function(Conjoon\Mail\Client\Data\MailAccount $account) use(&$mailClients, &$hordeBodyComposer, &$hordeHeaderComposer) {

    $accountId = $account->getId();

    if (isset($mailClients[$accountId])) {
        return $mailClients[$accountId];
    }


    $mailClient = new Conjoon\Horde\Mail\Client\Imap\HordeClient($account, $hordeBodyComposer, $hordeHeaderComposer);
    $mailClients[$accountId] = $mailClient;
    return $mailClient;
};


$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton('App\Imap\ImapUserRepository', function ($app) {
    return new App\Imap\DefaultImapUserRepository(config('imapserver'));
});

$app->singleton('Conjoon\Mail\Client\Service\MailFolderService', function ($app) use($getMailClient) {
    $mailClient = $getMailClient($app->auth->user()->getMailAccount($app->request->route('mailAccountId')));
    return new Conjoon\Mail\Client\Service\DefaultMailFolderService(
        $mailClient,
        new Conjoon\Mail\Client\Folder\Tree\DefaultMailFolderTreeBuilder(
            new Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper()
        )
    );
});


$app->singleton('Conjoon\Mail\Client\Service\AttachmentService', function ($app) use($getMailClient) {
    $mailClient = $getMailClient($app->auth->user()->getMailAccount($app->request->route('mailAccountId')));
    return new Conjoon\Mail\Client\Service\DefaultAttachmentService(
        $mailClient, new Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor
    );
});


$app->singleton('Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer',  function ($app) {
    return new Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer;
});

$app->singleton('Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer',  function ($app) {
    return new Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer;
});

$app->singleton('Conjoon\Mail\Client\Service\MessageItemService', function ($app) use($getMailClient) {
    $mailAccountId = null;

    if ($app->request->route('mailAccountId')) {
        $mailAccountId = $app->request->route('mailAccountId');
    } else {
        // mailAccountId not part of the routing url, but request parameters
        $mailAccountId = $app->request->input('mailAccountId');
    }

    $mailClient = $getMailClient($app->auth->user()->getMailAccount($mailAccountId));
    $charsetConverter = new Conjoon\Text\CharsetConverter();

    $readableMessagePartContentProcessor = new Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor(
        $charsetConverter,
        new Conjoon\Mail\Client\Reader\DefaultPlainReadableStrategy,
        new Conjoon\Mail\Client\Reader\PurifiedHtmlStrategy
    );

    $writableMessagePartContentProcessor = new Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor(
        $charsetConverter,
        new Conjoon\Mail\Client\Writer\DefaultPlainWritableStrategy,
        new Conjoon\Mail\Client\Writer\DefaultHtmlWritableStrategy
    );

    $defaultMessageItemFieldsProcessor = new Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor(
        $charsetConverter
    );

    return new Conjoon\Mail\Client\Service\DefaultMessageItemService(
        $mailClient,
        $defaultMessageItemFieldsProcessor,
        $readableMessagePartContentProcessor,
        $writableMessagePartContentProcessor,
        new Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor(
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


 $app->routeMiddleware([
     'auth' => App\Http\Middleware\Authenticate::class,
 ]);

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


$app->register(App\Providers\AuthServiceProvider::class);


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

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
