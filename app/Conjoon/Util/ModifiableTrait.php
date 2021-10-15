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

namespace Conjoon\Util;

/**
 * Trait ModifiableTrait.
 * Allows for keeping track of modified fields in implementing classes.
 *
 * @example
 *
 *   class User
 *   {
 *      use ModifiableTrait;
 *
 *      // Use suspend-/ resumeModifiable when the trait should not be updated
 *      // with updated fields
 *      // modified fields should only be tracked once the instance was fully
 *      // configured
 *      public function __construct($username)
 *     {
 *          $this->suspendModifiable();
 *          $this->setUsername($username);
 *          $this->resumeModifiable();
 *      }
 *
 *      public function setUsername(string $username): User
 *      {
 *          $this->addModified("userName");
 *          $this->userName = $username;
 *
 *          return $this;
 *      }
 *   }
 *
 *   $user = new User();
 *   $user->setUsername("admin"); // ;)
 *   $user->getModifiedFields();  // ["userName"]
 *
 * @package Conjoon\Util
 */
trait ModifiableTrait
{

    /**
     * @var array
     */
    protected array $modifiedFields = [];

    /**
     * @var bool
     */
    protected bool $suspendModifiable = false;


    /**
     * Suspends marking fields as modified.
     *
     * @return $this the implementing class instance
     */
    protected function suspendModifiable()
    {
        $this->suspendModifiable = true;

        return $this;
    }


    /**
     * Resumes marking fields as modified.
     *
     * @return $this the implementing class instance
     */
    protected function resumeModifiable()
    {
        $this->suspendModifiable = false;

        return $this;
    }


    /**
     * Add a field to the list of modified fields.
     *
     * @param string $fieldName
     *
     * @return $this the implementing class instance
     */
    protected function addModified(string $fieldName)
    {
        if ($this->suspendModifiable === true) {
            return $this;
        }
        $this->modifiedFields[$fieldName] = true;

        return $this;
    }


    /**
     * Returns an array of all modified fields .
     *
     * @return array
     */
    public function getModifiedFields(): array
    {
        return array_keys($this->modifiedFields);
    }
}
