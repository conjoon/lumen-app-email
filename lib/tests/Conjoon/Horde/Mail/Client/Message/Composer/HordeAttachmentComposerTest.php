<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Horde\Mail\Client\Message\Composer;

use Conjoon\Horde\Mail\Client\Message\Composer\HordeAttachmentComposer;
use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Message\Composer\AttachmentComposer;
use Horde_Mime_Exception;
use Tests\TestCase;

/**
 * Class HordeAttachmentComposerTest
 * @package Tests\Conjoon\Horder\Mail\Client\Message\Composer
 */
class HordeAttachmentComposerTest extends TestCase
{

    public function testClass()
    {
        $composer = new HordeAttachmentComposer();
        $this->assertInstanceOf(AttachmentComposer::class, $composer);
    }


    /**
     * @throws Horde_Mime_Exception
     */
    public function testCompose()
    {

        $fileAttachmentList = $this->createFileAttachmentList();
        $composer = new HordeAttachmentComposer();

        $default = ["Content-Type: multipart/mixed; boundary=\"=_zu6MNuSkrIrVCCCyolqPVoz\"",
            "MIME-Version: 1.0",
            "",
            "This message is in MIME format.",
            "",
            "--=_zu6MNuSkrIrVCCCyolqPVoz",
            "Content-Type: text/html; charset=utf-8",
            "",
            "foo",
            "--=_zu6MNuSkrIrVCCCyolqPVoz",
            "Content-Type: text/plain; charset=utf-8",
            "",
            "bar",
            "--=_zu6MNuSkrIrVCCCyolqPVoz"];

        $withAttachments = array_merge($default, [
            "Content-Type: text/plain; name=2",
            "Content-Disposition: attachment; size=1; filename=2",
            "",
            "",
            "--=_zu6MNuSkrIrVCCCyolqPVoz",
            "Content-Type: text/plain; name=2",
            "Content-Disposition: attachment; size=1; filename=2",
            "",
            "",
            "--=_zu6MNuSkrIrVCCCyolqPVoz--"
        ]);

        $result = $composer->compose(implode("\n", $default), $fileAttachmentList);
        $result = explode("\n", $result);

        foreach ($result as $idx => $line) {
                $this->assertSame($withAttachments[$idx], $line);
        }
    }


    /**
     * @return FileAttachmentList
     */
    protected function createFileAttachmentList(): FileAttachmentList
    {
        $attachment1 = $this->createAttachment(1);
        $attachment2 = $this->createAttachment(2);

        $attachmentList = new FileAttachmentList();
        $attachmentList[] = $attachment1;
        $attachmentList[] = $attachment2;

        return $attachmentList;
    }


    /**
     * @param $id
     * @return FileAttachment
     */
    protected function createAttachment($id): FileAttachment
    {
        return new FileAttachment(
            new AttachmentKey(
                "dev",
                "INBOX",
                "123",
                "" . $id
            ),
            [
                "type"     => "text/plain",
                "text"     => "2",
                "size"     => 1,
                "content"  => "4",
                "encoding" => "base64"
            ]
        );
    }
}
