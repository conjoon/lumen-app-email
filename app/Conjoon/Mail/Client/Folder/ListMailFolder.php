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

namespace Conjoon\Mail\Client\Folder;

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;


/**
 * Class ListMailFolder models MailFolder-informations for a specified MailAccount,
 * including delimiter property.
 *
 * @example
 *
 *    $item = new ListMailFolder(
 *              new FolderKey("dev", "INBOX.SomeFolder"),
 *              [
 *                 "name"        => "INBOX.Some Folder",
 *                 "delimiter"   => "."
 *                 "unreadCount" => 4
 *              ]
 *            );
 *
 *    $listMailFolder->getDelimiter(); // "."
 *    $item->getUnreadCount(4);
 *
 *
 *
 * @package Conjoon\Mail\Client\Folder
 */
class ListMailFolder extends AbstractMailFolder {


    /**
     * @var string
     */
    protected $delimiter;


    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException if delimiter in $data is missing
     */
    public function __construct(FolderKey $folderKey, array $data) {

        if (!isset($data["delimiter"])) {
            throw new \InvalidArgumentException(
                "value for property \"delimiter\" missing"
            );
        }

        parent::__construct($folderKey, $data);
    }


    /**
     * Sets the delimiter for this ListMailFolder.
     *
     * @param $delimiter
     */
    protected function setDelimiter(string $delimiter) {
        $this->delimiter = $delimiter;
    }


    /**
     * Returns the delimiter for this ListMailFolder.
     * @return string
     */
    public function getDelimiter() :string {
        return $this->delimiter;
    }


}