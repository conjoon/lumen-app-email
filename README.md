# conjoon/php-ms-imapuser ![MIT](https://img.shields.io/github/license/conjoon/php-ms-imapuser) ![Tests](https://github.com/conjoon/php-ms-imapuser/actions/workflows/run.tests.yml/badge.svg)


Implements **[rest-api-email](https://github.com/conjoon/rest-api-description)**.

## About

PHP microservice for accessing IMAP servers. 

## Installation

#### Development environment
Starting up a default **ddev** instance with
```shell
$ ddev start
```
will expose the API to https://php-ms-imapuser.ddev.site. Please refer to the `ddev`-configuration if you need to adjust
settings according to your environment.

#### Installing the Backend 
Navigate to the container's install directory. Then connect to the container via
```shell
$ ddev ssh
```
then run
```shell
$ composer i
```
This will install all required package dependencies.


## Available Rest API
* **rest-api-email** 
  <br>For the list of endpoints this microservice provides, please refer to the OpenApi-documentation of `rest-api-email`,
  hosted at [conjoon/rest-api-description](https://github.com/conjoon/rest-api-description).
* **rest-imapuser**
  <br>Authenticating a user against a single IMAP account.

## Required configurations

#### .env
The root directory of the project contains a [dotenv-configuration](https://github.com/vlucas/phpdotenv) file (`.env.example`).
Settings may be adjusted on your own to match your desired configuration for the environment the
microservice runs in. Copy and rename this file to `.env` and configure away!

#### 1. Allowed IMAP servers
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

#### 2. Configuring CORS

**php-ms-imapuser** uses [fruitcake/laravel-cors](https://github.com/fruitcake/laravel-cors) for enabling
[Cross-Origin Resource Sharing](http://enable-cors.org/).
<br>
A configuration template can be found in ```config/cors.php.example```. You need to create a file named
```config/cors.php``` - basically the configuration of ```config/cors.php.example``` should work, but if
you need to set specific options, this would be the place to do so.

#### Options

| Option                   | Description                                                              | Default value |
|--------------------------|--------------------------------------------------------------------------|---------------|
| paths                    | You can enable CORS for 1 or multiple paths, eg. `['api/*'] `            | `[]`          |
| allowed_origins          | Matches the request origin. Wildcards can be used, eg. `*.mydomain.com` or `mydomain.com:*`  | `['*']`       |
| allowed_origins_patterns | Matches the request origin with `preg_match`.                            | `[]`          |
| allowed_methods          | Matches the request method.                                              | `['*']`       |
| allowed_headers          | Sets the Access-Control-Allow-Headers response header.                   | `['*']`       |
| exposed_headers          | Sets the Access-Control-Expose-Headers response header.                  | `false`       |
| max_age                  | Sets the Access-Control-Max-Age response header.                         | `0`           |
| supports_credentials     | Sets the Access-Control-Allow-Credentials header.                        | `false`       |

## Troubleshooting
In case you cannot run tests from within this folder with your phpunit-installation, try running the tests with
phpunit included in the vendor directory:
```./vendor/bin/phpunit```

### Composer 2.0 - Pear/Horde vows
As of **v1.0.1**, _php-lib-conjoon_ no longer requires _Composer 1.*_ for installation.
For _Composer 2.*_-compatibility, _php-lib-conjoon_ relies on the following private composer
package repository:

```
https://horde-satis.maintaina.com
```
This repository is mentioned in **THIS** package's _composer.json_
This repository is also mentioned in the _composer.json_-file of
[horde\/horde_deployment](https://github.com/horde/horde-deployment/blob/master/composer.json).
