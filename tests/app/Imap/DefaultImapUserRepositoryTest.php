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

use App\Imap\DefaultImapUserRepository,
    App\Imap\ImapUserRepository,
    App\Imap\ImapUser,
    App\Imap\ImapAccount;

class DefaultImapUserRepositoryTest extends TestCase
{
    public function testGetUser()
    {
        $config = [
            [
                "id"              => "dev_sys_conjoon_org",
                "inbox_type"      => "IMAP",
                "inbox_address"   => "234.43.44.5",
                "inbox_port"      => 993,
                "inbox_ssl"       => true,
                "outbox_address"  => "234.2.2.2",
                "outbox_port"     => 993,
                "outbox_ssl"      => true,
                "match"           => ["/\@(conjoon.)(org|de|com|info)$/mi"]
            ], [
                "id"              => "imap_test",
                "inbox_type"      => "IMAP",
                "inbox_address"   => "1.1.1.0",
                "inbox_port"      => 993,
                "inbox_ssl"       => true,
                "outbox_address"  => "1.1.1.0",
                "outbox_port"     => 993,
                "outbox_ssl"      => true,
                "match"           => ["/\@(snafu)$/i"]
            ]];


        $repository = new DefaultImapUserRepository($config);


        $this->assertInstanceOf(ImapUserRepository::class, $repository);

        $ret = $repository->getUser('a', 'b');
        $this->assertNull($ret);

        $user = $repository->getUser('devtest@conjoon.org', 'b');
        $this->assertInstanceOf(ImapUser::class, $user);
        $this->assertNull($user->getImapAccount('abc'));
        $this->assertInstanceOf(ImapAccount::class, $user->getImapAccount('dev_sys_conjoon_org'));
        $this->assertSame($config[0]['id'], $user->getImapAccount('dev_sys_conjoon_org')->getId());


        $user = $repository->getUser('@sNafu', 'b');
        $this->assertInstanceOf(ImapUser::class, $user);
        $this->assertNull($user->getImapAccount("dev_sys_conjoon_org"));
        $this->assertInstanceOf(ImapAccount::class, $user->getImapAccount("imap_test"));
        $this->assertSame($config[1]['id'], $user->getImapAccount("imap_test")->getId());

    }
}