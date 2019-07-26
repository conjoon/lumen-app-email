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

namespace Conjoon\Mail\Client\Data;


/**
 * Class MailAccount models account information for a mail server.
 *
 * @example
 *
 *    $item = new MessageItem([
 *        'id'            => "3232",
 *        'mailAccountId' => "conjoon_developer",
 *        'mailFolderId'  => "INBOX"
 *    ]);
 *
 *    $item->getId(); // "3232"
 *    $item->setSubject("Foo");
 *    $item->getSubject(); // "Foo"
 *
 * @package Conjoon\Mail\Client\Data
 */
class MessageItem  {


    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $mailAccountId;

    /**
     * @var string
     */
    protected $mailFolderId;

    /**
     * @var array
     */
    protected $from;

    /**
     * @var array
     */
    protected $to;

    /**
     * @var int
     */
    protected $size;

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
     * @var bool
     */
    protected $hasAttachments;


    /**
     * MailAccount constructor.
     *
     * @param array $config
     *
     * @throws \TypeError if any of the submitted values for the properties do not match
     * their expected type
     * @throws \DataException
     */
    public function __construct(array $config) {

        if (isset($config["id"]) && isset($config["mailFolderId"]) && isset($config["mailAccountId"])) {

            if (!is_string($config["mailAccountId"]) || !is_string($config["mailFolderId"]) || !is_string($config["id"])) {
                $key = !is_string($config["id"])
                    ? "id"
                    : (!is_string($config["mailFolderId"])
                        ? "mailFolderId"
                        : "mailAccountId");

                throw new \TypeError("Wrong type for \"$key\" submitted, \"string\" expected.");
            }
            $this->id = $config["id"];
            $this->mailFolderId = $config["mailFolderId"];
            $this->mailAccountId = $config["mailAccountId"];

            unset($config["id"]);
            unset($config["mailFolderId"]);
            unset($config["mailAccountId"]);
        } else {
            $key = !isset($config["id"])
                    ? "id"
                    : (!isset($config["mailFolderId"])
                      ? "mailFolderId"
                      : "mailAccountId");

            throw new DataException("Missing key \"$key\" for MessageItem");
        }


        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {

                $method = "set" . ucfirst($key);
                $this->{$method}($value);
            }

        }
    }


    /**
     * Sets the Date of this message.
     * Makes sure no reference is stored to the date-object.
     *
     * @param \DateTime $date
     * @return $this
     */
    public function setDate(\DateTime $date) {
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
                    !in_array($property, ['mailFolderId', 'mailAccountId', 'id'])) {

                    $value = $arguments[0];
                    $typeFail = "";

                    switch ($property) {
                        case "subject":
                            if (!is_string($value)) {
                                $typeFail = "string";
                            }
                            break;

                        case "size":
                            if (!is_int($value)) {
                                $typeFail = "int";
                            }
                            break;

                        case "seen":
                        case "recent":
                        case "draft":
                        case "flagged":
                        case "answered":
                        case "hasAttachments":
                            if (!is_bool($value)) {
                                $typeFail = "bool";
                            }
                            break;


                        case "from":
                        case "to":
                            if (!is_array($value)) {
                                $typeFail = "array";
                            }
                            break;

                    }

                    if ($typeFail !== "") {
                        throw new \TypeError("Wrong type for \"$property\" submitted");
                    }

                    $this->{$property} = $value;
                    return $this;
                }
            }

            
        }

        throw new \BadMethodCallException("no method \"".$method."\" found.");
    }


    /**
     * Returns an array representation of this object.
     * 
     * Return array
     */
    public function toArray() :array {
        return [
            'id'             => $this->getId(),
            'mailAccountId'  => $this->getMailAccountId(),
            'mailFolderId'   => $this->getMailFolderId(),
            'from'           => $this->getFrom(),
            'to'             => $this->getTo(),
            'size'           => $this->getSize(),
            'subject'        => $this->getSubject(),
            'date'           => $this->getDate(),
            'seen'           => $this->getSeen(),
            'answered'       => $this->getAnswered(),
            'draft'          => $this->getDraft(),
            'flagged'        => $this->getFlagged(),
            'recent'         => $this->getRecent(),
            'hasAttachments' => $this->getHasAttachments()
        ];
    }

}