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

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Message\AbstractMessageItem;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor;
use Conjoon\Text\Converter;
use Tests\TestCase;

/**
 * Class DefaultMessageItemFieldsProcessorTest
 * @package Tests\Conjoon\Mail\Client\Message\Text
 */
class DefaultMessageItemFieldsProcessorTest extends TestCase
{


    /**
     * Test instance.
     */
    public function testInstance()
    {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(MessageItemFieldsProcessor::class, $processor);
    }


    /**
     * Test process
     */
    public function testProcess()
    {

        $processor = $this->createProcessor();

        $messageItem = new MessageItem(
            new MessageKey("dev", "INBOX", "1"),
            ["charset" => "utf-8", "subject" => "foo"]
        );

        $processor->process($messageItem, "ISO-8859-1");

        $this->assertSame("bar", $messageItem->getSubject());
        $this->assertSame("ISO-8859-1", $messageItem->getCharset());
    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @return DefaultMessageItemFieldsProcessor
     */
    protected function createProcessor()
    {

        return new class ($this->createConverter()) extends DefaultMessageItemFieldsProcessor {

            public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8"): AbstractMessageItem
            {

                $messagePart = parent::process($messageItem, $toCharset);

                $messagePart->setSubject("bar");

                return $messagePart;
            }
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
                return $text;
            }
        };
    }
}
