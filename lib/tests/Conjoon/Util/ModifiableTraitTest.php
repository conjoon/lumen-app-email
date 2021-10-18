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

namespace Tests\Conjoon\Util;

use Conjoon\Util\ModifiableTrait;
use Tests\TestCase;

/**
 * Class ModifiableTraitTest
 * @package Tests\Conjoon\Util
 */
class ModifiableTraitTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests instance
     */
    public function testInstance()
    {

        $trait = $this->getMockedTrait();

        $this->assertSame([], $trait->getModifiedFields());

        $this->assertSame($trait, $trait->suspendModifiableProxy());
        $this->assertSame($trait, $trait->setField("foo"));
        $this->assertSame([], $trait->getModifiedFields());
        $this->assertSame($trait, $trait->resumeModifiableProxy());

        $this->assertSame($trait, $trait->setField());

        $this->assertSame(["field"], $trait->getModifiedFields());
    }


// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @return __anonymous(class)
     */
    protected function getMockedTrait()
    {

        return new class () {
            use ModifiableTrait;

            public function suspendModifiableProxy()
            {
                return $this->suspendModifiable();
            }

            public function resumeModifiableProxy()
            {
                return $this->resumeModifiable();
            }

            public function setField($notUsed = null)
            {
                return $this->addModified("field");
            }
        };
    }
}
