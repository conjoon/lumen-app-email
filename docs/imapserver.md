# IMAP server configurations
When the instance of **lumen-app-email** was configured with the `single-imap-user` Authentication Provider, you
will have to maintain a list of IMAP server users may sign in to. 

Use [`php artisan configure:api`](./commands.md#configureapi) for configuring the Authentication Provider.

In order for users to authenticate against IMAP servers, `lumen-app-email` provides a template-configuration file in `app/config/imapserver.php.example`.
In this file, you can specify an array of mail server configurations. Each entry represents a mail server to which connection may be established, for both sending and receiving messages.

Note:
[`php artisan copyconfig`](./commands.md#copyconfig) can be used for automatically copying the configuration template
`imapserver.example.php` to its target destination. If you choose to manually work with the template, copy and rename it 
to `imapserver.php`, then adjust its entries.

## Entry Details

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
        "subscriptions"   => ["INBOX"],
        "match"           => ["/\@(googlemail.)(com)$/mi"]
    ]
```

| Option                           | Description                                                                                                                                                                                                   |
|----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
  `id`:_string_    | required for identifying the mail account. **MUST** be unique in the configuration file. Will be refered to as `mailAccountId` throughout [rest-api-email](https://conjoon.stoplight.io/docs/rest-api-description). |
|| **IMAP Settings** |
`inbox_type`:_string_ | the protocol used with the server for receiving messages. Right now, only **IMAP** is supported.                                                                                                              |
`inbox_address`:_string_ | (ip-)address of the server                                                                                                                                                                                    |
`inbox_port`:_integer_ | port that should be used with `inbox_address` for server communication                                                                                                                                        |
`inbox_ssl`:_boolean_ | use encrypted communication with the server. `true`: use **SSL** for encryption, `false`: use no encryption                                                                                                   |
|| **SMTP Settings** |
`outbox_address`:_string_ | (ip-)address of the server                                                                                                                                                                                    |
`outbox_port`:_integer_ | port that should be used with `outbox_address` for server communication                                                                                                                                       |
`outbox_secure`:_string_ | the encryption protocol to use with SMTP. Can be any of `ssl`, `tls` or `starttls`                                                                                                                            |
|| **Account Settings** |
`match`:_array_ | a regular expression that matches an email-address to **THIS** server configuration.                                                                                                                          |
`subscriptions`:_array_ | an array of mailbox names that denote the mailboxes the account has subscribed to. Leave the array empty if all mailboxes should be read out and send to the client.    |



### Example for `match`
**Prerequisite:** `match` is set to `["/\@(googlemail.)(com)$/mi"]`
<br>
A client authenticates with the username "name@**googlemail.com**". `lumen-app-email` will query through the configurations of `imapserver.php` and test **this** username against regular expression defined in `match`. In this example, the above regular expression matches the username (i.e. email address). The configuration where the regular expression is specified will be used for subsequent operations requested by the client.

### Example for `subscriptions`
A common mailbox layout of IMAP servers looks like this: 
```
INBOX
INBOX.Drafts
INBOX.Sent
INBOX.Junk
INBOX.Trash
```

If the `subscriptions`-configuration is set to `["INBOX"]`, the following mailboxes will be returned to the client:

```
INBOX
Drafts
Sent
Junk
Trash
```

Multiple subscription entries will be considered. This is useful with Google Mail, where root mailboxes (in gmail terms: "labels") can either be `[Google Mail]` or `[Gmail]`. `subscriptions` should be set to `["[Google Mail]", "[Gmail]"]` in this case. 


## Additional Resources
The documentation of [rest-api-email](https://conjoon.stoplight.io/docs/rest-api-description) provides information about querying available mailboxes for an authenticated client.
