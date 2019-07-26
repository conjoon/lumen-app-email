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

namespace App\Imap\Service;

use Conjoon\Mail\Client\Data\MailAccount,
    Conjoon\Mail\Client\MailClient;

/**
 * Class DefaultMessageItemService.
 * Default implementation of a MessageItemService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultMessageItemService implements MessageItemService {


    /**
     * @var MailClient
     */
    protected $client;


    public function __construct(MailClient $client) {

        $this->client = $client;
    }

    public function getClient() :MailClient {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function getMessageItemsFor(MailAccount $account, string $mailFolderId, array $options) :array {
        return $this->client->getMessageItemsFor($account, $mailFolderId, $options);
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemFor(MailAccount $account, string $mailFolderId, string $messageItemId) :array {
        return $this->client->getMessageItemFor($account, $mailFolderId, $messageItemId);

    }


    /**
     * @inheritdoc
     */
    public function getMessageBodyFor(MailAccount $account, string $mailFolderId, string $messageItemId) :array {
        return $this->client->getMessageBodyFor($account, $mailFolderId, $messageItemId);
    }




}