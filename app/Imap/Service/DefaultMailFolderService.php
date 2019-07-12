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

namespace App\Imap\Service;

use App\Imap\ImapAccount;

/**
 * Class DefaultMailFolderService.
 * Default implementation of a MailFolderService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultMailFolderService implements MailFolderService {

    use ImapTrait;

    /**
     * @inheritdoc
     */
    public function getMailFoldersFor(ImapAccount $account) :array {

        try {
            $client = $this->connect($account);

            $mailboxes = $client->listMailboxes(
                "*",
                \Horde_Imap_Client::MBOX_ALL,
                ["attributes" => true]
            );

        } catch (\Exception $e) {

            throw new MailFolderServiceException($e->getMessage(), 0, $e);
        }


        $flat = [];


        foreach ($mailboxes as $id => $mailbox) {

            if ($this->shouldSkipMailbox($id, $mailboxes)) {
                continue;
            }

            try {
                $status = $client->status(
                    $mailbox['mailbox'],
                    \Horde_Imap_Client::STATUS_UNSEEN
                );
            } catch (\Exception $e) {
                throw new MailFolderServiceException($e->getMessage(), 0, $e);
            }


            $flat[] = [
                "delimiter"     => $mailbox['delimiter'],
                "id"            => $id,
                "name"          => $mailbox['mailbox']->utf8,
                "status"        => $status
            ];
        }

        return $this->flatToTree($flat, $account->getId());
    }



    /**
     * Helper function for transforming a flat \Horde_Imap_Client generated
     * list of mailboxes to a tree structure.
     *
     * @param array $flatMailboxes
     *
     * @return array
     */
    protected function flatToTree(array $flatMailboxes, string $mailAccountId) :array {

        $data = [];

        function &lookup($id, &$data) {

             foreach ($data as &$node) {

                if (!isset($node['data'])) {
                    $node['data'] = [];
                }
                if ($node['id'] === $id) {
                    return $node['data'];
                }
                $node = &lookup($id, $node['data']);
             }

             return $node;
        };

        $processed = [];

        foreach ($flatMailboxes as $mailbox) {

            $id = $mailbox['id'];

            $delim = $mailbox['delimiter'];

            $path = explode($delim, $id);

            if (count($path) === 1 || $this->shouldBeRootFolder($id, $delim)) {
                $processed[$id] = true;

                $node = &$data;
                $names = explode($delim, $mailbox['name']);
                $actualName = array_pop($names);
            } else {

                $ex = explode($delim, $mailbox['name']);
                $actualName = array_pop($ex);
                array_pop($path);
                $node = &lookup(join($delim, $path), $data);
            }

            $node[] = [
                "id"   => $id,
                "mailAccountId" => $mailAccountId,
                "name" => $actualName,
                "unreadCount" => $mailbox["status"]["unseen"],
                "cn_folderType" => $this->mapFullFolderNameToType($id, $delim),
                "data" => []
            ];

        }

        return $data;
    }


    /**
     * Helper function to map a folder-name to a type.
     * Names will be split given the specified delimiter and a lookup on the
     * first two parts will be made to guess the type this name belong to.
     * For example, "INBOX" will always be an INBOX-type, whereas INBOX.Drafts
     * will be of type "DRAFT".
     *
     * @example
     *   $this->mapFullFolderNameToType("INBOX", "."); // returns "INBOX"
     *   $this->mapFullFolderNameToType("INBOX.Somefolder.Deep.Drafts", "."); // returns "INBOX"
     *   $this->mapFullFolderNameToType("INBOX.Drafts", "."); // returns "DRAFT"
     *   $this->mapFullFolderNameToType("INBOX.Trash.Deep.Deeper.Folder", "."); // returns "TRASH"
     *
     * @param string $name
     *
     * @param string $delimiter
     *
     * @return string INBOX|TRASH|SENT|DRAFT|JUNK
     */
    public function mapFullFolderNameToType(string $name, string $delimiter) :string {

        $path = explode($delimiter, $name);

        if (count($path) > 2) {
            $name = $path[0] . $delimiter . $path[1];
        }

        switch (strtoupper($name)) {

            case "INBOX":
                return "INBOX";

            case "TRASH":
            case "INBOX".$delimiter."TRASH":
            case "INBOX".$delimiter."PAPIERKORB":
            case "INBOX".$delimiter."DELETED ITEMS":
                return "TRASH";

            case "DRAFTS":
            case "INBOX".$delimiter."DRAFTS":
                return "DRAFT";

            case "SENT":
            case "INBOX".$delimiter."SENT":
            case "INBOX".$delimiter."SENT MESSAGES":
            case "INBOX".$delimiter."SENT ITEMS":
                return "SENT";

            case "JUNK":
            case "INBOX".$delimiter."JUNK":
            case "INBOX".$delimiter."SPAM":
                return "JUNK";

        }

        if (count($path) >= 2) {
            return $this->mapFullFolderNameToType($path[0], $delimiter);
        }

        return "INBOX";
    }


    /**
     * Returns true if the mailbox with this global name should be added as a root
     * node to the mailbox hierarchy, although it belongs to a parent folder already.
     * This is sometimes needed if system relevant mailboxes, such as SENT or DRAFTS
     * are direct child folders of the INBO mailbox.
     *
     * @return bool
     */
    public function shouldBeRootFolder($globalName, $delimiter) :bool {

        return array_search(strtoupper($globalName), [
            "INBOX",
            "INBOX".$delimiter."TRASH",
            "INBOX".$delimiter."JUNK",
            "INBOX".$delimiter."SENT",
            "INBOX".$delimiter."DRAFTS"
        ]) !== false;

    }


    /**
     * Returns true if the mailbox with the specified $id belonging to the
     * $mailboxes should be skipped and not placed in the folder hierarchy.
     * This is true if either nonexistent or noselect attributes are set, or
     * if the mailbox has a parent mailbox with these attributes.
     *
     * @param string $id
     * @param string $mailboxes
     *
     * @return bool
     */
    public function shouldSkipMailbox(string $id, array $mailboxes) :bool {

        $mailbox = isset($mailboxes[$id]) ? $mailboxes[$id] : null;

        if (!$mailbox) {
            return true;
        }

        if (array_search('\nonexistent', $mailbox['attributes']) !== false ||
            array_search('\noselect', $mailbox['attributes']) !== false) {
            return true;
        }

        $delim = $mailbox['delimiter'];

        $path = explode($delim, $id);
        array_pop($path);

        if (count($path) === 0) {
            return false;
        }

        return $this->shouldSkipMailbox(implode($delim, $path), $mailboxes);
    }



}