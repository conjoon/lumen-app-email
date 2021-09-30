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

use Conjoon\Mail\Client\Writer\DefaultPlainWritableStrategy,
    Conjoon\Mail\Client\Writer\PlainWritableStrategy,
    Conjoon\Mail\Client\Message\Text\PlainTextStrategy;


class DefaultPlainWritableStrategyTest extends TestCase {


    public function testClass() {

        $strategy = new DefaultPlainWritableStrategy();
        $this->assertInstanceOf(PlainWritableStrategy::class, $strategy);
        $this->assertInstanceOf(PlainTextStrategy::class, $strategy);
    }


    public function testProcess() {

        $strategy = new DefaultPlainWritableStrategy();

        $text = "<html><body>notosrandomstring <br /> 1 <br>2</body></html>";

        $result = "notosrandomstring \n 1 \n2";

        $this->assertSame($result, $strategy->process($text));
    }



}
