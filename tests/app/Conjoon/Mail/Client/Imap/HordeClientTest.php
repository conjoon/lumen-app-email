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
 * Class HordeClientTest
 */
class HordeClientTest extends TestCase {

    use TestTrait;


    /**
     * Tests constructor and base class.
     */
    public function testInstance() {

        $client = $this->createClient();
        $this->assertInstanceOf(\Conjoon\Mail\Client\MailClient::class, $client);
    }


    /**
     * Tests connect()
     */
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
    public function testGetMessageItemList_exception() {

        $this->expectException(ImapClientException::class);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('query')
            ->andThrow(new \Exception("This exception should be caught properly by the test"));

        $client = $this->createClient();
        $client->getMessageItemList(
            $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org"),
            "INBOX", ["start" => 0, "limit" => 25], function(){}
        );
    }


    /**
     * Multiple Message Item Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemList() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('search')->with("INBOX", \Mockery::any(), [
            "sort" => [\Horde_Imap_Client::SORT_REVERSE, \Horde_Imap_Client::SORT_DATE]
        ])->andReturn(["match" => new \Horde_Imap_Client_Ids([111, 222, 333])]);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();

        $fetchResults[111] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[111]->setUid(111);
        $fetchResults[222] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[222]->setUid(222);

        $fetchResults[111]->setEnvelope(['from' => "dev@conjoon.org", "to" => "devrec@conjoon.org"]);
        $fetchResults[222]->setEnvelope(['from' => "dev2@conjoon.org"]);


        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(),
            \Mockery::type('array')
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $messageItemList = $client->getMessageItemList($account, "INBOX", ["start" => 0, "limit" => 2], function($text){
            return "has been called";
        });


        $this->assertInstanceOf(\Conjoon\Mail\Client\Data\MessageItemList::class, $messageItemList);

        $this->assertSame(2, count($messageItemList));

        $this->assertSame("111", $messageItemList[0]->getMessageKey()->getId());
        $this->assertSame("INBOX", $messageItemList[0]->getMessageKey()->getMailFolderId());
        $this->assertSame("has been called", $messageItemList[0]->getPreviewText());
        $this->assertEquals(
            ["name" => "dev@conjoon.org", "address" => "dev@conjoon.org"], $messageItemList[0]->getFrom()->toArray()
        );
        $this->assertEquals(
            [["name" => "devrec@conjoon.org", "address" => "devrec@conjoon.org"]], $messageItemList[0]->getTo()->toArray()
        );
        $this->assertEquals(
            [], $messageItemList[1]->getTo()->toArray()
        );


    }


    /**
     * Single messageItem Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItem() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);


        $item = $client->getMessageItem($account, new \Conjoon\Mail\Client\Data\MessageKey("INBOX", "16"));

        $this->assertInstanceOf(\Conjoon\Mail\Client\Data\MessageItem::class, $item);
        $this->assertSame(null, $item->getFrom());

    }


    /**
     * Single MessageBody Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBody() {

        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);


        $messageBody = $client->getMessageBody($account, new \Conjoon\Mail\Client\Data\MessageKey("INBOX", "16"));

        $this->assertInstanceOf(\Conjoon\Mail\Client\Data\MessageBody::class, $messageBody);
    }


    /**
     * Tests getTotalMessageCount
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetTotalMessageCount() {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('search')->with("INBOX", \Mockery::any(), [])
                 ->andReturn(["match" => new \Horde_Imap_Client_Ids([111, 222, 333])]);

        $client = $this->createClient();

        $count = $client->getTotalMessageCount($account, "INBOX");
        $this->assertSame(3, $count);
    }


    /**
     * Tests getTotalUnreadCount
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetTotalUnreadCount() {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('status')->with("INBOX", \Horde_Imap_Client::STATUS_UNSEEN)
                 ->andReturn(["unseen" => 2]);

        $client = $this->createClient();

        $unreadCount = $client->getUnreadMessageCount($account, "INBOX");
        $this->assertSame(2, $unreadCount);
    }


// -------------------------------
//  Helper
// -------------------------------

    /**
     * Creates an instance of HordeClient.
     *
     * @return HordeCient
     */
    protected function createClient() :HordeClient {

        return new HordeClient(new CharsetConverter);
    }
}