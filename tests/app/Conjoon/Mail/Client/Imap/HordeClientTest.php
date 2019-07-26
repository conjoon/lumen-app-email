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

use Conjoon\Mail\Client\Imap\HordeClient,
    Conjoon\Text\CharsetConverter,
    Conjoon\Mail\Client\Imap\ImapClientException;


/**
 * Class DefaultMessageItemServiceTest
 *
 */
class HordeClientTest extends TestCase {

    use TestTrait;


    public function testInstance() {

        $client = $this->createClient();
        $this->assertInstanceOf(\Conjoon\Mail\Client\MailClient::class, $client);
        $this->assertInstanceOf(\Conjoon\Text\Converter::class, $client->getConverter());
    }


    public function testConnect()
    {

        $client = $this->createClient();

        $socket = $client->connect($this->getTestMailAccount("dev_sys_conjoon_org"));

        $this->assertInstanceOf(
            \Horde_Imap_Client_Socket::class, $socket
        );

    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemsFor_exception() {

        $this->expectException(ImapClientException::class);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('query')
            ->andThrow(new \Exception("This exception should be caught properly by the test"));

        $client = $this->createClient();
        $client->getMessageItemsFor(
            $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org"),
            "INBOX", ["start" => 0, "limit" => 25]
        );
    }

    /**
     * Multiple Message Item Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemsFor() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('status')->with("INBOX", 16)->andReturn(["unseen" => 43]);

        $imapStub->shouldReceive('search')->with("INBOX", \Mockery::any(), [
            "sort" => [\Horde_Imap_Client::SORT_REVERSE, \Horde_Imap_Client::SORT_DATE]
        ])->andReturn(["match" => new \Horde_Imap_Client_Ids([111, 222, 333])]);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();

        $fetchResults[111] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[111]->setUid(111);
        $fetchResults[222] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[222]->setUid(222);

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(),
            \Mockery::type('array')
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $results = $client->getMessageItemsFor($account, "INBOX", ["start" => 0, "limit" => 2]);

        $this->assertSame([
            "cn_unreadCount" => 43, "mailFolderId" => "INBOX", "mailAccountId" => $account->getId()
        ], $results["meta"]
        );

        $this->assertSame(3, $results["total"]);

        $this->assertSame(2, count($results["data"]));

        $structure = [
            "id", "mailAccountId", "mailFolderId", "from", "to", "size", "subject",
            "date", "seen", "answered", "draft", "flagged", "recent", "previewText",
            "hasAttachments"
        ];

        foreach ($results["data"] as $item) {
            foreach ($structure as $key) {
                $this->assertArrayHasKey($key, $item);
            }
        }
    }


    /**
     * Single messageItem Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemFor() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);


        $item = $client->getMessageItemFor($account, "INBOX", "16");

        $this->assertSame([
            "id"             => "16",
            "mailAccountId"  => $account->getId(),
            "mailFolderId"   => "INBOX",
            "from"           => [],
            "to"             => [],
            "size"           => 0,
            "subject"        => "",
            "date"           => $fetchResults[16]->getEnvelope()->date->format("Y-m-d H:i"),
            "seen"           => false,
            "answered"       => false,
            "draft"          => false,
            "flagged"        => false,
            "recent"         => false,
            "hasAttachments" => false
        ], $item);
    }


    /**
     * Single MessageBody Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyFor() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);


        $body = $client->getMessageBodyFor($account, "INBOX", "16");

        $this->assertSame([
            "id"             => "16",
            "mailFolderId"   => "INBOX",
            "mailAccountId"  => $account->getId(),
            "textPlain"      => "",
            "textHtml"       => ""
        ], $body);
    }


    protected function createClient() {

        return new HordeClient(new CharsetConverter);
    }
}