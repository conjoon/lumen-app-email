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

declare(strict_types=1);

namespace Tests\Conjoon\Mail\Client\Message\Text;

use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Message\Text\AbstractMessagePartContentProcessor;
use Conjoon\Mail\Client\Message\Text\HtmlTextStrategy;
use Conjoon\Mail\Client\Message\Text\MessagePartContentProcessor;
use Conjoon\Mail\Client\Message\Text\PlainTextStrategy;
use Conjoon\Mail\Client\Message\Text\ProcessorException;
use Conjoon\Text\Converter;
use Tests\TestCase;

/**
 * Class AbstractMessagePartContentProcessorTest
 * @package Tests\Conjoon\Mail\Client\Message\Text
 */
class AbstractMessagePartContentProcessorTest extends TestCase
{


    /**
     * Test instance.
     */
    public function testInstance()
    {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(MessagePartContentProcessor::class, $processor);
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

        $textPlain = new MessagePart("IsPlain", "FROM UTF-8", "text/plain");
        $textHtml = new MessagePart("html", "FROM UTF-8", "text/html");

        $processedTextPlain = $processor->process($textPlain, "ABC");
        $this->assertSame("PLAINIsPlain FROM UTF-8 ABC", $textPlain->getContents());

        $processedTextHtml = $processor->process($textHtml, "ABC");
        $this->assertSame("<HTML>html FROM UTF-8 ABC", $textHtml->getContents());

        $this->assertSame($textPlain, $processedTextPlain);
        $this->assertSame($textHtml, $processedTextHtml);
    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @return AbstractMessagePartContentProcessor
     */
    protected function createProcessor()
    {

        return new class (
            $this->createConverter(),
            $this->createPlainTextStrategy(),
            $this->createHtmlTextStrategy()
        ) extends AbstractMessagePartContentProcessor {

        };
    }


    /**
     * @return Converter
     */
    protected function createConverter(): Converter
    {

        return new class implements Converter {
            public function convert(string $text, string $fromCharset, string $targetCharset): string
            {
                return implode(" ", [$text, $fromCharset, $targetCharset]);
            }
        };
    }

    /**
     * @return HtmlTextStrategy
     */
    protected function createHtmlTextStrategy(): HtmlTextStrategy
    {

        return new class implements HtmlTextStrategy {
            public function process(string $text): string
            {
                return "<HTML>" . $text;
            }
        };
    }

    /**
     * @return PlainTextStrategy
     */
    protected function createPlainTextStrategy(): PlainTextStrategy
    {

        return new class implements PlainTextStrategy {
            public function process(string $text): string
            {
                return "PLAIN" . $text;
            }
        };
    }
}
