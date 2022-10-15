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
use App\ControllerUtil;
use App\Exceptions\Handler;
use Conjoon\Core\ClassLookup;
use Conjoon\Horde_Imap\Client\SortInfoStrategy;
use Conjoon\Http\Request\Request as HttpRequest;
use Conjoon\Core\Strategy\JsonStrategy;
use Conjoon\Horde_Imap\Client\HordeClient;
use Conjoon\Illuminate\Http\Request\LaravelRequest;
use Conjoon\JsonApi\Query\Validation\Validator;
use Conjoon\JsonApi\Request\Request as JsonApiRequest;
use Conjoon\Horde_Mime\Composer\HordeAttachmentComposer;
use Conjoon\Horde_Mime\Composer\HordeBodyComposer;
use Conjoon\Horde_Mime\Composer\HordeHeaderComposer;
use Conjoon\Illuminate\Auth\Imap\DefaultImapUserProvider;
use Conjoon\Illuminate\Auth\Imap\ImapUserProvider;
use Conjoon\JsonApi\Request\ResourceUrlParser;
use Conjoon\JsonApi\Request\ResourceUrlRegex;
use Conjoon\JsonApi\Request\ResourceUrlRegexList;
use Conjoon\Core\Data\Resource\ObjectDescription;
use Conjoon\MailClient\Message\Attachment\Processor\InlineDataProcessor;
use Conjoon\MailClient\Data\MailAccount;
use Conjoon\MailClient\Folder\Tree\DefaultMailFolderTreeBuilder;
use Conjoon\MailClient\Data\Protocol\Imap\Util\DefaultFolderIdToTypeMapper;
use Conjoon\MailClient\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\MailClient\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\MailClient\Data\Reader\DefaultPlainReadableStrategy;
use Conjoon\MailClient\Data\Reader\PurifiedHtmlStrategy;
use Conjoon\MailClient\Data\Reader\ReadableMessagePartContentProcessor;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\AttachmentListJsonTransformer;
use Conjoon\Illuminate\MailClient\Data\Protocol\Http\Request\Transformer\LaravelAttachmentListJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\DefaultMessageBodyDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\DefaultMessageItemDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\MessageBodyDraftJsonTransformer;
use Conjoon\MailClient\Data\Protocol\Http\Request\Transformer\MessageItemDraftJsonTransformer;
use Conjoon\MailClient\Service\AttachmentService;
use Conjoon\MailClient\Service\AuthService;
use Conjoon\MailClient\Service\DefaultAttachmentService;
use Conjoon\MailClient\Service\DefaultAuthService;
use Conjoon\MailClient\Service\DefaultMailFolderService;
use Conjoon\MailClient\Service\DefaultMessageItemService;
use Conjoon\MailClient\Service\MailFolderService;
use Conjoon\MailClient\Service\MessageItemService;
use Conjoon\MailClient\Data\Protocol\Http\Response\JsonApiStrategy;
use Conjoon\MailClient\Data\Writer\DefaultHtmlWritableStrategy;
use Conjoon\MailClient\Data\Writer\DefaultPlainWritableStrategy;
use Conjoon\MailClient\Data\Writer\WritableMessagePartContentProcessor;
use Conjoon\Text\CharsetConverter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;

// helper function to make sure Services can share HordeClients for the same account
$mailClients = [];
$hordeBodyComposer = new HordeBodyComposer();
$hordeAttachmentComposer = new HordeAttachmentComposer();
$hordeHeaderComposer = new HordeHeaderComposer();
$hordeSortInfoStrategy = new SortInfoStrategy();

$getMailClient = function (MailAccount $account) use (
    &$mailClients,
    &$hordeBodyComposer,
    &$hordeHeaderComposer,
    &$hordeAttachmentComposer,
    &$hordeSortInfoStrategy
) {

    $accountId = $account->getId();

    if (isset($mailClients[$accountId])) {
        return $mailClients[$accountId];
    }


    $mailClient = new HordeClient(
        $account,
        $hordeBodyComposer,
        $hordeHeaderComposer,
        $hordeAttachmentComposer,
        $hordeSortInfoStrategy
    );
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

$app->singleton(AuthService::class, function () {
    return new DefaultAuthService();
});

$app->singleton(JsonStrategy::class, function () {
    return new JsonApiStrategy();
});

$app->singleton(ControllerUtil::class, function () {
    return new ControllerUtil();
});

$app->singleton(MessageBodyDraftJsonTransformer::class, function () {
    return new DefaultMessageBodyDraftJsonTransformer();
});

$app->singleton(AttachmentListJsonTransformer::class, function () {
    return new LaravelAttachmentListJsonTransformer();
});

$app->singleton(AttachmentListJsonTransformer::class, function () {
    return new LaravelAttachmentListJsonTransformer();
});


$app->singleton(ResourceUrlRegexList::class, function () {
    $resourceUrls = config("app.api.resourceUrls");

    $urlRegexList = new ResourceUrlRegexList();

    foreach ($resourceUrls as $resourceUrlCfg) {
        $urlRegexList[] = new ResourceUrlRegex(
            $resourceUrlCfg["regex"],
            $resourceUrlCfg["nameIndex"],
            $resourceUrlCfg["singleIndex"]
        );
    }

    return $urlRegexList;
});


$app->scoped(HttpRequest::class, function ($app) {
    return new LaravelRequest($app->request);
});


$app->scoped(JsonApiRequest::class, function ($app) {

    $request      = $app->make(HttpRequest::class);
    $urlRegexList = $app->make(ResourceUrlRegexList::class);

    $latest = config("app.api.latest");
    preg_match_all(
        config("app.api.versionRegex"),
        $app->request->route()->getPrefix(),
        $matches,
        PREG_SET_ORDER,
        0
    );
    $apiVersion = strtoupper(
        $matches && $matches[0][1] ? $matches[0][1] : $latest
    );
    $resourceDescriptionParser = new ResourceUrlParser(
        $urlRegexList,
        str_replace("{apiVersion}", $apiVersion, config("app.api.resourceDescriptionTpl"))
    );

    $validationParser = new ResourceUrlParser(
        $urlRegexList,
        str_replace("{apiVersion}", $apiVersion, config("app.api.validationTpl.single")),
        str_replace("{apiVersion}", $apiVersion, config("app.api.validationTpl.collection"))
    );

    $classLookup = new ClassLookup();
    $resourceDescription = $classLookup->load(
        $resourceDescriptionParser->parse($request->getUrl()),
        ObjectDescription::class
    );
    $validator = $classLookup->load(
        $validationParser->parse($request->getUrl()),
        Validator::class
    );

    return new JsonApiRequest(
        $request,
        $resourceDescription,
        $validator
    );
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
