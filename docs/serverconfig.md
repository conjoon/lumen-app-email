# IMAP server configurations
In order for users to authenticate against IMAP servers, `lumen-app-email` provides a template-configuration file in `app/config/imapserver.php.example`.
In this file, you can specify an array of mail server configurations. Each entry represents a mail server to which connection may be established, for both sending and receiving messages.

Rename this file to `imapserver.php` once all configurations are defined.

## Example entry

```php
    [
        "id"              => "Google_Mail",
        "inbox_type"      => "IMAP",
        "inbox_address"   => 'imap.gmail.com',
        "inbox_port"      => 993,
        "inbox_ssl"       => true,
        "outbox_address"  => "smtp.gmail.com",
        "outbox_port"     => 465,
        "outbox_secure"   => "ssl",
        "root"            => ["INBOX"],
        "match"           => ["/\@(googlemail.)(com)$/mi"]
    ]
```

#### `id` : `string`
required for identifying the mail account. **MUST** be unique in
the configuration file. Will be refered to as `mailAccountId` throughout [rest-api-email](https://conjoon.stoplight.io/docs/rest-api-description).

### IMAP Server Settings

#### `inbox_type` : `string`
the protocol used with the server for receiving messages. Right now, only **IMAP** is supported.

#### `inbox_address` : `string`
the address where the inbox server can be found

#### `inbox_port` : `integer`
port that should be used with `inbox_address` for server communication 

#### `inbox_ssl` : `boolean`
use encrypted communication with the server. `true` will use **SSL** for encryption

#### SMTP Server Settings

#### `outbox_address` : `string`
the address where the SMTP server can be found

#### `outbox_port` : `integer`
port that should be used with `outbox_address` for server communication

#### `outbox_secure` : `string`
the encryption protocol to use with SMTP. Can be any of `ssl`, `tls` or ``starttls`

### Account Settings

#### `match` : `array`
a regular expression that matches an email-address to **THIS** server configuration. 
Example: If a user signs in with the username "name@**googlemail.com**", `lumen-app-email` will query through its
configurations and test this username against regular expression defined in `match`. The match's associated configuration will then be used with the request.

#### `root` : `array`
an array of mailbox names that serve as root folders to display for this account. Leave the array empty
if all mailboxes should be read out and send to the client. 
For example, a common mailbox layout of IMAP servers looks like this:

```
INBOX
INBOX.Drafts
INBOX.Sent
INBOX.Junk
INBOX.Trash
```

If the `root`-configuration is set to `INBOX`, the following mailboxes will be returned to the client:

```
INBOX
Drafts
Sent
Junk
Trash
```

Multiple root entries will be considered. Useful with Google Mail, where root mailboxes (in gmail terms: "labels") can either be `[Google Mail]` or `[Gmail]` 

## Additional Resources

The documentation of [rest-api-email](https://conjoon.stoplight.io/docs/rest-api-description) provides information about querying available mailboxes for an authenticated client.
