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
    Conjoon\Mail\Client\Message\Text\MessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\MessageBody;

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
     * @var MessagePartContentProcessor
     */
    protected $messagePartContentProcessor;


    /**
     * DefaultMessageItemService constructor.
     *
     * @param MailClient $mailClient
     * @param MessagePartContentProcessor $messagePartContentProcessor
     * @param PreviewTextProcessor $previewTextProcessor
     */
    public function __construct(
        MailClient $mailClient,
        MessagePartContentProcessor $messagePartContentProcessor,
        PreviewTextProcessor $previewTextProcessor) {
        $this->mailClient = $mailClient;
        $this->messagePartContentProcessor = $messagePartContentProcessor;
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
    public function getPreviewTextProcessor() :PreviewTextProcessor {
        return $this->previewTextProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getMessagePartContentProcessor() :MessagePartContentProcessor {
        return $this->messagePartContentProcessor;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemList(FolderKey $folderKey, array $options) :MessageItemList {

        $messageItemList = $this->mailClient->getMessageItemList(
            $folderKey, $options);

        foreach ($messageItemList as $listMessageItem) {
            $this->processTextForPreview($listMessageItem->getMessagePart());
        }

        return $messageItemList;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItem(MessageKey $key) :MessageItem {
        return $this->mailClient->getMessageItem($key);
    }


    /**
     * @inheritdoc
     */
    public function getMessageBody(MessageKey $key) :MessageBody {
        $messageBody = $this->mailClient->getMessageBody($key);
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


// -----------------------
// Helper
// -----------------------

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

        $this->getMessagePartContentProcessor()->process($textPlainPart, $targetCharset);

        if ($textHtmlPart) {
            $this->getMessagePartContentProcessor()->process($textHtmlPart, $targetCharset);

        } else {
            $textHtmlPart = new MessagePart($textPlainPart->getContents(), "UTF-8", "text/plain");
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