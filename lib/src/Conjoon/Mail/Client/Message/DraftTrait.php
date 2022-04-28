<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;

/**
 * Helper-Trait for defining draft-related information.
 *
 * Trait DraftTrait
 * @package Conjoon\Horde\Mail\Client\Message
 */
trait DraftTrait
{
    /**
     * @var MailAddressList|null
     */
    protected ?MailAddressList $cc = null;

    /**
     * @var MailAddressList|null
     */
    protected ?MailAddressList $bcc = null;

    /**
     * @var MailAddress|null
     */
    protected ?MailAddress $replyTo = null;

    /**
     * @var boolean
     */
    protected ?bool $draft = null;


    /**
     * Sets the "cc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList|null $mailAddressList
     * @return $this
     */
    public function setCc(MailAddressList $mailAddressList = null): AbstractMessageItem
    {
        $this->addModified("cc");
        $this->cc = $mailAddressList ? clone($mailAddressList) : null;
        return $this;
    }


    /**
     * Sets the "bcc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList|null $mailAddressList
     * @return $this
     */
    public function setBcc(MailAddressList $mailAddressList = null): AbstractMessageItem
    {
        $this->addModified("bcc");
        $this->bcc = $mailAddressList ? clone($mailAddressList) : null;
        return $this;
    }


    /**
     * Sets the "replyTo" property of this message.
     * Makes sure no reference to the MailAddress-object is stored.
     *
     * @param MailAddress|null $replyTo
     * @return $this
     */
    public function setReplyTo(MailAddress $replyTo = null): AbstractMessageItem
    {
        $this->addModified("replyTo");
        $this->replyTo = $replyTo === null ? null : clone($replyTo);
        return $this;
    }


    /**
     * @inheritdoc
     */
    public static function isHeaderField($field): bool
    {
        return parent::isHeaderField($field) || in_array($field, ["cc", "bcc", "replyTo"]);
    }
}
