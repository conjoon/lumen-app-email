# IMAP server configurations
In order for users to authenticate against IMAP servers, `lumen-app-email` provides
a template-configuration file in ```config/imapserver.php.example```.
In this file, you can specify an array of supported IMAP servers to which users
of your application can connect to.
This is how an example entry for the array looks like:;
```
    "id"              => "Google_Mail",
    "inbox_type"      => "IMAP",
    "inbox_address"   => 'imap.gmail.com',
    "inbox_port"      => 993,
    "inbox_ssl"       => true,
    "outbox_address"  => "smtp.gmail.com",
    "outbox_port"     => 465,
    "outbox_ssl"      => true,
    "root"            => ["INBOX"],
    "match"           => ["/\@(googlemail.)(com)$/mi"]
```
Along with the usual information regarding the connection options, the "match" entry should be a regular
expression that matches an email-address to "this" specific server configuration. The example
above will use the connection information for every user that uses an email-address matching
the regular expression ```"/\@(googlemail.)(com)$/mi"```.
Copy and rename this file to ```config/imapserver.php``` once all IMAP-servers were configured.
