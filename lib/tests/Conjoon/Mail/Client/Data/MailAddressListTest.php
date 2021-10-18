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
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Util\AbstractList;
use Conjoon\Util\Copyable;
use Conjoon\Util\Jsonable;
use Conjoon\Util\JsonDecodable;
use Conjoon\Util\JsonDecodeException;
use Tests\TestCase;

/**
 * Class MailAddressListTest
 * @package Tests\Conjoon\Mail\Client\Data
 */
class MailAddressListTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass()
    {

        $mailAddressList = new MailAddressList();
        $this->assertInstanceOf(AbstractList::class, $mailAddressList);
        $this->assertInstanceOf(Jsonable::class, $mailAddressList);
        $this->assertInstanceOf(JsonDecodable::class, $mailAddressList);
        $this->assertInstanceOf(Copyable::class, $mailAddressList);


        $this->assertSame(MailAddress::class, $mailAddressList->getEntityType());
    }



    /**
     * Tests toArray
     */
    public function testToJson()
    {

        $mailAddressList = new MailAddressList();
        $mailAddressList[] = new MailAddress("name1@address.testcomdomaindev", "name1");
        $mailAddressList[] = new MailAddress("name2@address.testcomdomaindev", "name2");

        $this->assertEquals([
            $mailAddressList[0]->toJson(),
            $mailAddressList[1]->toJson()
        ], $mailAddressList->toJson());
    }


    /**
     * fromJsonString
     */
    public function testFromString()
    {

        $mailAddressList = new MailAddressList();
        $mailAddressList[] = new MailAddress("name1@address.testcomdomaindev", "name1");
        $mailAddressList[] = new MailAddress("name2@address.testcomdomaindev", "name2");

        $jsonString = json_encode($mailAddressList->toJson());
        $this->assertEquals($mailAddressList, MailAddressList::fromString($jsonString));

        $jsonString = "[{\"name\" : \"foo\"}]";
        $this->assertEmpty(MailAddressList::fromString($jsonString));
    }


    /**
     * expect JsonDecodeException
     */
    public function testFromStringWithException()
    {
        $this->expectException(JsonDecodeException::class);
        $jsonString = json_encode([]);
        MailAddressList::fromString($jsonString);
    }


    /**
     * expect JsonDecodeException
     */
    public function testFromStringWithExceptionInvalid()
    {
        $this->expectException(JsonDecodeException::class);
        $jsonString = "{json:1)";
        MailAddressList::fromString($jsonString);
    }


    /**
     * toString
     */
    public function testToString()
    {

        $mailAddressList = new MailAddressList();
        $mailAddressList[] = new MailAddress("name1@address.testcomdomaindev", "name1");
        $mailAddressList[] = new MailAddress("name2@address.testcomdomaindev", "name2");

        $this->assertSame(
            $mailAddressList[0]->toString() . ", " . $mailAddressList[1]->toString(),
            $mailAddressList->toString()
        );
    }

    /**
     *
     */
    public function testCopy()
    {

        $mailAddressList = new MailAddressList();
        $mailAddressList[] = new MailAddress("name1@address.testcomdomaindev", "name1");
        $mailAddressList[] = new MailAddress("name2@address.testcomdomaindev", "name2");

        $copy = $mailAddressList->copy();
        $this->assertEquals($copy, $mailAddressList);
        $this->assertNotSame($copy, $mailAddressList);
    }
}
