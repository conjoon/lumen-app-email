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

        $tree = $this->flatToTree($flat, $account->getId(), $mailboxes);

        $folders = [];
        $inbox = null;

        for ($i = count($tree) - 1; $i >= 0; $i--) {
            if ($tree[$i]["cn_folderType"] === "FOLDER") {
                $folders[] = array_splice($tree, $i, 1)[0];
            } else if ($tree[$i]["id"] === "INBOX") {
                $inbox = [array_splice($tree, $i, 1)[0]];
            }
        }


        return array_merge($inbox, $tree, $folders);

    }


    /**
     * Helper function for transforming a flat \Horde_Imap_Client generated
     * list of mailboxes to a tree structure.
     *
     * @param array $flatMailboxes
     *
     * @return array
     */
    protected function flatToTree(array $flatMailboxes, string $mailAccountId, array $mailboxes) :array {

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
            $tp = $path;
            array_pop($tp);
            $parent = implode($delim, $tp);

            $parentSkip = $this->shouldSkipMailbox($parent, $mailboxes);
            $isRoot = count($path) === 1;
            $shouldBeRoot = $this->shouldBeRootFolder($id, $delim);
            $cnFolderType = "FOLDER";

            if ($isRoot || $shouldBeRoot || $parentSkip) {

                if ($isRoot || $shouldBeRoot) {
                    $cnFolderType = $this->mapFullFolderIdToType($id, $delim);
                }

                $processed[$id] = true;

                $node = &$data;
                $names = explode($delim, $mailbox['name']);
                $actualName = $parentSkip ? $mailbox['name'] : array_pop($names);
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
                "cn_folderType" => $cnFolderType,
                "data" => []
            ];
        }

        function seedFolderType($folderType, &$childs) {
            foreach ($childs as &$node) {
                $node["cn_folderType"] = $folderType;
                if (count($node["data"])) {
                    seedFolderType($folderType, $node["data"]);
                }
            }

        };

        $systemFolders = [];

        foreach ($data as &$node) {

            $folderType = $node["cn_folderType"];

            if ($folderType !== "FOLDER" && !in_array($folderType, $systemFolders)) {
                $systemFolders[] = $folderType;
            } else {
                // reset if we have the same type already for a root node
                $node["cn_folderType"] = "FOLDER";
                continue;
            }

            if ($folderType !== "FOLDER" && count($node["data"])) {
                seedFolderType($folderType, $node["data"]);
            }

        }

        return $data;
    }



    /**
     * Helper function to map a folder-id (global name) to a type.
     * Names will be split given the specified delimiter and a lookup on the
     * first two parts (if available) will be made to guess the type this id should represent.
     * For example, "INBOX" will always be an INBOX-type, whereas INBOX.Drafts
     * will be of type "DRAFT". Global names consisting of more than 2 parts will
     * automatically have the type "FOLDER".
     *
     * @example
     *   $this->mapFullFolderNameToType("INBOX", "."); // returns "INBOX"
     *   $this->mapFullFolderNameToType("INBOX.Somefolder.Deep.Drafts", "."); // returns "FOLDER"
     *   $this->mapFullFolderNameToType("INBOX.Drafts", "."); // returns "DRAFT"
     *   $this->mapFullFolderNameToType("INBOX.Trash.Deep.Deeper.Folder", "."); // returns "FOLDER"
     *
     * @param string $name
     *
     * @param string $delimiter
     *
     * @return string INBOX|TRASH|SENT|DRAFT|JUNK
     */
    public function mapFullFolderIdToType(string $id, string $delimiter) :string {

        $name = $id;

        $path = explode($delimiter, $name);

        $type = "FOLDER";

        if (count($path) > 2) {
            return $type;
        }

        switch (strtoupper($name)) {

            case "INBOX":
                $type = "INBOX";
                break;

            case "TRASH":
            case "INBOX".$delimiter."TRASH":
            case "INBOX".$delimiter."PAPIERKORB":
            case "INBOX".$delimiter."DELETED ITEMS":
                $type = "TRASH";
                break;

            case "DRAFTS":
            case "INBOX".$delimiter."DRAFTS":
                $type = "DRAFT";
                break;

            case "SENT":
            case "INBOX".$delimiter."SENT":
            case "INBOX".$delimiter."SENT MESSAGES":
            case "INBOX".$delimiter."SENT ITEMS":
                $type = "SENT";
            break;

            case "JUNK":
            case "INBOX".$delimiter."JUNK":
            case "INBOX".$delimiter."SPAM":
                $type = "JUNK";
                break;

        }


        return $type;
    }


    /**
     * Returns true if the mailbox with this global name should be added as a root
     * node to the mailbox hierarchy, although it belongs to a parent folder already.
     * This is sometimes needed if system relevant mailboxes, such as SENT or DRAFTS
     * are direct child folders of the INBOX mailbox.
     * Returns always true for the "INBOX" folder.
     *
     * @return bool
     */
    public function shouldBeRootFolder($globalName, $delimiter) :bool {

        $paths = explode($delimiter, $globalName);
        $count = count($paths);

        return $count === 1 || $globalName === "INBOX" ||
                ($count === 2 && $paths[0] === "INBOX");

    }


    /**
     * Returns true if the mailbox with the specified $id belonging to the
     * $mailboxes should be skipped and not placed in the folder hierarchy.
     * This is true if either nonexistent or noselect attributes are set.
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

        return false;

    }



}