<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey;


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
     * A json encoded array, encoded as a base64-string, containing information about the
     * mailAccountId, the mailFolderId and the messageItemId this draft references),
     * in this order.
     * This value will be set by the client once a draft gets saved that is created
     * for a reply-to/-all regarding a message, and will be reused once the draft
     * gets send to update the message represented by the info in this field with
     * appropriate message flags (e.g. \answered).
     *
     * @var string
     */
    protected $xCnDraftInfo;


    /**
     * @inheritdoc
     */
    public static function isHeaderField($field) {

        return parent::isHeaderField($field) || in_array($field, ["cc", "bcc", "replyTo"]);

    }


    /**
     * Sets the "messageKey" by creating a new MessageItemDraft with the specified
     * key and returning a new instance with this data.
     * No references to any data of the original instance will be available.
     * The state of Modifiable will not carry over.
     *
     * @param MessageKey $messageKey
     *
     * @return $this
     */
    public function setMessageKey(MessageKey $messageKey) :MessageItemDraft {

        $d = $this->toJson();

        $draft = new self($messageKey);

        $draft->suspendModifiable();
        foreach ($d as $key => $value) {

            if (in_array($key, ["id", "mailAccountId", "mailFolderId"])) {
                continue;
            }

            $setter = "set" . ucfirst($key);
            $getter = "get" . ucfirst($key);
            $copyable = $this->{$getter}();

            if ($copyable === null) {
                continue;
            }

            if (in_array($key, ["from", "replyTo", "to", "cc", "bcc"])) {
                if ($copyable) {
                    $draft->{$setter}($copyable->copy());
                }
            } else {
                $draft->{$setter}($this->{$getter}());
            }
        }
        $draft->resumeModifiable();
        return $draft;
    }


    /**
     * Sets the "cc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList $mailAddressList
     * @return $this
     */
    public function setCc(MailAddressList $mailAddressList = null) {
        $this->addModified("cc");
        $this->cc = $mailAddressList ? clone($mailAddressList) : null;
        return $this;
    }


    /**
     * Sets the "bcc" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList $mailAddressList
     * @return $this
     */
    public function setBcc(MailAddressList $mailAddressList = null) {
        $this->addModified("bcc");
        $this->bcc = $mailAddressList ? clone($mailAddressList) : null;
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
        $this->addModified("replyTo");
        $this->replyTo = $replyTo === null ? null : clone($replyTo);
        return $this;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public function toJson() :array{

        $data = array_merge(parent::toJson(), [
            'cc'      => $this->getCc() ? $this->getCc()->toJson() : [],
            'bcc'     => $this->getBcc() ? $this->getBcc()->toJson() : [],
            'replyTo' => $this->getReplyTo() ? $this->getReplyTo()->toJson() : []
        ]);

        return $data;
    }

}