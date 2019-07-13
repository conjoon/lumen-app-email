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

use App\Imap\ImapAccount,
    App\Imap\Service\MessageItemServiceException;

/**
 * Class DefaultMessageItemService.
 * Default implementation of a MessageItemService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultMessageItemService implements MessageItemService {

    use ImapTrait;

    /**
     * @inheritdoc
     */
    public function getMessageItemsFor(ImapAccount $account, string $mailFolderId, array $options) :array {


        try {

            $client = $this->connect($account);

            $response = $this->buildMessageItems($client, $account->getId(), $mailFolderId, $options);

            $status = $client->status(
                $mailFolderId,
                \Horde_Imap_Client::STATUS_UNSEEN
            );

            $response["meta"] = [
                "cn_unreadCount" => $status["unseen"],
                "mailFolderId"   => $mailFolderId,
                "mailAccountId"  => $account->getId()
            ];


        } catch (\Exception $e) {
            throw new MessageItemServiceException($e->getMessage(), 0, $e);
        }


        return $response;

    }


    /**
     * Helper function for building a list of message items found in the mailbox with the global name
     * $mailFolderId given the specified options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $accountId
     * @param string $mailFolderId
     *
     * @return array
     *
     * @see queryItems
     */
    protected function buildMessageItems(\Horde_Imap_Client_Socket $client, string $accountId, string $mailFolderId, array $options) :array {


        $items = $this->queryItems($client, $mailFolderId, $options);

        $total  = $items["total"];
        $list   = $items["sortedIds"];
        $result = $items["fetchResult"];

        $bodyQuery = new \Horde_Imap_Client_Fetch_Query();

        $messageItems = [];

        foreach ($list as $id) {

            $item = $result[$id];

            $envelope = $item->getEnvelope();
            $flags    = $item->getFlags();

            $hasAttachments = false;

            $froms = [];
            foreach ($envelope->from as $from) {
                $froms["name"]    = $from->personal ? $from->personal : $from->bare_address;
                $froms["address"] = $from->bare_address;
            }

            $tos = [];
            foreach ($envelope->to as $to) {
                $tos[] = [
                    "name"    => $to->personal ? $to->personal : $to->bare_address,
                    "address" => $to->bare_address
                ];
            }


            // parse body
            $messageStructure = $item->getStructure();
            $typeMap          = $messageStructure->contentTypeMap();
            foreach ($typeMap as $part => $type) {
                // The body of the part - attempt to decode it on the server.
                $bodyQuery->bodyPart($part, array(
                    'decode' => true,
                    'peek'   => true,
                    'length' => 200
                ));
            }

            $textPlain = "";

            $messageData = $client->fetch(
                $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($id)]
            )->first();
            if ($messageData) {
                $plainPartId = $messageStructure->findBody('plain');
                foreach ($typeMap as $part => $type) {
                    $stream = $messageData->getBodyPart($part, true);
                    $partData = $messageStructure->getPart($part);
                    $partData->setContents($stream, array('usestream' => true));
                    if ($part == $plainPartId) {
                        $textPlain = $partData->getContents();
                    } else if ($filename = $partData->getName($part)) {
                        $hasAttachments = true;
                    }
                }
            }


            $messageItem = [
                "id"      => $item->getUid(),
                "mailAccountId"  => $accountId,
                "mailFolderId"   => $mailFolderId,
                "from"           => $froms,
                "to"             => $tos,
                "size"           => $item->getSize(),
                "subject"        => $envelope->subject,
                "date"           => $envelope->date->format("Y-m-d H:i"),
                "seen"           => array_search(\Horde_Imap_Client::FLAG_SEEN, $flags) !== false,
                "answered"       => array_search(\Horde_Imap_Client::FLAG_ANSWERED, $flags) !== false,
                "draft"          => array_search(\Horde_Imap_Client::FLAG_DRAFT, $flags) !== false,
                "flagged"        => array_search(\Horde_Imap_Client::FLAG_FLAGGED, $flags) !== false,
                "recent"         => array_search(\Horde_Imap_Client::FLAG_RECENT, $flags) !== false,
                "previewText"    => mb_convert_encoding($textPlain, 'UTF-8', 'UTF-8'),
                "hasAttachments" => $hasAttachments
            ];


            $messageItems[] = $messageItem;
        }

        return ["data" => $messageItems, "total" => $total];
    }



    /**
     * Sends a query against the currently conected IMAP server for retrieving
     * a list of messages based on the specified $options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $mailFolderId The global name of the mailbox to search in
     * @param array $options An array with the following options
     * - start (integer) The start position from where to return the results
     * - limit (integer) The number of items to return.
     *
     * @return array
     */
    protected function queryItems(\Horde_Imap_Client_Socket $client, string $mailFolderId, array $options) :array {

        $start = $options["start"];
        $limit = $options["limit"];

        // search and narrow down list
        $searchQuery = new \Horde_Imap_Client_Search_Query();
        $results = $client->search($mailFolderId, $searchQuery, [
            "sort" => [\Horde_Imap_Client::SORT_REVERSE, \Horde_Imap_Client::SORT_DATE]
        ]);

        $total = count($results['match']);

        $tmpList = new \Horde_Imap_Client_Ids();
        foreach ($results['match'] as $key => $entry) {
            if ($key >= $start && $key < $start + $limit) {
                $tmpList->add($entry);
            }
        }
        $list = $tmpList->ids;

        // fetch IMAP
        $fetchQuery = new \Horde_Imap_Client_Fetch_Query();
        $fetchQuery->flags();
        $fetchQuery->size();
        $fetchQuery->envelope();
        $fetchQuery->structure();

        $fetchResult = $client->fetch($mailFolderId, $fetchQuery, ['ids' => $tmpList]);

        return [
            "total"       => $total,
            "sortedIds"   => $list,
            "fetchResult" => $fetchResult
        ];
    }

}