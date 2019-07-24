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
declare(strict_types=1);

namespace App\Imap;

use Conjoon\Mail\Client\Data\MailAccount;

/**
 * Class Util
 *
 * @package App\Imap
 */
class Util  {


    /**
     * Creates an MailAccount by merging information from $config with $username and $password.
     *
     * @example
     *       $iaccount = Util::makeAccount("dev@conjoon.org", "foo", [
     *                          "id"              => "dev_sys_conjoon_org",
     *                          "inbox_type"      => "IMAP",
     *                          "inbox_address"   => "234.43.44.5",
     *                          "inbox_port"      => 993,
     *                          "inbox_ssl"       => true,
     *                          "outbox_address"  => "234.2.2.2",
     *                          "outbox_port"     => 993,
     *                          "outbox_ssl"      => true,
     *                          "match"           => ["/\@(conjoon.)(org|de|com|info)$/mi"]]
     *       );
     *
     *       // dump $account
             //  'id'              => "dev_sys_conjoon_org",
     *       //  'name'            => "dev@conjoon.org",
     *       //  'from'            => ["name" => "dev@conjoon.org", "address" => 'dev@conjoon.org'],
     *       //  'replyTo'         => ["name" => "dev@conjoon.org", "address" => 'dev@conjoon.org'],
     *       //  'inbox_type'      => 'IMAP',
     *       //  'inbox_address'   => 'sfsffs.ffssf.sffs',
     *       //  'inbox_port'      => 993,
     *       //  'inbox_user'      => "dev@conjoon.org",
     *       //  'inbox_password'  => 'foo',
     *       //  'inbox_ssl'       => true,
     *       //  'outbox_address'  => 'sfsffs.ffssf.sffs',
     *       //  'outbox_port'     => 993,
     *       //  'outbox_user'     => "dev@conjoon.org",
     *       //  'outbox_password' => 'foo',
     *       //  'outbox_ssl'      => true
     *
     *
     * @param string $username
     * @param string $password
     * @param array $config
     *
     * @return MailAccount
     */
    public static function makeAccount(string $username, string $password, array $config): MailAccount {

        $config['name']    = $username;
        $config['from']    = ["name" => $username, "address" => $username];
        $config['replyTo'] = ["name" => $username, "address" => $username];

        $config['inbox_user']     = $username;
        $config['inbox_password'] = $password;

        $config['outbox_user']     = $username;
        $config['outbox_password'] = $password;

        return new MailAccount($config);
    }



}