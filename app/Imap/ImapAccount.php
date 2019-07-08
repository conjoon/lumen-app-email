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
 * Class ImapAccount models account information for an imap server.
 *
 * @example
 *
 *    $account = new ImapAccount([
 *        'id'              => "dev_sys_conjoon_org",
 *        'name'            => "conjoon developer",
 *        'from'            => ["name" => 'John Smith', "address" => 'dev@conjoon.org'],
 *        'replyTo'         => ["name" => 'John Smith', "address" => 'dev@conjoon.org'],
 *        'inbox_type'      => 'IMAP',
 *        'inbox_address'   => 'sfsffs.ffssf.sffs',
 *        'inbox_port'      => 993,
 *        'inbox_user'      => 'inboxuser',
 *        'inbox_password'  => 'inboxpassword',
 *        'inbox_ssl'       => true,
 *        'outbox_address'  => 'sfsffs.ffssf.sffs',
 *        'outbox_port'     => 993,
 *        'outbox_user'     => 'outboxuser',
 *        'outbox_password' => 'outboxpassword',
 *        'outbox_ssl'      => true
 *    ]);
 *
 *    $account->getOutboxSsl(); // true
 *    $account->getInboxPort(); // 993
 *    $account->getReplyTo();   // ['name' => 'John Smith', 'address' => 'dev@conjoon.org'],
 *
 *
 *
 * @package App\Imap
 */
class ImapAccount implements \Illuminate\Contracts\Support\Arrayable {


    /**
     * @var
     */
    protected $id;

    /**
     * @var string clear text name
     */
    protected $name;

    /**
     * @var array name, address
     */
    protected $from;

    /**
     * @var array name, address
     */
    protected $replyTo;

    /**
    * @var string
    */
    protected $inbox_type = 'IMAP';

    /**
     * @var string
     */
    protected $inbox_address;

    /**
     * @var int
     */
    protected $inbox_port;

    /**
     * @var string
     */
    protected $inbox_user;

    /**
     * @var string
     */
    protected $inbox_password;

    /**
     * @var boolean
     */
    protected $inbox_ssl;

    /**
     * @var string
     */
    protected $outbox_address;

    /**
     * @var int
     */
    protected $outbox_port;

    /**
     * @var string
     */
    protected $outbox_user;

    /**
     * @var string
     */
    protected $outbox_password;

    /**
     * @var boolean
     */
    protected $outbox_ssl;


    /**
     * ImapAccount constructor.
     *
     * @param array $config
     */
    public function __construct(array $config) {


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
     * @throws \RuntimeException if a method is called for which no property exists
     */
    public function __call($method, $arguments) {

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

        throw new \RuntimeException("no method \"".$method."\" found.");
    }


    /**
     * @inheritdoc
     */
    public function toArray() {
        return [
            "id"              => $this->getId(),
            "name"            => $this->getName(),
            "from"            => $this->getFrom(),
            "replyTo"         => $this->getReplyTo(),
            "inbox_type"      => $this->getInboxType(),
            "inbox_address"   => $this->getInboxAddress(),
            "inbox_port"      => $this->getInboxPort(),
            "inbox_user"      => $this->getInboxUser(),
            "inbox_password"  => $this->getInboxPassword(),
            "inbox_ssl"       => $this->getInboxSsl(),
            "outbox_address"  => $this->getOutboxAddress(),
            "outbox_port"     => $this->getOutboxPort(),
            "outbox_user"     => $this->getOutboxUser(),
            "outbox_password" => $this->getOutboxPassword(),
            "outbox_ssl"      => $this->getOutboxSsl()
        ];
    }

}