<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2020-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
| The following routes represent the rest-api-email API V0.
| https://github.com/conjoon/rest-api-description
*/

$router->post(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}',
    'MessageItemController@sendMessageDraft'
);

$router->get('MailAccounts', 'MailAccountController@index');

$router->get('MailAccounts/{mailAccountId}/MailFolders', 'MailFolderController@index');

// {mailFolderId:.*} allows for %2F (forward slash) in route when querying MessageItems if AllowEncodedSlashes
// webserver option is set to "on"
$router->get(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems',
    'MessageItemController@index'
);
$router->post(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems',
    'MessageItemController@post'
);

/**
 * GET MessageItem / MessageBody / MessageDraft
 */
$router->get(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}',
    'MessageItemController@get'
);

$router->patch(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/MessageItem',
    'MessageItemController@patchMessageItem'
);

$router->patch(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/MessageDraft',
    'MessageItemController@patchMessageDraft'
);

$router->delete(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}',
    'MessageItemController@delete'
);

/**
 * MessageBody
 */
$router->get(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/MessageBody',
    'MessageItemController@getMessageBody'
);

$router->patch(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/MessageBody',
    'MessageItemController@patchMessageBody'
);

/**
 * Attachments
 */
$router->get(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/Attachments',
    'AttachmentController@index'
);
$router->post(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/Attachments',
    'AttachmentController@post'
);
$router->delete(
    'MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/Attachments/{id}',
    'AttachmentController@delete'
);
