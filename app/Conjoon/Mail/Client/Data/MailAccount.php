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

namespace Conjoon\Mail\Client\Data;

use BadMethodCallException;

/**
 * Class MailAccount models account information for a mail server.
 *
 * @example
 *
 *    $account = new MailAccount([
 *        "id"              => "dev_sys_conjoon_org",
 *        "name"            => "conjoon developer",
 *        "from"            => ["name" => "John Smith", "address" => "dev@conjoon.org"],
 *        "replyTo"         => ["name" => "John Smith", "address" => "dev@conjoon.org"],
 *        "inbox_type"      => "IMAP",
 *        "inbox_address"   => "sfsffs.ffssf.sffs",
 *        "inbox_port"      => 993,
 *        "inbox_user"      => "inboxuser",
 *        "inbox_password"  => "inboxpassword",
 *        "inbox_ssl"       => true,
 *        "outbox_address"  => "sfsffs.ffssf.sffs",
 *        "outbox_port"     => 993,
 *        "outbox_user"     => "outboxuser",
 *        "outbox_password" => "outboxpassword',
 *        'outbox_ssl'      => true,
 *        'root'            => "INBOX"
 *    ]);
 *
 *    $account->getOutboxSsl(); // true
 *    $account->getInboxPort(); // 993
 *    $account->getReplyTo();   // ['name' => 'John Smith', 'address' => 'dev@conjoon.org'],
 *
 * The property "root" allows for specifying a root mailbox and defaults to "INBOX".
 *
 * @package Conjoon\Mail\Client\Data
 *
 * @method string getName()
 * @method array getFrom()
 * @method array getReplyTo()
 * @method string getInboxUser()
 * @method string getInboxPassword()
 * @method string getOutboxUser()
 * @method string getOutboxPassword()
 * @method string getId()
 * @method string getInboxAddress()
 * @method string getInboxType()
 * @method int getInboxPort()
 * @method bool getInboxSsl()
 * @method string getOutboxAddress()
 * @method int getOutboxPort()
 * @method bool getOutboxSsl()
 * @method array getRoot()
 *
 * @noinspection SpellCheckingInspection
 */
class MailAccount
{

    /**
     * @var string
     */
    protected string $id;

    /**
     * @var string clear text name
     */
    protected string $name;

    /**
     * @var array name, address
     */
    protected array $from;

    /**
     * @var array name, address
     */
    protected array $replyTo;

    /**
     * @var string
     */
    protected string $inbox_type = 'IMAP';

    /**
     * @var string
     */
    protected string $inbox_address;

    /**
     * @var int
     */
    protected int $inbox_port;

    /**
     * @var string
     */
    protected string $inbox_user;

    /**
     * @var string
     */
    protected string $inbox_password;

    /**
     * @var boolean
     */
    protected bool $inbox_ssl;

    /**
     * @var string
     */
    protected string $outbox_address;

    /**
     * @var int
     */
    protected int $outbox_port;

    /**
     * @var string
     */
    protected string $outbox_user;

    /**
     * @var string
     */
    protected string $outbox_password;

    /**
     * @var boolean
     */
    protected bool $outbox_ssl;

    /**
     * @var array
     */
    protected array $root = ["INBOX"];

    /**
     * MailAccount constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }


    /**
     * Makes sure defined properties in this class are accessible via getter method calls.
     * If needed, camelized methods are resolved to their underscored representations in this
     * class.
     *
     * @param String $method
     * @param Mixed $arguments
     *
     * @return mixed
     *
     * @throws BadMethodCallException if a method is called for which no property exists
     */
    public function __call(string $method, $arguments)
    {

        if (strpos($method, 'get') === 0) {
            if ($method !== 'getReplyTo') {
                $method = substr($method, 3);
                $property = strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $method));
            } else {
                $property = 'replyTo';
            }

            if (property_exists($this, $property)) {
                return $this->{$property};
            }
        }


        throw new BadMethodCallException("no method named \"$method\" found.");
    }


    /**
     * Returns an Array representation of this instance.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "from" => $this->getFrom(),
            "replyTo" => $this->getReplyTo(),
            "inbox_type" => $this->getInboxType(),
            "inbox_address" => $this->getInboxAddress(),
            "inbox_port" => $this->getInboxPort(),
            "inbox_user" => $this->getInboxUser(),
            "inbox_password" => $this->getInboxPassword(),
            "inbox_ssl" => $this->getInboxSsl(),
            "outbox_address" => $this->getOutboxAddress(),
            "outbox_port" => $this->getOutboxPort(),
            "outbox_user" => $this->getOutboxUser(),
            "outbox_password" => $this->getOutboxPassword(),
            "outbox_ssl" => $this->getOutboxSsl(),
            "root" => $this->getRoot()
        ];
    }
}
