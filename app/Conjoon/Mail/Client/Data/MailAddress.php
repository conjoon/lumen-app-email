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

use Conjoon\Util\Jsonable;

/**
 * Class MailAddress models a Mail Address, containing a "name" and an "address".
 *
 * @example
 *
 *    $address = new MailAddress("PeterParker@newyork.com", "Peter Parker");
 *
 *    $address->getName(); // "Peter Parker"
 *    $address->getAddress(); // "PeterParker@newyork.com"
 *
 * @package Conjoon\Mail\Client\Data
 */
class MailAddress  implements Jsonable {


    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $address;


    /**
     * MailAddress constructor.
     *
     * @param string $address
     * @param string $name
     */
    public function __construct(string $address, string $name) {
        $this->address = $address;
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getName() :string {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getAddress() :string {
        return $this->address;
    }


// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * Returns an array representing this MailAddress.
     *
     * Each entry in the returning array must consist of the following key/value-pairs:
     *
     * - address (string)
     * - name (string)
     *
     * @return array
     */
    public function toJson() :array{

        return [
            'address' => $this->getAddress(),
            'name'    => $this->getName()
        ];
    }


}