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
 * Class DefaultAttachmentService.
 * Default implementation of an AttachmentService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultAttachmentService implements AttachmentService {

    use ImapTrait;

    /**
     * @inheritdoc
     */
    public function getAttachmentsFor(ImapAccount $account, string $mailFolderId, string $messageItemId) :array {

        try {

            $client = $this->connect($account);

            $query = new \Horde_Imap_Client_Fetch_Query();
            $query->structure();

            $uid = new \Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();
            $typeMap          = $messageStructure->contentTypeMap();
            $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();

            foreach ($typeMap as $part => $type) {
                $bodyQuery->bodyPart($part, array(
                    'peek'   => true,
                    "usestream" => false
                ));
            }

            $messageData = $client->fetch(
                $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($messageItemId)]
            )->first();

            $attachments = [];

            if ($messageData) {

                $id = 0;
                foreach ($typeMap as $typePart => $type) {

                    $stream = $messageData->getBodyPart($typePart, true);

                    $part = $messageStructure->getPart($typePart);
                    $part->setContents($stream, array('usestream' => true));

                    if ($this->isFileAttachment($part, $typePart)) {
                        $filename = $part->getName($typePart);
                        $attachments[] = $this->buildAttachment(
                            $account, $mailFolderId, $messageItemId, $part, $filename, (string)++$id
                        );
                    }

                }
            }

        } catch (\Exception $e) {
            throw new AttachmentServiceException($e->getMessage(), 0, $e);
        }

        return $attachments;
    }


    /**
     * Returns true if the specified $part represent an attachment.
     *
     * @param $part
     * @param $index
     *
     * @return bool
     */
    protected function isFileAttachment(\Horde_Mime_Part $part, $index) :bool {
        return !!$part->getName($index);
    }


    /**
     * Builds an attachment from the specified data.
     *
     * @param $account
     * @param $mailFolderId
     * @param $messageItemId
     * @param $part
     * @param $fileName
     * @param $attachmentId
     * @return array
     */
    protected function buildAttachment(
        ImapAccount $account, string $mailFolderId, string $messageItemId, \Horde_Mime_Part $part,
        string $fileName, string $attachmentId) :array {

        $mimeType = $part->getType();
        $base64   = base64_encode($part->getContents());

        return [
            "id"            => $attachmentId,
            "mailAccountId" => $account->getId(),
            "mailFolderId" => $mailFolderId,
            "parentMessageItemId" => $messageItemId,
            "type" => $mimeType,
            "text" => $fileName,
            "size" => $part->getBytes(),
            "downloadUrl" => 'data:application/octet-stream;base64,' . $base64,
            "previewImgSrc" => stripos($mimeType, "image") === 0
                ? "data:" . $mimeType . ";base64," . $base64
                : null
        ];
    }



}