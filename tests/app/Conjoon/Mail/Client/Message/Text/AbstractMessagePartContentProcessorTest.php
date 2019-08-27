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

use Conjoon\Mail\Client\Message\Text\MessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\AbstractMessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\ProcessorException,
    Conjoon\Text\Converter,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Reader\HtmlReadableStrategy;

/**
 * Class DefaultMessagePartContentProcessorTest
 * 
 */
class AbstractMessagePartContentProcessorTest extends TestCase {


    /**
     * Test instance.
     */
    public function testInstance() {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(MessagePartContentProcessor::class, $processor);
    }


    /**
     * Test process w/ exception
     */
    public function testProcess_exception() {

        $this->expectException(ProcessorException::class);

        $processor = $this->createProcessor();

        $mp = new MessagePart("foo", "UTF-8", "image/jpg");

        $processor->process($mp);
    }


    /**
     * Test process
     */
    public function testProcess() {

        $processor = $this->createProcessor();

        $textPlain = new MessagePart("plain", "FROMUTF-8", "text/plain");
        $textHtml = new MessagePart("html", "FROMUTF-8", "text/html");

        $processedTextPlain = $processor->process($textPlain, "ABC");
        $this->assertSame("plain FROMUTF-8 ABC", $textPlain->getContents());

        $processedTextHtml = $processor->process($textHtml, "ABC");
        $this->assertSame("<HTMLREADABLE>html FROMUTF-8 ABC", $textHtml->getContents());

        $this->assertSame($textPlain, $processedTextPlain);
        $this->assertSame($textHtml, $processedTextHtml);
    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @return DefaultMessagePartContentProcessor
     */
    protected function createProcessor() {
        return new class($this->createConverter(), $this->createHtmlReadableStrategy()) extends AbstractMessagePartContentProcessor{

        };
    }


    /**
     * @return Converter
     */
    protected function createConverter() :Converter{

        return new class implements Converter {
            public function convert(string $text, string $fromCharset, string $targetCharset) :string {
                return implode(" ", [$text, $fromCharset, $targetCharset]);
            }
        };

    }

    /**
     * @return HtmlReadableStrategy
     */
    protected function createHtmlReadableStrategy() :HtmlReadableStrategy{

        return new class implements HtmlReadableStrategy {
            public function process(string $text) :string {
                return "<HTMLREADABLE>" . $text ;
            }
        };

    }

}