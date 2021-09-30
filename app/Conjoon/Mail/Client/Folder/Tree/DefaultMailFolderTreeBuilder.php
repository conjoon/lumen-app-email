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

namespace Conjoon\Mail\Client\Folder\Tree;

use Conjoon\Mail\Client\Folder\MailFolderList,
    Conjoon\Mail\Client\Folder\MailFolderChildList,
    Conjoon\Mail\Client\Folder\MailFolder,
    Conjoon\Mail\Client\Folder\ListMailFolder,
    Conjoon\Mail\Client\Folder\FolderIdToTypeMapper,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey;

/**
 * Class DefaultMailFolderTreeBuilder.
 * Default implementation for a MailFolderTreeBuilder.
 *
 *
 * @package Conjoon\Mail\Client\Folder\Tree
 */
class DefaultMailFolderTreeBuilder implements MailFolderTreeBuilder {


    /**
     * @var FolderIdToTypeMapper
     */
    protected $folderIdToTypeMapper;


    /**
     * DefaultMailFolderTreeBuilder constructor.
     * @param FolderIdToTypeMapper $folderIdToTypeMapper
     */
    public function __construct(FolderIdToTypeMapper $folderIdToTypeMapper) {
        $this->folderIdToTypeMapper = $folderIdToTypeMapper;
    }


    /**
     * @return FolderIdToTypeMapper
     */
    public function getFolderIdToTypeMapper() : FolderIdToTypeMapper{
        return $this->folderIdToTypeMapper;
    }


// +-------------------------------
// | MailFolderTreeBuilder
// +-------------------------------

    /**
     * @inheritdoc
     */
    public function listToTree(MailFolderList $mailFolderList, array $root) :MailFolderChildList {

        $mailFolder = null;

        $folders = [];

        $systemFolderTypes = [];

        foreach ($mailFolderList as $mailbox) {

            if ($this->shouldSkipMailFolder($mailbox, $root)) {
                continue;
            };

            $parts = explode($mailbox->getDelimiter(), $mailbox->getFolderKey()->getId());
            array_pop($parts);
            $nameParts = explode($mailbox->getDelimiter(), $mailbox->getName());
            $name = array_pop($nameParts);


            $folderType = $this->getFolderIdToTypeMapper()->getFolderType($mailbox);

            if (in_array($folderType, $systemFolderTypes)) {
                $folderType = MailFolder::TYPE_FOLDER;
            }

            $mailFolder = new MailFolder(
                $mailbox->getFolderKey(),
                ["name" => $name,
                 "unreadCount" => $mailbox->getUnreadCount(),
                 "folderType" =>  $folderType]
            );

            if ($folderType !== MailFolder::TYPE_FOLDER) {
                $systemFolderTypes[] = $folderType;
            }

            $parentKey = implode($mailbox->getDelimiter(), $parts);
            if (!isset($folders[$parentKey])) {
                $folders[$parentKey] = [];
            }
            $folders[$parentKey][] = $mailFolder;
        }

        $mailFolderChildList = new MailFolderChildList;

        foreach ($folders as $parentKey => $mailFolders) {

            usort($mailFolders, function($a, $b) {
                if ($a->getFolderType() == $b->getFolderType()) {
                    return 0;
                }
                return ($a->getFolderType() === MailFolder::TYPE_FOLDER) ? 1 : -1;
            });


            if ($parentKey === "") {
                $mailFolder            = $mailFolders[0];
                $mailFolderChildList[] = $mailFolder;
                continue;
            }

            $tmp = $this->getMailFolderWithId($parentKey, $folders);
            foreach ($mailFolders as $item) {
                foreach ($root as $rootId) {
                    if ($parentKey === $rootId) {
                        $mailFolderChildList[] = $item;
                        continue 2;
                    }
                }
                if (!$tmp) {
                    $mailFolderChildList[] = $item;
                } else {
                    $tmp->addMailFolder($item);
                }
            }

        }

        return $mailFolderChildList;
    }


// +-------------------------------
// | Helper
// +-------------------------------

    /**
     * Looks up the folder with the specified id in the list of MailFolders.
     *
     * @param $id
     * @param $folders
     * @return MailFolder
     */
    private function getMailFolderWithId(string $id, array $folders) :?MailFolder{

        foreach ($folders as $key => $folderList) {

            foreach ($folderList as $item) {
                if ($item->getFolderKey()->getId() === $id) {
                    return $item;
                }
            }
        }

        return null;
    }


    /**
     * Returns true if the specified MailFolder should be ignored,
     * which is true if either the \noselect or \nonexistent attribute
     * is set for this ListMailFolder, or if the id of the Mailbox does not indicate
     * a child relationship with the specified $root id.
     *
     * @param ListMailFolder $listMailFolder
     *
     * @return boolean
     */
    protected function shouldSkipMailFolder(ListMailFolder $listMailFolder, array $root) :bool {

        $id = $listMailFolder->getFolderKey()->getId();

        $idParts = explode($listMailFolder->getDelimiter(), $id);
        $skip    = 0;
        foreach ($root as $globalIds) {
            $rootParts = explode($listMailFolder->getDelimiter(), $globalIds);
            foreach ($rootParts as $key => $rootId) {
                if (!isset($idParts[$key]) || $rootId !== $idParts[$key]) {
                    $skip++;
                }
            }
        }
        if ($skip === count($root)) {
            return true;
        }

        return in_array("\\noselect",    $listMailFolder->getAttributes()) ||
               in_array("\\nonexistent", $listMailFolder->getAttributes());
    }


}
