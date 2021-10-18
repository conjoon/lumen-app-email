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

use Conjoon\Util\AbstractList;
use Conjoon\Util\Copyable;
use Conjoon\Util\Jsonable;
use Conjoon\Util\JsonDecodable;
use Conjoon\Util\JsonDecodeException;
use Conjoon\Util\Stringable;

/**
 * Class MailAddressList organizes a list of MailAddresses.
 *
 * @example
 *
 *    $list = new MailAddressList();
 *
 *    $address = new MailAddress("PeterParker@newyork.com", "Peter Parker");
 *    $list[] = $address;
 *
 *    foreach ($list as $key => $address) {
 *        // iterating over the item
 *    }
 *
 * @package Conjoon\Mail\Client\Data
 */
class MailAddressList extends AbstractList implements JsonDecodable, Stringable, Copyable, Jsonable
{


// --------------------------------
//  Copyable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public function copy(): MailAddressList
    {

        $list = new MailAddressList();

        foreach ($this as $entry) {
            $list[] = $entry->copy();
        }

        $this->rewind();
        return $list;
    }



// --------------------------------
//  JsonDecodable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public static function fromArray(array $arr): Jsonable
    {

        $val = json_encode($arr);

        if (!$val) {
            throw new JsonDecodeException("could not decode the array");
        }

        return self::fromString($val);
    }


    /**
     * @inheritdoc
     */
    public static function fromString(string $value): Jsonable
    {

        $val = json_decode($value, true);

        if (!$val) {
            throw new JsonDecodeException("could not decode the string");
        }

        $list = new self();

        foreach ($val as $entry) {
            $address = null;
            try {
                $address = MailAddress::fromArray($entry);
            } catch (JsonDecodeException $e) {
                // intentionally left empty
            }

            if (!$address) {
                continue;
            }

            $list[] = $address;
        }

        return $list;
    }



// --------------------------------
//  Stringable interface
// --------------------------------
    /**
     * Returns a string representation of this email address list.
     *
     * @return string
     * @example
     *   $list = new MailAddressList();
     *   $list[] = new MailAddress("PeterParker@newyork.com", "Peter Parker");
     *   $list[] = new MailAddress("PeterGriffin@quahog.com", "Peter Griffin");
     *
     *   $list->toString(); // returns "Peter Parker <PeterParker@newyork.com>, Peter Griffin <PeterGriffin@quahog.com>"
     *
     */
    public function toString(): string
    {

        $data = [];
        foreach ($this->data as $address) {
            $data[] = $address->toString();
        }

        return implode(", ", $data);
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MailAddressList.
     *
     * @return array
     *
     * @see MailAddress::toJson()
     */
    public function toJson(): array
    {

        $d = [];

        foreach ($this->data as $address) {
            $d[] = $address->toJson();
        }


        return $d;
    }


// -------------------------
//  AbstractList
// -------------------------

    /**
     * @inheritdoc
     */
    public function getEntityType(): string
    {
        return MailAddress::class;
    }
}
