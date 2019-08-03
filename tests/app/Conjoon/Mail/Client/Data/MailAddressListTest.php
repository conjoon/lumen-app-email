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

use Conjoon\Util\AbstractList,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList;


class MailAddressListTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass() {

        $mailAddressList = new MailAddressList();
        $this->assertInstanceOf(AbstractList::class, $mailAddressList);


        $this->assertSame(MailAddress::class, $mailAddressList->getEntityType());
    }



    /**
     * Tests toArray
     */
    public function testToArray() {

        $mailAddressList = new MailAddressList;

        $mailAddressList[] = new MailAddress("name1@address.testcomdomaindev", "name1");
        $mailAddressList[] = new MailAddress("name2@address.testcomdomaindev", "name2");

        $this->assertEquals([
            ["name" => "name1", "address" => "name1@address.testcomdomaindev"],
            ["name" => "name2", "address" => "name2@address.testcomdomaindev"]
        ], $mailAddressList->toArray());
    }

}