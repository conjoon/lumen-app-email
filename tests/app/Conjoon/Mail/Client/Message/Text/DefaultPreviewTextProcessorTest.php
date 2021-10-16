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

namespace Tests\Conjoon\Mail\Client\Message\Text;

use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor;
use Conjoon\Mail\Client\Message\Text\PreviewTextProcessor;
use Conjoon\Mail\Client\Message\Text\ProcessorException;
use Conjoon\Mail\Client\Reader\HtmlReadableStrategy;
use Conjoon\Mail\Client\Reader\PlainReadableStrategy;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Text\Converter;
use Tests\TestCase;

/**
 * Class DefaultPreviewTextProcessorTest
 * @package Tests\Conjoon\Mail\Client\Message\Text
 */
class DefaultPreviewTextProcessorTest extends TestCase
{


    /**
     * Test instance.
     */
    public function testInstance()
    {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(PreviewTextProcessor::class, $processor);
        $this->assertInstanceOf(
            ReadableMessagePartContentProcessor::class,
            $processor->getReadableMessagePartContentProcessor()
        );
    }


    /**
     * Test process w/ exception
     */
    public function testProcessException()
    {

        $this->expectException(ProcessorException::class);

        $processor = $this->createProcessor();

        $mp = new MessagePart("foo", "UTF-8", "image/jpg");

        $processor->process($mp);
    }


    /**
     * Test process
     */
    public function testProcess()
    {

        $processor = $this->createProcessor();

        $textPlain = new MessagePart(str_repeat("A", 300), "UTF-8", "text/plain");
        $textHtml = new MessagePart("<b>" . str_repeat("B", 300) . "</b>", "UTF-8", "text/html");

        $processedTextPlain = $processor->process($textPlain);
        $this->assertSame("CALLED" . str_repeat("A", 194), $processedTextPlain->getContents());

        $processedTextHtml = $processor->process($textHtml);
        $this->assertSame("CALLED" . str_repeat("B", 194), $processedTextHtml->getContents());

        $this->assertSame($textPlain, $processedTextPlain);
        $this->assertSame($textHtml, $processedTextHtml);

        $textHtml = new MessagePart(" A > B", "UTF-8", "text/html");
        $processedTextHtml = $processor->process($textHtml);
        $this->assertSame("CALLED A &gt; B", $processedTextHtml->getContents());
    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @return DefaultPreviewTextProcessor
     */
    protected function createProcessor(): DefaultPreviewTextProcessor
    {

        $proc = new class (
            $this->createConverter(),
            $this->createPlainReadableStrategy(),
            $this->createHtmlReadableStrategy()
        ) extends ReadableMessagePartContentProcessor {

            public function process(MessagePart $messagePart, string $toCharset = "UTF-8"): MessagePart
            {
                $messagePart = parent::process($messagePart, $toCharset);

                $messagePart->setContents(" CALLED" . $messagePart->getContents(), $toCharset);

                return $messagePart;
            }
        };

        return new DefaultPreviewTextProcessor($proc);
    }

    /**
     * @return Converter
     */
    protected function createConverter(): Converter
    {

        return new class implements Converter {
            public function convert(string $text, string $fromCharset, string $targetCharset): string
            {
                return $text;
            }
        };
    }

    /**
     * @return HtmlReadableStrategy
     */
    protected function createHtmlReadableStrategy(): HtmlReadableStrategy
    {

        return new class implements HtmlReadableStrategy {
            public function process(string $text): string
            {
                return $text;
            }
        };
    }

    /**
     * @return PlainReadableStrategy
     */
    protected function createPlainReadableStrategy(): PlainReadableStrategy
    {

        return new class implements PlainReadableStrategy {
            public function process(string $text): string
            {
                return $text;
            }
        };
    }
}
