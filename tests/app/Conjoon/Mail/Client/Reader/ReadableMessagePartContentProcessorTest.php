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

require_once(dirname(__FILE__). "/../Message/Text/AbstractMessagePartContentProcessorTest.php");


use Conjoon\Mail\Client\Message\Text\AbstractMessagePartContentProcessor,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor,
    Conjoon\Text\Converter,
    Conjoon\Mail\Client\Reader\PlainReadableStrategy,
    Conjoon\Mail\Client\Reader\HtmlReadableStrategy;
/**
 * Class DefaultMessagePartContentProcessorTest
 *
 */
class ReadableMessagePartContentProcessorTest extends AbstractMessagePartContentProcessorTest {

    /**
     * Test instance.
     */
    public function testInstance() {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(AbstractMessagePartContentProcessor::class, $processor);
    }


    // +--------------------------
// | Helper
// +--------------------------

    /**
     * @return ReadableMessagePartContentProcessor
     */
    protected function createProcessor() :ReadableMessagePartContentProcessor {

        return new ReadableMessagePartContentProcessor(
            $this->createConverter(),
            $this->createPlainReadableStrategy(),
            $this->createHtmlReadableStrategy());
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
                return "<HTML>" . $text ;
            }
        };

    }

    /**
     * @return PlainReadableStrategy
     */
    protected function createPlainReadableStrategy() :PlainReadableStrategy{

        return new class implements PlainReadableStrategy {
            public function process(string $text) :string {
                return "PLAIN" . $text ;
            }
        };

    }


}
