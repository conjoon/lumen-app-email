<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use BadMethodCallException;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\MailClientException;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlaggedFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\SeenFlag;
use Conjoon\Util\Jsonable;
use Conjoon\Util\Modifiable;
use Conjoon\Util\ModifiableTrait;
use DateTime;
use TypeError;

/**
 * Class MessageItem models simplified envelope information for a Mail Message.
 *
 * @example
 *
 *    class MessageItem extends AbstractMessageItem  {}
 *
 *    $item = new MessageItem(
 *              ["date" => new \DateTime()]
 *            );
 *
 *    $item->setSubject("Foo");
 *    $item->getSubject(); // "Foo"
 *
 * @package Conjoon\Mail\Client\Message
 * @method setSubject(string $convert)
 * @method getSubject()
 * @method getCharset()
 * @method setCharset(string $toCharset)
 * @method getMessageKey()
 * @method getFrom()
 * @method getTo()
 * @method getDate()
 * @method getSeen()
 * @method setSeen(bool $true)
 * @method getAnswered()
 * @method getDraft()
 * @method getFlagged()
 * @method getRecent()
 * @method getMessageId()
 * @method getReferences()
 * @method getInReplyTo()
 */
abstract class AbstractMessageItem implements Jsonable, Modifiable
{
    use ModifiableTrait;

    /**
     * @var MessageKey
     */
    protected MessageKey $messageKey;

    /**
     * @var MailAddress|null
     */
    protected ?MailAddress $from = null;

    /**
     * @var MailAddressList|null
     */
    protected ?MailAddressList $to = null;

    /**
     * @var string|null
     */
    protected ?string $subject = null;

    /**
     * @var DateTime|null
     */
    protected ?DateTime $date = null;

    /**
     * @var bool|null
     */
    protected ?bool $seen = null;

    /**
     * @var bool|null
     */
    protected ?bool $answered = null;

    /**
     * @var bool|null
     */
    protected ?bool $draft = null;

    /**
     * @var bool|null
     */
    protected ?bool $flagged = null;

    /**
     * @var bool|null
     */
    protected ?bool $recent = null;

    /**
     * @var string|null
     */
    protected ?string $charset = null;

    /**
     * @var string|null
     */
    protected ?string $messageId = null;

    /**
     * @var string|null
     */
    protected ?string $inReplyTo = null;

    /**
     * @var string|null
     */
    protected ?string $references = null;


    /**
     * Returns true is the specified field is a header field.
     *
     * @param $field
     *
     * @return boolean
     */
    public static function isHeaderField($field): bool
    {

        return in_array($field, ["from", "to", "subject", "date", "inReplyTo", "references"]);
    }


