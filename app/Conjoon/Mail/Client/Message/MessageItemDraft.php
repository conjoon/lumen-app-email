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

namespace Conjoon\Mail\Client\Message;

use Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\MailClientException;


/**
 * Class MessageItemDraft models envelope informations of a Message Draft.
 * A MessageDraft that was stored and exists physically provides also a MessageKey.
 *
 * @package Conjoon\Mail\Client\Message
 */
class MessageItemDraft extends AbstractMessageItem {

    /**
     * @var MailAddressList
     */
    protected $cc;

    /**
     * @var MailAddressList
     */
    protected $bcc;

    /**
     * @var MailAddress
     */
    protected $replyTo;

    /**
     * @var boolean
     */
    protected $draft = true;


    /**
     * Sets the "messageKey" property of this message.
     * Throws an exception if the messageKey was already set.
     *
     * @param MessageKey $messageKey
     *
     * @return $this
     */
    public function setMessageKey(MessageKey $messageKey) :AbstractMessageItem {
        if ($this->messageKey) {
            throw new MailClientException("\"messageKey\" was already set.");
        }
        $this->messageKey = $messageKey;
        return $this;
    }


    /**
     * Sets the "cc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList $mailAddressList
     * @return $this
     */
    public function setCc(MailAddressList $mailAddressList) {
        $this->cc = clone($mailAddressList);
        return $this;
    }


    /**
     * Sets the "bcc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList $mailAddressList
     * @return $this
     */
    public function setBcc(MailAddressList $mailAddressList) {
        $this->bcc = clone($mailAddressList);
        return $this;
    }


    /**
     * Sets the "replyTo" property of this message.
     * Makes sure no reference to the MailAddress-object is stored.
     *
     * @param MailAddress $replyTo
     * @return $this
     */
    public function setReplyTo(MailAddress $replyTo = null) {
        $this->replyTo = $replyTo === null ? null : clone($replyTo);
        return $this;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------
    /**
     * Returns an array representing this MessageItem.
     *
     * @return array
     */
    public function toJson() :array{

        return array_merge(parent::toJson(), [
            'cc'      => $this->getCc() ? $this->getCc()->toJson() : [],
            'bcc'     => $this->getBcc() ? $this->getBcc()->toJson() : [],
            'replyTo' => $this->getReplyTo() ? $this->getReplyTo()->toJson() : []
        ]);
    }
}