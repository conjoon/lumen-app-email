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

namespace Conjoon\Mail\Client\Message\Text;

use Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;

/**
 * Class PreviewProcessor
 *
 * @package Conjoon\Mail\Client\Message\Text
 */
class DefaultPreviewTextProcessor implements PreviewTextProcessor {


    /**
     * @var DefaultMessagePartContentProcessor
     */
    protected $processor;


    /**
     * DefaultPreviewTextProcessor constructor.
     * @param ReadableMessagePartContentProcessor $processor
     */
    public function __construct(ReadableMessagePartContentProcessor $processor) {
        $this->processor = $processor;
    }


    /**
     * Returns the ReadableMessagePartContentProcessor decorated by this PreviewTextProcessor.
     *
     * @return ReadableMessagePartContentProcessor
     */
    public function getReadableMessagePartContentProcessor() {
        return $this->processor;
    }

// +--------------------------------------
// | MessagePartContentProcessor
// +--------------------------------------

    /**
     * Processes the specified MessagePart and returns its contents properly converted to UTF-8
     * and stripped of all HTML-tags  as a 200 character long previewText.
     *
     * @inheritdoc
     */
    public function process(MessagePart $messagePart, string $toCharset = "UTF-8") : MessagePart {

        $messagePart = $this->processor->process($messagePart, $toCharset);

        $messagePart->setContents(
            htmlentities(
                mb_substr(
                    strip_tags(trim($messagePart->getContents())),0,200,  $toCharset
                )
            ),
            $toCharset
        );

        return $messagePart;
    }


}