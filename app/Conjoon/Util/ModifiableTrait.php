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

namespace Conjoon\Util;

/**
 * Trait ModifiableTrait
 *
 * @package Conjoon\Util
 */
trait ModifiableTrait  {

    /**
     * @var array
     */
    protected $modifiedFields = [];

    /**
     * @var bool
     */
    protected $suspendModifiable = false;


    /**
     * Suspends marking fields as modified.
     *
     * @return self
     */
    protected function suspendModifiable() {
        $this->suspendModifiable = true;

        return $this;
    }


    /**
     * Resumes marking fields as modified.
     *
     * @return self
     */
    protected function resumeModifiable() {
        $this->suspendModifiable = false;

        return $this;
    }


    /**
     * @param $fieldName
     */
    protected function addModified($fieldName) {
        if ($this->suspendModifiable === true) {
            return;
        }
        $this->modifiedFields[$fieldName] = true;
    }


    /**
     * Returns an array of all modified fields .
     *
     * @return this
     */
    public function getModifiedFields() :array {
        return array_keys($this->modifiedFields);
    }


}