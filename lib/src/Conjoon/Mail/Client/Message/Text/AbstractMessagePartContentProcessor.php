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

namespace Conjoon\Mail\Client\Message\Text;

use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Text\Converter;

/**
 * AbstractMessagePartContentProcessor.
 *
 * @package Conjoon\Mail\Client\Message\Text
 */
abstract class AbstractMessagePartContentProcessor implements MessagePartContentProcessor
{

    /**
     * @var Converter
     */
    protected Converter $converter;

    /**
     * @var PlainTextStrategy
     */
    protected PlainTextStrategy $plainTextStrategy;

    /**
     * @var HtmlTextStrategy
     */
    protected HtmlTextStrategy $htmlTextStrategy;


    /**
     * DefaultMessagePartContentProcessor constructor.
     *
     * @param Converter $converter
     * @param PlainTextStrategy $plainTextStrategy
     * @param HtmlTextStrategy $htmlTextStrategy
     */
    public function __construct(
        Converter $converter,
        PlainTextStrategy $plainTextStrategy,
        HtmlTextStrategy $htmlTextStrategy
    ) {

        $this->converter = $converter;
        $this->plainTextStrategy = $plainTextStrategy;
        $this->htmlTextStrategy = $htmlTextStrategy;
    }


// +--------------------------------------
// | Interface MessagePartContentProcessor
// +--------------------------------------
    /**
     * Processes the contents of the MessagePart and makes sure this converter converts
     * the contents to proper UTF-8.
     * Additionally, the text/html part will be filtered by this $htmlReadableStrategy.
     *
     * @inheritdoc
     */
    public function process(MessagePart $messagePart, string $toCharset = "UTF-8"): MessagePart
    {

        $mimeType = $messagePart->getMimeType();

        switch ($mimeType) {
            case "text/plain":
                $messagePart->setContents($this->plainTextStrategy->process($this->converter->convert(
                    $messagePart->getContents(),
                    $messagePart->getCharset(),
                    $toCharset
                )), $toCharset);
                break;

            case "text/html":
                $messagePart->setContents($this->htmlTextStrategy->process($this->converter->convert(
                    $messagePart->getContents(),
                    $messagePart->getCharset(),
                    $toCharset
                )), $toCharset);
                break;

            default:
                throw new ProcessorException("Cannot process any mime type other than \"text/plain\"/\"text/html\"");
        }

        return $messagePart;
    }
}
