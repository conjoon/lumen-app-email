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

use App\Imap\Util,
    Conjoon\Mail\Client\Data\MailAccount;

class UtilTest extends TestCase
{

    protected $config = [
        'id'               => "dev_sys_conjoon_org",
        'inbox_type'      => 'IMAP',
        'inbox_address'   => 'sfsffs.ffssf.sffs',
        'inbox_port'      => 993,
        'inbox_ssl'       => true,
        'outbox_address'  => 'sfsffs.ffssf.sffs',
        'outbox_port'     => 993,
        'outbox_ssl'      => true
    ];

    public function testMake()
    {
        $config = $this->config;

        $username = "dev@conjoon.org";
        $password = "foo";

        $account = Util::makeAccount($username, $password, $this->config);

        $this->assertInstanceOf(MailAccount::class, $account);

        foreach ($this->config as $property => $value) {
                $camelKey = '_' . str_replace('_', ' ', strtolower($property));
                $camelKey = ltrim(str_replace(' ', '', ucwords($camelKey)), '_');
                $method   = 'get' . ucfirst($camelKey);

            $this->assertSame($value, $account->{$method}());
        }

        $address = ["name" => $username, "address" => $username];

        $this->assertSame($username, $account->getName());

        $this->assertSame($address, $account->getFrom());
        $this->assertSame($address, $account->getReplyTo());

        $this->assertSame($username, $account->getInboxUser());
        $this->assertSame($password, $account->getInboxPassword());

        $this->assertSame($username, $account->getOutboxUser());
        $this->assertSame($password, $account->getOutboxPassword());

    }

}
