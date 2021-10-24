<?php

/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

namespace Conjoon\Mail\Client\Service;

use Conjoon\Core\ParameterBag;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\MailClientException;
use Conjoon\Mail\Client\Message\AbstractMessageItem;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\PreviewTextProcessor;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor;

/**
 * Class DefaultMessageItemService.
 * Default implementation of a MessageItemService.
 *
 * @package App\Imap\Service
 */
class DefaultMessageItemService implements MessageItemService
{


    /**
     * @var MailClient
     */
    protected MailClient $mailClient;

    /**
     * @var PreviewTextProcessor
     */
    protected PreviewTextProcessor $previewTextProcessor;

    /**
     * @var ReadableMessagePartContentProcessor
     */
    protected ReadableMessagePartContentProcessor $readableMessagePartContentProcessor;

    /**
     * @var WritableMessagePartContentProcessor
     */
    protected WritableMessagePartContentProcessor $writableMessagePartContentProcessor;

    /**
     * @var MessageItemFieldsProcessor
     */
    protected MessageItemFieldsProcessor $messageItemFieldsProcessor;


    /**
     * DefaultMessageItemService constructor.
     *
     * @param MailClient $mailClient
     * @param MessageItemFieldsProcessor $messageItemFieldsProcessor
     * @param ReadableMessagePartContentProcessor $readableMessagePartContentProcessor
     * @param WritableMessagePartContentProcessor $writableMessagePartContentProcessor
     * @param PreviewTextProcessor $previewTextProcessor
     */
    public function __construct(
        MailClient $mailClient,
        MessageItemFieldsProcessor $messageItemFieldsProcessor,
        ReadableMessagePartContentProcessor $readableMessagePartContentProcessor,
        WritableMessagePartContentProcessor $writableMessagePartContentProcessor,
        PreviewTextProcessor $previewTextProcessor
    ) {
        $this->messageItemFieldsProcessor = $messageItemFieldsProcessor;
        $this->mailClient = $mailClient;
        $this->readableMessagePartContentProcessor = $readableMessagePartContentProcessor;
        $this->writableMessagePartContentProcessor = $writableMessagePartContentProcessor;
        $this->previewTextProcessor = $previewTextProcessor;
    }


// -------------------------
//  MessageItemService
// -------------------------
    /**
     * @inheritdoc
     */
    public function getMailClient(): MailClient
    {
        return $this->mailClient;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemFieldsProcessor(): MessageItemFieldsProcessor
    {
        return $this->messageItemFieldsProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getPreviewTextProcessor(): PreviewTextProcessor
    {
        return $this->previewTextProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getReadableMessagePartContentProcessor(): ReadableMessagePartContentProcessor
    {
        return $this->readableMessagePartContentProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getWritableMessagePartContentProcessor(): WritableMessagePartContentProcessor
    {
        return $this->writableMessagePartContentProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemList(FolderKey $folderKey, MessageItemListResourceQuery $query): MessageItemList
    {
        $messageItemList = $this->mailClient->getMessageItemList(
            $folderKey,
            $query
        );

        foreach ($messageItemList as $listMessageItem) {
            $this->charsetConvertHeaderFields($listMessageItem);
            if ($listMessageItem->getMessagePart()) {
                $this->processTextForPreview($listMessageItem->getMessagePart());
            }
        }

        return $messageItemList;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MessageKey $messageKey): MessageItem
    {
        $messageItem = $this->mailClient->getMessageItem($messageKey);
        $this->charsetConvertHeaderFields($messageItem);
        return $messageItem;
    }


    /**
     * @inheritdoc
     */
    public function deleteMessage(MessageKey $messageKey): bool
    {
        $result = false;

        try {
            $result = $this->mailClient->deleteMessage($messageKey);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return $result;
    }


    /**
     * @inheritdoc
     */
    public function getListMessageItem(MessageKey $messageKey): ListMessageItem
    {
        $messageItemList = $this->getMessageItemList(
            $messageKey->getFolderKey(),
            new MessageItemListResourceQuery(new ParameterBag([
                "ids" => [$messageKey->getId()]
            ]))
        );

        return $messageItemList[0];
    }

    /**
     * @inheritdoc
     */
    public function getMessageItemDraft(MessageKey $messageKey): ?MessageItemDraft
    {
        $messageItemDraft = $this->mailClient->getMessageItemDraft($messageKey);
        $this->charsetConvertHeaderFields($messageItemDraft);
        return $messageItemDraft;
    }


    /**
     * @inheritdoc
     */
    public function sendMessageDraft(MessageKey $messageKey): bool
    {

        $result = false;

        try {
            $result = $this->mailClient->sendMessageDraft($messageKey);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return $result;
    }


    /**
     * @inheritdoc
     */
    public function getMessageBody(MessageKey $messageKey): MessageBody
    {
        $messageBody = $this->mailClient->getMessageBody($messageKey);
        $this->processMessageBody($messageBody);
        return $messageBody;
    }


    /**
     * @inheritdoc
     */
    public function getUnreadMessageCount(FolderKey $folderKey): int
    {
        return $this->getMailClient()->getUnreadMessageCount($folderKey);
    }


    /**
     * @inheritdoc
     */
    public function getTotalMessageCount(FolderKey $folderKey): int
    {
        return $this->getMailClient()->getTotalMessageCount($folderKey);
    }

    /**
     * @inheritdoc
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList): bool
    {
        return $this->getMailClient()->setFlags($messageKey, $flagList);
    }


    /**
     * @inheritdoc
     */
    public function moveMessage(MessageKey $messageKey, FolderKey $folderKey): ?MessageKey
    {

        try {
            return $this->getMailClient()->moveMessage($messageKey, $folderKey);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function createMessageBodyDraft(FolderKey $folderKey, MessageBodyDraft $draft): ?MessageBodyDraft
    {

        if ($draft->getMessageKey()) {
            throw new ServiceException(
                "Cannot create a MessageBodyDraft that has a MessageKey"
            );
        }

        $this->processMessageBodyDraft($draft);

        try {
            return $this->getMailClient()->createMessageBodyDraft($folderKey, $draft);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function updateMessageBodyDraft(MessageBodyDraft $draft): ?MessageBodyDraft
    {

        if (!$draft->getMessageKey()) {
            throw new ServiceException(
                "Cannot update a MessageBodyDraft that has no MessageKey"
            );
        }

        $this->processMessageBodyDraft($draft);

        try {
            return $this->getMailClient()->updateMessageBodyDraft($draft);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function updateMessageDraft(MessageItemDraft $messageItemDraft): ?MessageItemDraft
    {

        $updated = null;

        try {
            $updated = $this->getMailClient()->updateMessageDraft($messageItemDraft);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return $updated;
    }


// -----------------------
// Helper
// -----------------------

    /**
     * Makes sure that there is a text/plain part for this message if only text/html was
     * available. If only text/plain is available, a text/html part will be created.
     *
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return MessageBodyDraft
     */
    protected function processMessageBodyDraft(MessageBodyDraft $messageBodyDraft): MessageBodyDraft
    {

        $targetCharset = "UTF-8";
        $plainPart = $messageBodyDraft->getTextPlain();
        $htmlPart = $messageBodyDraft->getTextHtml();

        if (!$plainPart && $htmlPart) {
            $plainPart = new MessagePart($htmlPart->getContents(), $htmlPart->getCharset(), "text/plain");
            $messageBodyDraft->setTextPlain($plainPart);
        }
        if ($plainPart && !$htmlPart) {
            $htmlPart = new MessagePart($plainPart->getContents(), $plainPart->getCharset(), "text/html");
            $messageBodyDraft->setTextHtml($htmlPart);
        }

        if (!$plainPart && !$htmlPart) {
            $plainPart = new MessagePart("", $targetCharset, "text/plain");
            $messageBodyDraft->setTextPlain($plainPart);
            $htmlPart = new MessagePart("", $targetCharset, "text/html");
            $messageBodyDraft->setTextHtml($htmlPart);
        }

        // wee need to strip any tags
        $this->getWritableMessagePartContentProcessor()->process($plainPart, $targetCharset);

        // we need to convert line breaks to html tags
        $this->getWritableMessagePartContentProcessor()->process($htmlPart, $targetCharset);

        return $messageBodyDraft;
    }


    /**
     * Processes the specified MessageItem with the help of this MessageItemFieldsProcessor
     * @param AbstractMessageItem $messageItem
     * @return AbstractMessageItem
     */
    protected function charsetConvertHeaderFields(AbstractMessageItem $messageItem): AbstractMessageItem
    {

        $targetCharset = "UTF-8";

        return $this->getMessageItemFieldsProcessor()->process($messageItem, $targetCharset);
    }


    /**
     * Processes the contents of the MessageBody's Parts and makes sure this converter converts
     * the contents to proper UTF-8.
     * Additionally, the text/html part will be filtered by this $htmlReadableStrategy.
     *
     *
     * @param MessageBody $messageBody
     *
     * @return MessageBody
     *
     * @see MessagePartContentProcessor::process
     */
    protected function processMessageBody(MessageBody $messageBody): MessageBody
    {

        $textPlainPart = $messageBody->getTextPlain();
        $textHtmlPart = $messageBody->getTextHtml();

        $targetCharset = "UTF-8";

        if ($textPlainPart) {
            $this->getReadableMessagePartContentProcessor()->process($textPlainPart, $targetCharset);
        }

        if ($textHtmlPart) {
            $this->getReadableMessagePartContentProcessor()->process($textHtmlPart, $targetCharset);
        }

        return $messageBody;
    }


    /**
     * Processes the specified MessagePart and returns its contents properly converted to UTF-8
     * and stripped of all HTML-tags  as a 200 character long previewText.
     *
     * @param MessagePart $messagePart
     *
     * @return MessagePart
     *
     * @see PreviewTextProcessor::process
     */
    protected function processTextForPreview(MessagePart $messagePart): MessagePart
    {
        return $this->getPreviewTextProcessor()->process($messagePart);
    }
}