    /**
     * MessageItem constructor.
     *
     * @param MessageKey $messageKey
     * @param array|null $data
     *
     *
     */
    public function __construct(MessageKey $messageKey, array $data = null)
    {

        $this->messageKey = $messageKey;

        if (!$data) {
            return;
        }

        $this->suspendModifiable();
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $method = "set" . ucfirst($key);
                $this->{$method}($value);
            }
        }
        $this->resumeModifiable();
    }


    /**
     * Sets the "to" property of this message.
     * Makes sure no reference to the MailAddressList-object is stored.
     *
     * @param MailAddressList|null $mailAddressList
     * @return $this
     */
    public function setTo(MailAddressList $mailAddressList = null): AbstractMessageItem
    {
        $this->addModified("to");
        $this->to = $mailAddressList ? clone($mailAddressList) : null;
        return $this;
    }


    /**
     * Sets the "from" property of this message.
     * Makes sure no reference to the MailAddress-object is stored.
     *
     * @param MailAddress|null $mailAddress
     * @return $this
     */
    public function setFrom(MailAddress $mailAddress = null): AbstractMessageItem
    {
        $this->addModified("from");
        $this->from = $mailAddress === null ? null : clone($mailAddress);
        return $this;
    }


    /**
     * Sets the Date of this message.
     * Makes sure no reference is stored to the date-object.
     *
     * @param DateTime $date
     * @return $this
     */
    public function setDate(DateTime $date): AbstractMessageItem
    {
        $this->addModified("date");
        $this->date = clone($date);
        return $this;
    }


    /**
     * Sets the messageId of this MessageItem and throws if a value was already
     * set.
     *
     * @param string $messageId
     * @return AbstractMessageItem
     */
    public function setMessageId(string $messageId): AbstractMessageItem
    {
        if ($this->getMessageId()) {
            throw new MailClientException("\"messageId\" was already set.");
        }

        $this->messageId = $messageId;

        return $this;
    }


    /**
     * Sets the inReplyTo of this MessageItem and throws if a value was already
     * set.
     *
     * @param string|null $inReplyTo
     *
     * @return AbstractMessageItem
     */
    public function setInReplyTo(string $inReplyTo = null): AbstractMessageItem
    {
        if (!is_null($this->getInReplyTo())) {
            throw new MailClientException("\"inReplyTo\" was already set.");
        }

        $this->__call("setInReplyTo", [$inReplyTo]);

        return $this;
    }


    /**
     * Sets the references of this MessageItem and throws if a value was already
     * set.
     *
     * @param string|null $references
     * @return AbstractMessageItem
     */
    public function setReferences(string $references = null): AbstractMessageItem
    {
        if (!is_null($this->getReferences())) {
            throw new MailClientException("\"references\" was already set.");
        }

        $this->__call("setReferences", [$references]);

        return $this;
    }


    /**
     * Makes sure defined properties in this class are accessible via getter method calls.
     *
     * @param String $method
     * @param Mixed $arguments
     *
     * @return mixed The value of the property if a getter was called, otherwise this instance
     * if a property was successfully set.
     *
     * @throws BadMethodCallException if a method is called for which no property exists
     * @throws TypeError if a value is of the wrong type for a property.
     */
    public function __call(string $method, $arguments)
    {
        $isSetter = false;

        if (
            ($isGetter = strpos($method, 'get') === 0) ||
            ($isSetter = strpos($method, 'set') === 0)
        ) {
            $property = lcfirst(substr($method, 3));

            if ($isGetter) {
                if (property_exists($this, $property)) {
                    return $this->{$property};
                }
            } elseif ($isSetter) {
                if (
                    property_exists($this, $property) &&
                    !in_array($property, ['messageKey'])
                ) {
                    $value = $arguments[0];

                    if (($this->checkType($property, $value)) !== true) {
                        throw new TypeError("Wrong type for \"$property\" submitted");
                    }

                    $this->addModified($property);
                    $this->{$property} = $value;
                    return $this;
                }
            }
        }

        throw new BadMethodCallException("no method named \"$method\" found.");
    }


    /**
     * Returns a FlagList representation of all flags set for this MessageItem.
     *
     * @return FlagList
     */
    public function getFlagList(): FlagList
    {
        $flagList = new FlagList();

        $this->getDraft() !== null && $flagList[] = new DraftFlag($this->getDraft());
        $this->getSeen() !== null && $flagList[] = new SeenFlag($this->getSeen());
        $this->getFlagged() !== null && $flagList[] = new FlaggedFlag($this->getFlagged());

        return $flagList;
    }


    /**
     * Helper for __call to determine if the proper type for a property is submitted
     * when using magic set* methods.
     *
     * @param string $property
     * @param mixed $value
     *
     * @return bool|string Returns true if the passed $value matches the expected type
     * of $property, otherwise a string containing the expected type.
     */
    protected function checkType(string $property, $value)
    {
        switch ($property) {
            case "inReplyTo":
            case "references":
                if (!is_string($value) && !is_null($value)) {
                    return "string or null";
                }
                break;

            case "charset":
            case "subject":
            case "messageId":
                if (!is_string($value)) {
                    return "string";
                }
                break;

            case "seen":
            case "recent":
            case "draft":
            case "flagged":
            case "answered":
                if (!is_bool($value)) {
                    return "bool";
                }
                break;
        }

        return true;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MessageItem.
     *
     * @return array
     */
    public function toJson(): array
    {
        $mk = $this->getMessageKey();

        $data = array_merge($mk->toJson(), [
            'from' => $this->getFrom() ? $this->getFrom()->toJson() : null,
            'to' => $this->getTo() ? $this->getTo()->toJson() : null,
            'subject' => $this->getSubject(),
            'date' => $this->getDate() ? $this->getDate()->format("Y-m-d H:i:s O") : null,
            'seen' => $this->getSeen(),
            'answered' => $this->getAnswered(),
            'draft' => $this->getDraft(),
            'flagged' => $this->getFlagged(),
            'recent' => $this->getRecent(),
            'messageId' => $this->getMessageId(),
            'references' => $this->getReferences(),
        ]);

        return $this->buildJson($data);
    }


    /**
     * Helper Method to assemble json arrays.
     *
     * @param array $thisData
     * @return array
     */
    protected function buildJson(array $thisData): array
    {
        $thisData = array_filter(
            $thisData,
            fn ($item) => $item !== null
        );

        ksort($thisData);

        return $thisData;
    }
}
