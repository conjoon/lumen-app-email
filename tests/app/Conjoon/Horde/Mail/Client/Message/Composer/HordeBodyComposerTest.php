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

use Conjoon\Mail\Client\Message\Composer\BodyComposer,
    Conjoon\Horde\Mail\Client\Message\Composer\HordeBodyComposer,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\MessagePart;

/**
 * Class HordeBodyComposerTest
 *
 */
class HordeBodyComposerTest extends TestCase {


    /**
     * Test instance.
     */
    public function testInstance() {
        $composer = $this->createComposer();
        $this->assertInstanceOf(BodyComposer::class, $composer);
    }


    /**
     *
     */
    public function testTransform() {

        $messageBodyDraft = new MessageBodyDraft();
        $htmlPart = new MessagePart("foo", "UTF-8", "text/html");
        $plainPart = new MessagePart("bar", "UTF-8", "text/plain");
        $messageBodyDraft->setTextHtml($htmlPart);
        $messageBodyDraft->setTextPlain($plainPart);

        $composer = $this->createComposer();

        $txt = ["Content-Type: multipart/alternative;",
            "MIME-Version: 1.0",
            "",
            "This message is in MIME format.",
            "",
            "--=",
            "Content-Type: text/html; charset=utf-8",
            "",
            "foo",
            "--=",
            "Content-Type: text/plain; charset=utf-8",
            "",
            "bar",
            "--="];

        $default = ["Content-Type: multipart/alternative; boundary=\"_zu6MNuSkrIrVCCCyolqPVoz\"",
            "MIME-Version: 1.0",
            "",
            "This message is in MIME format.",
            "",
            "--=_zu6MNuSkrIrVCCCyolqPVoz",
            "Content-Type: text/html; charset=utf-8",
            "",
            "foo",
            "--=_zu6MNuSkrIrVCCCyolqPVoz",
            "Content-Type: text/plain; charset=utf-8",
            "",
            "bar",
            "--=_zu6MNuSkrIrVCCCyolqPVoz--"];

        $result = $composer->compose(implode("\n", $default), $messageBodyDraft);

        $result = explode("\n", $result);
        $caught = 0;
        foreach ($result as $idx => $line) {
            if (in_array($idx, [0, 5, 9, 13])) {
                $this->assertNotSame($default[$idx], $line);
                $this->assertContains($txt[$idx], $line);
                $caught++;
            } else {
                $this->assertSame($txt[$idx], $line);
                $caught++;
            }
        }

        $this->assertSame($caught, 14);

    }

// +--------------------------
// | Helper
// +--------------------------

    /**
     * @return HordeMessageBodyDraftToTextTransformer
     */
    protected function createComposer() :HordeBodyComposer {

        return new HordeBodyComposer();
    }
}
