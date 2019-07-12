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


/**
 * Class ImapUser encapsulates a user for the php-cn_imapuser package, containing
 * associated ImapAccount-information.
 *
 * @package App\Imap
 */
class ImapUser {


    /**
     * @var ImapAccount
     */
    protected $imapAccount;

    /**
     * @var string
     */
    protected $username;


    /**
     * ImapUser constructor.
     *
     * @param string $username
     * @param string $password
     * @param ImapAccount $account
     */
    public function __construct(string $username, string $password, ImapAccount $imapAccount) {

        $this->username = $username;
        $this->password = $password;

        $this->imapAccount = $imapAccount;
    }


    /**
     * @return string
     */
    public function getUsername() :string {
        return $this->username;
    }


    /**
     * @return string
     */
    public function getPassword() :string {
        return $this->password;
    }


    /**
     * @return ImapAccount
     */
    public function getImapAccount() :ImapAccount {
        return $this->imapAccount;
    }


    /**
     * Returns an array representation of this user.
     *
     * @return array
     */
    public function toArray() :array {

        return [
            'username' => $this->username,
            'password' => $this->password
        ];

    }

}