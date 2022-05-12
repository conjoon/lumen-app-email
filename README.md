# conjoon/lumen-app-email ![MIT](https://img.shields.io/github/license/conjoon/lumen-app-email) ![Tests](https://github.com/conjoon/lumen-app-email/actions/workflows/run.tests.yml/badge.svg)
Microservice for IMAP operations.

## About
**conjoon/lumen-app-email** is a PHP application built with [Lumen](https://lumen.laravel.com).
It provides the REST API implementation according to [conjoon\/rest-api-description\#rest-api-email](conjoon/rest-api-description)
and serves as a functional webmail-backend for IMAP operations.

## Available REST API endpoints
* **rest-api-email**
  <br>For the list of endpoints this microservice provides, please refer to the OpenApi-documentation of `rest-api-email`,
  hosted at [conjoon/rest-api-description](https://github.com/conjoon/rest-api-description).

## Installation

```shell
$ git clone https://github.com/conjoon/lumen-app-email
$ cd ./lumen-app-email
$ composer i
```

The official [Lumen\-documentation](https://lumen.laravel.com/docs/) has guides 
on setting up a webservers running a Lumen application.

#### Configuration
Please refer to the official [documentation](./docs) of **lumen-app-email** for further information on
installation and configuration.

## Additional Resources
### Official Docker Container
[conjoon\/ddev-ms-email](conjoon/ddev-ms-email) provides a `.ddev`/**Docker** configuration for a container
running **lumen-app-email** out-of-the-box.

## Troubleshooting
### Composer 2.0 - Pear/Horde vows
As of **v1.0.1**, **[php-lib-conjoon](conjoon/php-lib-conjoon)** no longer requires _Composer 1.*_ for installation.
For _Composer 2.*_-compatibility, **php-lib-conjoon** relies on the following private composer
package repository:

```
https://horde-satis.maintaina.com
```
This repository is mentioned in **THIS** package's _composer.json_
This repository is also mentioned in the _composer.json_-file of
[horde\/horde_deployment](https://github.com/horde/horde-deployment/blob/master/composer.json).
