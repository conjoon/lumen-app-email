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

use Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor,
    Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor,
    Conjoon\Text\Converter,
    Conjoon\Mail\Client\Message\AbstractMessageItem,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey;

/**
 * Class PreviewTextProcessorTest
 * 
 */
class DefaultMessageItemFieldsProcessorTest extends TestCase {


    /**
     * Test instance.
     */
    public function testInstance() {
        $processor = $this->createProcessor();
        $this->assertInstanceOf(MessageItemFieldsProcessor::class, $processor);
    }


    /**
     * Test process
     */
    public function testProcess() {

        $processor = $this->createProcessor();

        $messageItem = new MessageItem(
            new MessageKey('dev', 'INBOX', '1'),
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
    protected function createProcessor() {

        $proc = new class($this->createConverter()) extends DefaultMessageItemFieldsProcessor {

            public $def;

            public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8") :AbstractMessageItem {

                $messagePart = parent::process($messageItem, $toCharset);

                $messagePart->setSubject("bar");

                return $messagePart;
            }
        };

        return $proc;
    }

    /**
     * @return Converter
     */
    protected function createConverter() :Converter{

        return new class implements Converter {
            public function convert(string $text, string $fromCharset, string $targetCharset) :string {
                return $text;
            }
        };

    }


}