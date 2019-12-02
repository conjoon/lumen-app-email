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
    Conjoon\Util\Jsonable,
    Conjoon\Util\Modifiable,
    Conjoon\Util\ModifiableTrait,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\DraftFlag,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey;

/**
 * Class MessageItem models simplified envelope informations for a Mail Message.
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
 */
abstract class AbstractMessageItem implements Jsonable, Modifiable {

    use ModifiableTrait;

    /**
     * @var MessageKey
     */
    protected $messageKey;

    /**
     * @var MailAddress
     */
    protected $from;

    /**
     * @var MailAddressList
     */
    protected $to;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var bool
     */
    protected $seen;

    /**
     * @var bool
     */
    protected $answered;

    /**
     * @var bool
     */
    protected $draft;

    /**
     * @var bool
     */
    protected $flagged;

    /**
     * @var bool
     */
    protected $recent;


    /**
     * Returns true is the specified field is a header field.
     *
     * @param $field
     *
     * @return boolean
     */
    public static function isHeaderField($field) {

        return in_array($field, ["from", "to", "subject", "date"]);

    }


    /**
     * MessageItem constructor.
     *
     * @param MessageKey $messageKey
     * @param array $data
     *
     *
     * @throws \TypeError if any of the submitted values for the properties do not match
     * their expected type
     */
    public function __construct(MessageKey $messageKey, array $data = null) {

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
     * @param MailAddressList $mailAddressList
     * @return $this
     */
    public function setTo(MailAddressList $mailAddressList = null) {
        $this->addModified("to");
        $this->to = $mailAddressList ? clone($mailAddressList) : null;
        return $this;
    }


    /**
     * Sets the "from" property of this message.
     * Makes sure no reference to the MailAddress-object is stored.
     *
     * @param MailAddress $mailAddress
     * @return $this
     */
    public function setFrom(MailAddress $mailAddress = null) {
        $this->addModified("from");
        $this->from = $mailAddress === null ? null : clone($mailAddress);
        return $this;
    }


    /**
     * Sets the Date of this message.
     * Makes sure no reference is stored to the date-object.
     *
     * @param \DateTime $date
     * @return $this
     */
    public function setDate(\DateTime $date) {
        $this->addModified("date");
        $this->date = clone($date);
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
     * @throws \BadMethodCallException if a method is called for which no property exists
     * @throws \TypeError if a value is of the wrong type for a property.
     */
    public function __call($method, $arguments) {

        
        if (($isGetter = strpos($method, 'get') === 0) || 
            ($isSetter = strpos($method, 'set') === 0)) {

            $property = lcfirst(substr($method, 3));

            if ($isGetter) {
                if (property_exists($this, $property)) {
                    return $this->{$property};
                }    
            } else if ($isSetter) {

                if (property_exists($this, $property) && 
                    !in_array($property, ['messageKey'])) {

                    $value = $arguments[0];

                    if (($typeFail = $this->checkType($property, $value)) !== true) {
                        throw new \TypeError("Wrong type for \"$property\" submitted");
                    }

                    $this->addModified($property);
                    $this->{$property} = $value;
                    return $this;
                }
            }

            
        }

        throw new \BadMethodCallException("no method \"".$method."\" found.");
    }


    /**
     * Returns a FlagList representation of all flags set for this MessageItem.
     *
     * @return FlagList
     */
    public function getFlagList() :FlagList {
        $flagList   = new FlagList();

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
    protected function checkType($property, $value) {
        switch ($property) {
            case "subject":
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
    public function toJson() :array{

        $mk = $this->getMessageKey();

        return array_merge($mk->toJson(), [
            'from'           => $this->getFrom() ? $this->getFrom()->toJson() : [],
            'to'             => $this->getTo() ? $this->getTo()->toJson() : [],
            'subject'        => $this->getSubject(),
            'date'           => ($this->getDate() ? $this->getDate() : new \DateTime("1970-01-01"))->format("Y-m-d H:i:s"),
            'seen'           => $this->getSeen(),
            'answered'       => $this->getAnswered(),
            'draft'          => $this->getDraft(),
            'flagged'        => $this->getFlagged(),
            'recent'         => $this->getRecent(),
        ]);
    }



}