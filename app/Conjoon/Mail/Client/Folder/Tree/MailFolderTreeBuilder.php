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

namespace Conjoon\Mail\Client\Folder\Tree;

use Conjoon\Mail\Client\Folder\MailFolderList,
    Conjoon\Mail\Client\Folder\MailFolderChildList;

/**
 * Interface MailFolderTreeBuilder.
 *
 *
 * @package Conjoon\Mail\Client\Folder\Tree
 */
interface MailFolderTreeBuilder {


    /**
     * Iterates through the list of ListMailFolders in $mailFolderList and returns
     * a tree structure representing this list. The tree structure does not necessarily start
     * with a root folder and allows for returning a MailFolderChildList which can either hold
     * one entry (representing the root) or multiple entries.
     *
     * @param MailFolderList $mailFolderList
     * @param array $root One or more global ids of the folders that should be used as the root folders.
     * For most IMAP installations, this will be ["INBOX"].
     *
     * @return MailFolderChildList
     */
    public function listToTree(MailFolderList $mailFolderList, array $root) :MailFolderChildList;



}