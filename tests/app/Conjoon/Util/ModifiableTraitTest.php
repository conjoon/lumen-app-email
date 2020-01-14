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

use Conjoon\Util\ModifiableTrait;


class ModifiableTraitTest extends TestCase {


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests instance
     */
    public function testInstance() {

        $trait = $this->getMockedTrait();

        $this->assertSame([], $trait->getModifiedFields());

        $trait->suspendModifiableProxy();
        $trait->setField();
        $this->assertSame([], $trait->getModifiedFields());
        $trait->resumeModifiableProxy();

        $trait->setField();

        $this->assertSame(["field"], $trait->getModifiedFields());
    }


// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @return  anonymous anonymous class implementing trait
     */
    protected function getMockedTrait() {

        return new class() {

            use ModifiableTrait;

            public function suspendModifiableProxy() {
                $this->suspendModifiable();
            }

            public function resumeModifiableProxy() {
                $this->resumeModifiable();
            }

            public function setField() {
                $this->addModified("field");
            }

        };

    }



}