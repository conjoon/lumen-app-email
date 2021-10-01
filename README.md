# conjoon/php-ms-imapuser
Simplistic RESTful PHP backend created with [Lumen](https://github.com/laravel/lumen/) for [conjoon](https://github.com/conjoon), 
supporting [extjs-app-webmail](https://github.com/conjoon/extjs-app-webmail) with [extjs-app-imapuser](https://github.com/conjoon/extjs-app-imapuser).

This microservice provides Basic Authorization against a single IMAP server, providing access to it's mailboxes. 

## Installation

### Development
Starting up a default **ddev** instance with
```shell
$ ddev start
```
will expose the API to https://php-ms-imapuser.ddev.site. Please refer to the `ddev`-configuration if you need to adjust
settings according to your environment.

## Available Rest API
* api-imap 
  <br>For the list of IMAP commands this microservice provides, please refer to the OpenApi-documentation of `api-imap.json`,
  hosted at [conjoon/openapi-docs repository](https://github.com/conjoon/openapi-docs).
* api-imapuser
  <br>Authenticating a user against a single IMAP account
  is specified in the OpenApi-documentation of `api-imapuser.json`,
  hosted at [conjoon/openapi-docs repository](https://github.com/conjoon/openapi-docs/).

## Usage

### Adding pre-configured server configurations
In order for users to authenticate against IMAP servers, `php-ms-imapuser` provides 
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


## Troubleshooting
In case you cannot run tests from within this folder with your phpunit-installation, try running the tests with
phpunit included in the vendor directory:
```./vendor/bin/phpunit```

### Composer 2.0 / Horde vows
Unfortunately, there is no full support for the required Horde packages as of now. If you experience any troubles running ```composer update```, the following will most likely help:

```
// remove the "requires" and "repositories" from the composer.json
//
> composer self-update --1
// add the previously "requires" and "repositories" from the composer.json back
// ...
> composer update
> composer self-update --rollback
```
