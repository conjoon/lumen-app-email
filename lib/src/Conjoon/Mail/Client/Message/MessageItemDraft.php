<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\MailClientException;

/**
 * Class MessageItemDraft models envelope information of a Message Draft.
 * A MessageDraft that was stored and exists physically provides also a MessageKey.
 *
 * @package Conjoon\Mail\Client\Message
 * @method getReplyTo()
 * @method getBcc()
 * @method getCc()
 * @method getXCnDraftInfo()
 */
class MessageItemDraft extends AbstractMessageItem
{
    use DraftTrait;


    /**
     * A json encoded array, encoded as a base64-string, containing information about the
     * mailAccountId, the mailFolderId and the messageItemId this draft references),
     * in this order.
     * This value will be set by the client once a draft gets saved that is created
     * for a reply-to/-all regarding a message, and will be reused once the draft
     * gets send to update the message represented by the info in this field with
     * appropriate message flags (e.g. \answered).
     *
     * @var string|null
     */
    protected ?string $xCnDraftInfo = null;


    /**
     * @inheritdoc
     */
    public function __construct(MessageKey $messageKey, array $data = null)
    {
        $this->draft = true;
        parent::__construct($messageKey, $data);
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
    public function setMessageKey(MessageKey $messageKey): MessageItemDraft
    {

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
     * Sets the xCnDraftInfo for this MessageItemDraft and throws if
     * the value was already set.
     *
     * @param string|null $xCnDraftInfo
     * @return $this
     */
    public function setXCnDraftInfo(string $xCnDraftInfo = null): MessageItemDraft
    {

        if (is_string($this->getXCnDraftInfo())) {
            throw new MailClientException("\"xCnDraftInfo\" was already set.");
        }

        $this->xCnDraftInfo = $xCnDraftInfo;

        return $this;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public function toJson(): array
    {
        $data = array_merge(parent::toJson(), [
            'cc' => $this->getCc() ? $this->getCc()->toJson() : null,
            'bcc' => $this->getBcc() ? $this->getBcc()->toJson() : null,
            'replyTo' => $this->getReplyTo() ? $this->getReplyTo()->toJson() : null
        ]);

        return $this->buildJson($data);
    }
}
