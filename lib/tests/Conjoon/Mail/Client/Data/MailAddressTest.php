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

namespace Tests\Conjoon\Mail\Client\Data;

use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Util\Copyable;
use Conjoon\Util\Jsonable;
use Conjoon\Util\JsonDecodable;
use Conjoon\Util\JsonDecodeException;
use Conjoon\Util\Stringable;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class MailAddressTest
 * @package Tests\Conjoon\Mail\Client\Data
 */
class MailAddressTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Test class
     */
    public function testClass()
    {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $this->assertInstanceOf(Jsonable::class, $mailAddress);
        $this->assertInstanceOf(Stringable::class, $mailAddress);
        $this->assertInstanceOf(JsonDecodable::class, $mailAddress);
        $this->assertInstanceOf(Copyable::class, $mailAddress);
        $this->assertSame($address, $mailAddress->getAddress());
        $this->assertSame($name, $mailAddress->getName());

        $this->assertEquals(["name" => $name, "address" => $address], $mailAddress->toJson());
    }


    /**
     * Test InvalidArgumentException
     *
     */
    public function testInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        new MailAddress("", "name");
    }

    /**
     * Test InvalidArgumentException caused by decoding
     *
     */
    public function testJsonDecodeExceptionFromCaughtInConstructor()
    {
        $this->expectException(JsonDecodeException::class);
        $this->expectExceptionMessageMatches("/(cannot)/");
        $jsonString = json_encode(["address" => "", "name" => "name"]);
        MailAddress::fromString($jsonString);
    }


    /**
     * toString()
     */
    public function testToString()
    {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $this->assertSame("Peter Parker <peter.parker@newyork.com>", $mailAddress->toString());
    }

    /**
     * toString()
     */
    public function testFromString()
    {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $jsonString = json_encode($mailAddress->toJson());
        $this->assertEquals($mailAddress, MailAddress::fromString($jsonString));

        $jsonString = json_encode(["address" => "foo"]);
        $address = MailAddress::fromString($jsonString);

        $this->assertSame("foo", $address->getName());
        $this->assertSame("foo", $address->getAddress());
    }


    /**
     * expect JsonDecodeException invalid string
     */
    public function testFomStringWithExceptionInvalidString()
    {
        $this->expectException(JsonDecodeException::class);
        $jsonString = "foo/:bar{";
        MailAddress::fromString($jsonString);
    }


    /**
     * expect JsonDecodeException address missing
     */
    public function testFromStringWithExceptionAddressMissing()
    {
        $this->expectException(JsonDecodeException::class);
        $jsonString = json_encode(["name" => "foo"]);
        MailAddress::fromString($jsonString);
    }


    /**
     * copy()
     */
    public function testCopy()
    {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $address1 = $mailAddress->copy();

        $this->assertSame($address1->getAddress(), $mailAddress->getAddress());
        $this->assertSame($address1->getName(), $mailAddress->getName());
        $this->assertNotSame($address1, $mailAddress);
    }
}
