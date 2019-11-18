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

use Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Util\Jsonable,
    Conjoon\Util\JsonDecodable,
    Conjoon\Util\Stringable;


class MailAddressTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------
    /**
     * Test class
     */
    public function testClass() {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $this->assertInstanceOf(Jsonable::class, $mailAddress);
        $this->assertInstanceOf(Stringable::class, $mailAddress);
        $this->assertInstanceOf(JsonDecodable::class, $mailAddress);
        $this->assertSame($address, $mailAddress->getAddress());
        $this->assertSame($name, $mailAddress->getName());

        $this->assertEquals(["name" => $name, "address" => $address], $mailAddress->toJson());
    }


    /**
     * toString()
     */
    public function testToString() {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $this->assertSame("Peter Parker <peter.parker@newyork.com>", $mailAddress->toString());

    }

    /**
     * toString()
     */
    public function testFromJsonString() {

        $name = "Peter Parker";
        $address = "peter.parker@newyork.com";
        $mailAddress = new MailAddress($address, $name);

        $jsonString = json_encode($mailAddress->toJson());
        $this->assertEquals($mailAddress, MailAddress::fromJsonString($jsonString));

        $jsonString = "foo/:bar{";
        $this->assertNull(MailAddress::fromJsonString($jsonString));

        $jsonString = json_encode(["name" => "foo"]);
        $this->assertNull(MailAddress::fromJsonString($jsonString));

        $jsonString = json_encode(["address" => "foo"]);
        $address = MailAddress::fromJsonString($jsonString);

        $this->assertSame("foo", $address->getName());
        $this->assertSame("foo", $address->getAddress());


    }

}