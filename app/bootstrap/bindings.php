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

use App\Console\Kernel as ConsoleKernel;
use App\Exceptions\Handler;
use Conjoon\Horde\Mail\Client\Imap\HordeClient;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeAttachmentComposer;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeBodyComposer;
use Conjoon\Horde\Mail\Client\Message\Composer\HordeHeaderComposer;
use Conjoon\Illuminate\Auth\Imap\DefaultImapUserProvider;
use Conjoon\Illuminate\Auth\Imap\ImapUserProvider;
use Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Folder\Tree\DefaultMailFolderTreeBuilder;
use Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper;
use Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\Mail\Client\Reader\DefaultPlainReadableStrategy;
use Conjoon\Mail\Client\Reader\PurifiedHtmlStrategy;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer\LaravelAttachmentListJsonTransformer;
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
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;

// helper function to make sure Services can share HordeClients for the same account
$mailClients = [];
$hordeBodyComposer = new HordeBodyComposer();
$hordeAttachmentComposer = new HordeAttachmentComposer();
$hordeHeaderComposer = new HordeHeaderComposer();

$getMailClient = function (MailAccount $account) use (
    &$mailClients,
    &$hordeBodyComposer,
    &$hordeHeaderComposer,
    &$hordeAttachmentComposer
) {

    $accountId = $account->getId();

    if (isset($mailClients[$accountId])) {
        return $mailClients[$accountId];
    }


    $mailClient = new HordeClient($account, $hordeBodyComposer, $hordeHeaderComposer, $hordeAttachmentComposer);
    $mailClients[$accountId] = $mailClient;
    return $mailClient;
};

/** @noinspection PhpUndefinedVariableInspection */
$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

$app->singleton(
    Kernel::class,
    ConsoleKernel::class
);

$app->singleton(ImapUserProvider::class, function () {
    return new DefaultImapUserProvider(config('imapserver'));
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

$app->singleton(AttachmentListJsonTransformer::class, function () {
    return new LaravelAttachmentListJsonTransformer();
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
