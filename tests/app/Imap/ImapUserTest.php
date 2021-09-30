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

use App\Imap\ImapUser,
    Conjoon\Mail\Client\Data\MailAccount;

class ImapUserTest extends TestCase
{

    public function testClass()
    {
        $mailAccount = new MailAccount(array("id" => "foo"));
        $username    = "foo";
        $password     = "bar";

        $user = new ImapUser($username, $password, $mailAccount);

        $this->assertSame($username, $user->getUsername());
        $this->assertSame($password, $user->getPassword());
        $this->assertSame([
            "username" => $username,
            "password" => $password
        ], $user->toArray());

        $this->assertSame($mailAccount, $user->getMailAccount($mailAccount->getId()));

        $accounts = $user->getMailAccounts();

        foreach ($accounts as $account) {
            $this->assertSame($account, $user->getMailAccount($account->getId()));
        }


    }

}
