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

declare(strict_types=1);

namespace Conjoon\Illuminate\Auth\Imap;

use Conjoon\Mail\Client\Data\MailAccount;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class ImapUser encapsulates a user for the php-ms-imapuser package, containing
 * associated MailAccount-information.
 *
 * @package Conjoon\Illuminate\Auth\Imap
 */
class ImapUser implements Authenticatable
{

    /**
     * @var string
     */
    protected string $username;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var MailAccount $mailAccount
     */
    private MailAccount $mailAccount;


    /**
     * ImapUser constructor.
     *
     * @param string $username
     * @param string $password
     * @param MailAccount $mailAccount
     */
    public function __construct(string $username, string $password, MailAccount $mailAccount)
    {
        $this->username = $username;
        $this->password = $password;

        $this->mailAccount = $mailAccount;
    }


    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }


    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }


    /**
     * Returns the MailAccount that belongs to this user with the specified
     * $mailAccountId.
     *
     * Returns null if no MailAccount with the specified id was found.
     *
     * @param string $mailAccountId
     *
     * @return MailAccount
     */
    public function getMailAccount(string $mailAccountId): ?MailAccount
    {

        if ($this->mailAccount->getId() !== $mailAccountId) {
            return null;
        }
        return $this->mailAccount;
    }

    /**
     * Returns an array of all MailAccounts belonging to this user.
     *
     * @return array
     */
    public function getMailAccounts(): array
    {
        return [$this->mailAccount];
    }


    /**
     * Returns an array representation of this user.
     *
     * @return array
     */
    public function toArray(): array
    {

        return [
            'username' => $this->username,
            'password' => $this->password
        ];
    }


// ------ Authenticable

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string|null
     */
    public function getAuthIdentifierName(): ?string
    {
        return null;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return void
     */
    public function getAuthIdentifier(): ?string
    {
        return null;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): ?string
    {
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }
}
