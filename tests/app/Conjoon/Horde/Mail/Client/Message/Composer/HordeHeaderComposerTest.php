<?php
/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use Conjoon\Horde\Mail\Client\Message\Composer\HordeHeaderComposer,
    Conjoon\Mail\Client\Message\Composer\HeaderComposer,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList;


/**
 * Class HordeHeaderComposerTest
 *
 */
class HordeHeaderComposerTest extends TestCase {


    public function testClass() {

        $strategy = new HordeHeaderComposer();
        $this->assertInstanceOf(HeaderComposer::class, $strategy);
    }

    public function testSortHeaderFields() {

        $composer = new HordeHeaderComposer();

        $this->assertSame(
            ["date", "subject", "cc", "references", "foo", "bar"],
            $composer->sortHeaderFields(["foo", "bar", "cc", "date", "subject", "references"])
        );

    }


    public function testWrite_1() {

        $composer = new HordeHeaderComposer();

        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setSubject("Test");
        $msgText = ["Subject: foobar\nMessage-ID: foo"];
        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Subject: Test",
            "Message-ID: foo",
            "User-Agent: php-conjoon",
            "",
            ""
        ];

        $msgText = implode("\n", $msgText);

        $composedResult = $composer->compose($msgText, $messageItemDraft);

        $this->assertEquals($result, explode("\n", $composedResult));
    }


    public function testWrite_2() {

        $composer = new HordeHeaderComposer();


        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setSubject("Test");
        $messageItemDraft->setTo($this->createAddress("to"));
        $messageItemDraft->setCc($this->createAddress("cc"));
        $messageItemDraft->setBcc($this->createAddress("bcc"));
        $messageItemDraft->setReplyTo($this->createAddress("replyTo"));
        $messageItemDraft->setInReplyTo("messageidstr1");
        $messageItemDraft->setReferences("messageidstr2");
        $messageItemDraft->setFrom($this->createAddress("from"));
        $messageItemDraft->setDate(new \DateTime());

        $msgText = ["Subject: foobar\nMessage-Id: foobar"];
        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Subject: Test",
            "From: " . $this->createAddress("from")->toString(),
            "Reply-To: " . $this->createAddress("replyTo")->toString(),
            "To: " . $this->createAddress("to")->toString(),
            "Cc: " . $this->createAddress("cc")->toString(),
            "Bcc: " . $this->createAddress("bcc")->toString(),
            "Message-ID: foobar",
            "In-Reply-To: messageidstr1",
            "References: messageidstr2",
            "User-Agent: php-conjoon",
            "",
            ""
        ];
        $msgText = implode("\n", $msgText);
        $this->assertEquals($result, explode("\n", $composer->compose($msgText, $messageItemDraft)));
    }


    /**
     * Makes sure header fields are not removed if target fields are not
     * explicitely defined.
     */
    public function testFieldsAreNotRemoved() {

        $composer = new HordeHeaderComposer();

        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));

        $msgText = [
            "Subject: foobar",
            "From: a",
            "Reply-To: b",
            "To: c",
            "Cc: d",
            "Bcc: e",
            "Message-ID: snafu"
        ];
        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Subject: foobar",
            "From: a",
            "Reply-To: b",
            "To: c",
            "Cc: d",
            "Bcc: e",
            "Message-ID: snafu",
            "User-Agent: php-conjoon",
            "",
            ""
        ];
        $msgText = implode("\n", $msgText);

        $composed =  explode("\n", $composer->compose($msgText, $messageItemDraft));



        $this->assertEquals($result, $composed);

    }

    /**
     * Makes sure everything works as expected if header address fields are null
     */
    public function testNullAddress() {

        $composer = new HordeHeaderComposer();

        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));


        $messageItemDraft->setFrom(null);
        $messageItemDraft->setReplyTo(null);
        $messageItemDraft->setCc(null);
        $messageItemDraft->setBcc(null);
        $messageItemDraft->setTo(null);

        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Message-ID: foo",
            "User-Agent: php-conjoon",
            "",
            ""
        ];

        $composed = $composer->compose("BcC: test@me.com\nMessage-ID: foo", $messageItemDraft);


        $this->assertEquals($result, explode("\n", $composed));
    }


    public function testStringable() {

        $composer = new HordeHeaderComposer();


        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setSeen(true);

        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Message-ID: bar",
            "User-Agent: php-conjoon",
            "",
            ""
        ];

        $this->assertEquals($result, explode("\n", $composer->compose("Message-ID: bar", $messageItemDraft)));
    }


    public function testMessageId() {

        $composer = new HordeHeaderComposer();

        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setSeen(true);

        // should also set Message-ID of draft
        $composed = $composer->compose("", $messageItemDraft);

        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Message-ID: " . $messageItemDraft->getMessageId(),
            "User-Agent: php-conjoon",
            "",
            ""
        ];

        $this->assertEquals($result, explode("\n", $composed));
    }


    public function testXCnDraftInfo() {

        $composer = new HordeHeaderComposer();

        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setXCnDraftInfo("somestring");

        $composed = $composer->compose("", $messageItemDraft);

        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Message-ID: " . $messageItemDraft->getMessageId(),
            "User-Agent: php-conjoon",
            "X-Cn-Draft-Info: somestring",
            "",
            ""
        ];

        $this->assertEquals($result, explode("\n", $composed));
    }


    public function testWrite_emptyStrings() {

        $composer = new HordeHeaderComposer();


        $messageItemDraft = new MessageItemDraft(new MessageKey("a", "b", "c"));
        $messageItemDraft->setSubject("Test");
        $messageItemDraft->setReferences("");

        $msgText = ["Subject: foobar\nMessage-Id: foobar\nReferences: Id"];
        $result  = [
            "Date: " . (new \DateTime())->format("r"),
            "Subject: Test",
            "Message-ID: foobar",
            "User-Agent: php-conjoon",
            "",
            ""
        ];
        $msgText = implode("\n", $msgText);
        $this->assertEquals($result, explode("\n", $composer->compose($msgText, $messageItemDraft)));
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
