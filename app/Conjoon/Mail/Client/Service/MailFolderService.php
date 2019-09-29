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

namespace Conjoon\Mail\Client\Service;

use Conjoon\Mail\Client\Data\MailAccount,
    Conjoon\Mail\Client\Folder\MailFolderChildList,
    Conjoon\Mail\Client\MailClient;

interface MailFolderService {


    /**
     * Returns MailFolderChildList MailFolders for the specified
     * MailAccount.
     *
     * @param MailAccount $account The MailAccount for which the folder structure
     * should be returned.
     *
     * @return MailFolderChildList An MailFolderChildList of the Mailbox-structure
     * found on the server.
     * The order of the returned mailboxes are as follows:
     *  - The INBOX Mailbox comes always first, followed by
     *  - all system relevant mailboxes, such as JUNK, TRASH, SENT, DRAFT (Note: If those mailboxes
     *  are child-elements of the INBOX mailbox, they wil be moved to the same level as the INBOX
     * mailbox.), followed by
     *  - all remaining folders which will have the type "FOLDER" applied to them.
     * Child folders of \nonexistent and/or \noselect mailboxes will not have a parent-folder and
     * returned as root level if applicable.
     *
     *
     * @see \Horde_Imap_Client_Socket
     */
    public function getMailFolderChildList(MailAccount $account) :MailFolderChildList;


    /**
     * Returns the MailClient used by this MessageService.
     *
     * @return MailClient
     */
    public function getMailClient() :MailClient;


}