<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor,
    Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\MailClientException,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\AbstractMessageItem,
    Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;

/**
 * Class DefaultMessageItemService.
 * Default implementation of a MessageItemService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultMessageItemService implements MessageItemService {


    /**
     * @var MailClient
     */
    protected $mailClient;

    /**
     * @var PreviewTextProcessor
     */
    protected $previewTextProcessor;

    /**
     * @var ReadableMessagePartContentProcessor
     */
    protected $readableMessagePartContentProcessor;

    /**
     * @var WritableMessagePartContentProcessor
     */
    protected $writableMessagePartContentProcessor;

    /**
     * @var MessageItemFieldsProcessor
     */
    protected $messageItemFieldsProcessor;


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
        PreviewTextProcessor $previewTextProcessor) {
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
     *  @inheritdoc
     */
    public function getMailClient() :MailClient {
        return $this->mailClient;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemFieldsProcessor() :MessageItemFieldsProcessor {
        return $this->messageItemFieldsProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getPreviewTextProcessor() :PreviewTextProcessor {
        return $this->previewTextProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getReadableMessagePartContentProcessor() :ReadableMessagePartContentProcessor {
        return $this->readableMessagePartContentProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getWritableMessagePartContentProcessor() :WritableMessagePartContentProcessor {
        return $this->writableMessagePartContentProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemList(FolderKey $folderKey, array $options) :MessageItemList {

        $messageItemList = $this->mailClient->getMessageItemList(
            $folderKey, $options);

        foreach ($messageItemList as $listMessageItem) {
            $this->charsetConvertHeaderFields($listMessageItem);
            $this->processTextForPreview($listMessageItem->getMessagePart());
        }

        return $messageItemList;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MessageKey $key) :MessageItem {
        $messageItem = $this->mailClient->getMessageItem($key);
        $this->charsetConvertHeaderFields($messageItem);
        return $messageItem;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemDraft(MessageKey $key) :?MessageItemDraft {
        $messageItemDraft = $this->mailClient->getMessageItemDraft($key);
        $this->charsetConvertHeaderFields($messageItemDraft);
        return $messageItemDraft;
    }


    /**
     * @inheritdoc
     */
    public function sendMessageDraft(MessageKey $key) : bool {

        $result = false;

        try {
            $result = $this->mailClient->sendMessageDraft($key);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return $result;
    }



    /**
     * @inheritdoc
     */
    public function getMessageBody(MessageKey $key) :MessageBody {
        $messageBody = $this->mailClient->getMessageBody($key);
        $this->processMessageBody($messageBody);
        return $messageBody;
    }


    /**
     * @inheritdoc
     */
    public function getUnreadMessageCount(FolderKey $folderKey) :int {
        return $this->getMailClient()->getUnreadMessageCount($folderKey);
    }


    /**
     * @inheritdoc
     */
    public function getTotalMessageCount(FolderKey $folderKey) :int {
        return $this->getMailClient()->getTotalMessageCount($folderKey);
    }

    /**
     * @inheritdoc
     */
    public function setFlags(MessageKey $messageKey, FlagList $flagList) :bool {
        return $this->getMailClient()->setFlags($messageKey, $flagList);
    }


    /**
     * @inheritdoc
     */
    public function createMessageBodyDraft(FolderKey $key, MessageBodyDraft $messageBodyDraft) :?MessageBodyDraft {

        if ($messageBodyDraft->getMessageKey()) {
            throw new ServiceException(
                "Cannot create a MessageBodyDraft that has a MessageKey"
            );
        }

        $this->processMessageBodyDraft($messageBodyDraft);

        try {
            return $this->getMailClient()->createMessageBodyDraft($key, $messageBodyDraft);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function updateMessageBodyDraft(MessageBodyDraft $messageBodyDraft) :?MessageBodyDraft {

        if (!$messageBodyDraft->getMessageKey()) {
            throw new ServiceException(
                "Cannot update a MessageBodyDraft that has no MessageKey"
            );
        }

        $this->processMessageBodyDraft($messageBodyDraft);

        try {
            return $this->getMailClient()->updateMessageBodyDraft($messageBodyDraft);
        } catch (MailClientException $e) {
            // intentionally left empty
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function updateMessageDraft(MessageItemDraft $messageItemDraft) :?MessageItemDraft {

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
     * Mases sure that there is a text/plain part for this message if only text/html was
     * available. If only text/plain is available, a text/html part will be created.
     *
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return MessageBodyDraft
     */
    protected function processMessageBodyDraft(MessageBodyDraft $messageBodyDraft) {

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
    protected function charsetConvertHeaderFields(AbstractMessageItem $messageItem) :AbstractMessageItem {

        $targetCharset = "UTF-8";

        $messageItem = $this->getMessageItemFieldsProcessor()->process($messageItem, $targetCharset);

        return $messageItem;

    }


    /**
     * Processes the contents of the MessageBody's Parts and makes sure this converter converts
     * the contents to proper UTF-8.
     * Additionally, the text/html part will be filtered by this $htmlReadableStrategy.
     * If no text/html part is available, the text/plain part will be used instead.
     *
     * @param MessageBody $messageBody
     *
     * @return MessageBody
     *
     * @see MessagePartContentProcessor::process
     */
    protected function processMessageBody(MessageBody $messageBody) :MessageBody {

        $textPlainPart = $messageBody->getTextPlain();
        $textHtmlPart  = $messageBody->getTextHtml();

        $targetCharset = "UTF-8";

        if (!$textPlainPart) {
            $textPlainPart = new MessagePart("", "UTF-8", "text/plain");
            $messageBody->setTextPlain($textPlainPart);
        }

        $this->getReadableMessagePartContentProcessor()->process($textPlainPart, $targetCharset);

        if ($textHtmlPart && $textHtmlPart->getContents()) {
            $this->getReadableMessagePartContentProcessor()->process($textHtmlPart, $targetCharset);
        } else {
            $textHtmlPart = new MessagePart($textPlainPart->getContents(), "UTF-8", "text/html");
            $messageBody->setTextHtml($textHtmlPart);
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
    protected function processTextForPreview(MessagePart $messagePart) :MessagePart {
        return $this->getPreviewTextProcessor()->process($messagePart);
    }


}