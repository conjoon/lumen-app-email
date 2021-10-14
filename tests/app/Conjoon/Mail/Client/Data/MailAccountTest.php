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

use Tests\TestCase,
    Conjoon\Mail\Client\Data\MailAccount;


class MailAccountTest extends TestCase
{

    protected $accountConfig = [
        "id"               => "dev_sys_conjoon_org",
        "name"            => "conjoon developer",
        "from"            => ["name" => "John Smith", "address" => "dev@conjoon.org"],
        "replyTo"         => ["name" => "John Smith", "address" => "dev@conjoon.org"],
        "inbox_type"      => "IMAP",
        "inbox_address"   => "sfsffs.ffssf.sffs",
        "inbox_port"      => 993,
        "inbox_user"      => "inboxuser",
        "inbox_password"  => "inboxpassword",
        "inbox_ssl"       => true,
        "outbox_address"  => "sfsffs.ffssf.sffs",
        "outbox_port"     => 993,
        "outbox_user"     => "outboxuser",
        "outbox_password" => "outboxpassword",
        "outbox_ssl"      => true,
        "root"            => ["[Gmail]"]
    ];

    public function testGetter()
    {
        $config = $this->accountConfig;

        $oldRoot = $config["root"];
        $this->assertSame(["[Gmail]"], $oldRoot);
        unset($config["root"]);

        $account = new MailAccount($config);
        $this->assertSame(["INBOX"], $account->getRoot());

        $config["root"] = $oldRoot;
        $account = new MailAccount($config);

        foreach ($config as $property => $value) {


            if ($property === "from" || $property === "replyTo") {
                $method = $property == "from" ? "getFrom" : "getReplyTo";
            } else {
                $camelKey = "_" . str_replace("_", " ", strtolower($property));
                $camelKey = ltrim(str_replace(" ", "", ucwords($camelKey)), "_");
                $method   = "get" . ucfirst($camelKey);
            }

            $this->assertSame($value, $account->{$method}());
        }
    }


    public function testGetterException() {

        $config = $this->accountConfig;
        $account = new MailAccount($config);

        $this->expectException(\BadMethodCallException::class);

        $account->getSomeFoo();
    }


    public function testToArray() {

        $config = $this->accountConfig;

        $account = new MailAccount($config);

        $this->assertSame($config, $account->toArray());
    }

}
