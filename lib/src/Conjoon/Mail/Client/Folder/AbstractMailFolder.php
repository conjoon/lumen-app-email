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

namespace Conjoon\Mail\Client\Folder;

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use InvalidArgumentException;

/**
 * AbstractMailFolder models base information for a MailFolder.
 *
 * @package Conjoon\Mail\Client\Folder
 */
abstract class AbstractMailFolder
{

    /**
     * @var FolderKey
     */
    protected FolderKey $folderKey;


    /**
     * @var string
     */
    protected string $name;


    /**
     * @var int
     */
    protected int $unreadCount;


    /**
     * ListMailFolder constructor.
     *
     * @param FolderKey $folderKey
     * @param array|null $data
     *
     * @throws InvalidArgumentException if the name or unreadCount
     * in $data is missing
     */
    public function __construct(FolderKey $folderKey, array $data)
    {

        $this->folderKey = $folderKey;

        $missing = "";
        if (!isset($data["unreadCount"])) {
            $missing = "unreadCount";
        } elseif (!isset($data["name"])) {
            $missing = "name";
        }

        if ($missing) {
            throw new InvalidArgumentException(
                "value for property \"" . $missing . "\" missing"
            );
        }

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $method = "set" . ucfirst($key);
                $this->{$method}($value);
            }
        }
    }


    /**
     * Returns the FolderKey of this ListMailFolder.
     *
     * @return FolderKey
     */
    public function getFolderKey(): FolderKey
    {
        return $this->folderKey;
    }


    /**
     * Sets the name for this ListMailFolder.
     *
     * @param string $name
     */
    protected function setName(string $name)
    {
        $this->name = $name;
    }


    /**
     * Returns the name for this ListMailFolder.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Sets the unread count for this ListMailFolder.
     *
     * @param int $unreadCount
     */
    protected function setUnreadCount(int $unreadCount)
    {
        $this->unreadCount = $unreadCount;
    }


    /**
     * Returns the unread count for this ListMailFolder.
     *
     * @return int
     */
    public function getUnreadCount(): int
    {
        return $this->unreadCount;
    }
}
