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

use Conjoon\Illuminate\Auth\Imap\Util as ImapUtil;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class DefaultImapUserRepository provides a default implementation for an ImapUserProvider
 * which statically holds a list of Imap Server-configurations, against which the username is tested
 * (@see #match).
 * In order for this to work, the configuration feed into an instance of this class has to
 * contain a "match" property which is an array of Regular Expressions that are tested against
 * the $username passed to @see getUser.
 *
 *
 * @example
 *
 *    $repository = new DefaultImapUserRepository([
 *                      [
 *                          "id"              => "dev_sys_conjoon_org",
 *                          "inbox_type"      => "IMAP",
 *                          "inbox_address"   => "234.43.44.5",
 *                          "inbox_port"      => 993,
 *                          "inbox_ssl"       => true,
 *                          "outbox_address"  => "234.2.2.2",
 *                          "outbox_port"     => 993,
 *                          "outbox_ssl"      => true,
 *                          "match"           => ["/\@(conjoon.)(org|de|com|info)$/mi"]
 *                  ], [
 *                          "id"              => "imap_test",
 *                          "inbox_type"      => "IMAP",
 *                          "inbox_address"   => "1.1.1.0",
 *                          "inbox_port"      => 993,
 *                          "inbox_ssl"       => true,
 *                          "outbox_address"  => "1.1.1.0",
 *                          "outbox_port"     => 993,
 *                          "outbox_ssl"      => true,
 *                          "match"           => ["/\@(snafu)$/"]
 *                  ]])
 *
 *     $repository->getUser("test@foobar", "meh.");     // returns null
 *     $repository->getUser("test@conjoon.org", "foo"); // returns ImapUser containing the MailAccount
 *                                                      // with the id "dev_sys_conjoon_org"
 *     $repository->getUser("jon@conjoon.com", "foo");  // returns ImapUser containing the MailAccount
 *                                                      // with the id "dev_sys_conjoon_org"
 *
 *     $repository->getUser("dev@snafu", "foo");        // returns ImapUser containing the MailAccount
 *                                                      // with the id "imap_test"
 *
 *
 *
 * @package Conjoon\Illuminate\Auth\Imap
 */
class DefaultImapUserProvider implements ImapUserProvider
{

    /**
     * A list of Imap Server Configuration entries, set in the constructor.
     * @var array
     */
    protected array $entries;


    /**
     * DefaultImapUserRepository constructor.
     *
     * @param array $config An array of objects containing the following
     * key/value-pairs:
     *   "id"
     *   "inbox_type"
     *   "inbox_address"
     *   "inbox_port"
     *   "inbox_ssl"
     *   "outbox_address"
     *   "outbox_port"
     *   "outbox_ssl"
     *   "match"
     */
    public function __construct(array $config)
    {

        $this->entries = $config;
    }


    /**
     * @inheritdoc
     */
    public function getUser(string $username, string $password): ?ImapUser
    {

        foreach ($this->entries as $entry) {
            foreach ($entry['match'] as $regex) {
                if (preg_match($regex, $username) === 1) {
                    return new ImapUser($username, $password, ImapUtil::makeAccount(
                        $username,
                        $password,
                        $entry
                    ));
                }
            }
        }

        return null;
    }

// -------------- UserProvider

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return ImapUser|null
     */
    public function retrieveByCredentials(array $credentials): ?ImapUser
    {
        return $this->getUser($credentials["username"], $credentials["password"]);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return false;
    }
}
