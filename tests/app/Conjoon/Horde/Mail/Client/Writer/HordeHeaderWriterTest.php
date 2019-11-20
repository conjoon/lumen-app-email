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

use Conjoon\Mail\Client\Writer\HeaderWriter,
    Conjoon\Horde\Mail\Client\Writer\HordeHeaderWriter,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList;


class HordeHeaderWriterTest extends TestCase {


    public function testClass() {

        $strategy = new HordeHeaderWriter();
        $this->assertInstanceOf(HeaderWriter::class, $strategy);
    }


    public function testWrite() {

        $writer = new HordeHeaderWriter();

        $messageItemDraft = new MessageItemDraft();
        $messageItemDraft->setSubject("Test");
        $msgText = ["Subject: foobar"];
        $result  = [
            "Subject: Test",
            "Date: " . (new \DateTime())->format("r"),
            "User-Agent: php-conjoon"
        ];
        $msgText = implode("\n", $msgText);
        $result  = implode("\n", $result) . "\n\n";
        $this->assertSame($result, $writer->write($msgText, $messageItemDraft));


        $messageItemDraft = new MessageItemDraft();
        $messageItemDraft->setSubject("Test");
        $messageItemDraft->setTo($this->createAddress("to"));
        $messageItemDraft->setCc($this->createAddress("cc"));
        $messageItemDraft->setBcc($this->createAddress("bcc"));
        $messageItemDraft->setReplyTo($this->createAddress("replyTo"));
        $messageItemDraft->setFrom($this->createAddress("from"));
        $messageItemDraft->setDate(new \DateTime());

        $msgText = ["Subject: foobar"];
        $result  = [
            "Subject: Test",
            "From: " . $this->createAddress("from")->toString(),
            "Reply-To: " . $this->createAddress("replyTo")->toString(),
            "To: " . $this->createAddress("to")->toString(),
            "Cc: " . $this->createAddress("cc")->toString(),
            "Bcc: " . $this->createAddress("bcc")->toString(),
            "Date: " . (new \DateTime())->format("r"),
            "User-Agent: php-conjoon"
        ];
        $msgText = implode("\n", $msgText);
        $result  = implode("\n", $result) . "\n\n";
        $this->assertSame($result, $writer->write($msgText, $messageItemDraft));
    }


// +-----------------------------------
// | Helper
// +-----------------------------------

    /**
     * @param $type
     *
     * @return mixed
     */
    protected function createAddress($type) {

        $mailAddresses = [
            new MailAddress($type . "1@" . strtolower($type) . ".com", $type . "1"),
            new MailAddress($type . "2@" . strtolower($type) . ".com", $type . "2"),
            new MailAddress($type . "3@" . strtolower($type) . ".com", $type . "3")
        ];

        switch ($type) {

            case "to":
            case "cc":
            case "bcc":

                $list = new MailAddressList();
                foreach ($mailAddresses as $add) {
                    $list[] = $add;
                }
                return $list;

            case "from":
            case "replyTo":
                return $mailAddresses[0];

        }


    }


}