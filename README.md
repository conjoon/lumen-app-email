# conjoon/lumen-app-email ![MIT](https://img.shields.io/github/license/conjoon/lumen-app-email) ![Tests](https://github.com/conjoon/lumen-app-email/actions/workflows/run.tests.yml/badge.svg)
Laravel/Lumen Microservice for RESTful communication with IMAP/SMTP servers.

## About
**conjoon/lumen-app-email** is a PHP application built with [Lumen](https://lumen.laravel.com).
It provides the REST API implementation according to [conjoon\/rest-api-description\#rest-api-email](conjoon/rest-api-description)
and serves as a lightweight backend providing functionality for reading, writing and sending email messages.

## Use Case

**lumen-app-email** follows a service oriented approach. Implementations are easily replacable with the help of upfront DI configurations and related bindings. 

**Use lumen-app-email, if you...**
- need a fully functional middleware for communicating with IMAP / SMTP server
- want to provide webmail solutions with domain-specific sign-in to IMAP accounts
- are looking for a distribution with minimal footprint and easy setup
- require a headless, microservice oriented architecture with your infrastructure 

**do not use lumen-app-email, if you...**
 - are looking for a stateful, session based webmail backend
 - need baked-in caching


## Supported REST API
* **rest-api-email**
  <br>For the list of endpoints this microservice provides, please refer to the OpenApi-documentation of `rest-api-email`,
  hosted at [conjoon/rest-api-description](https://github.com/conjoon/rest-api-description) and available as OpenAPI documentation at [conjoon.stoplight.io](https://conjoon.stoplight.io/docs/rest-api-description/)

## Installation and Configuration

```shell
$ git clone https://github.com/conjoon/lumen-app-email
$ cd ./lumen-app-email
$ composer i
```

The official [Lumen\-documentation](https://lumen.laravel.com/docs/) has guides on setting up webservers running Lumen applications. 
<br>
For a quick start, we suggest to use a pre-configured container for running the backend: [conjoon\/ddev-ms-email](conjoon/ddev-ms-email) provides a `.ddev`/**Docker** configuration for a container running **lumen-app-email** out-of-the-box and is easy to install.

#### Documentation
Please refer to the official [documentation](./docs) of **lumen-app-email** for further information on installation and configuration.


### Additional Notes
#### Composer 2.0 - Pear/Horde vows
As of **v1.0.1**, **[php-lib-conjoon](conjoon/php-lib-conjoon)** no longer requires _Composer 1.*_ for installation.
For _Composer 2.*_-compatibility, **php-lib-conjoon** relies on the following private composer
package repository:

```
https://horde-satis.maintaina.com
```
This repository is mentioned in **THIS** package's _composer.json_
This repository is also mentioned in the _composer.json_-file of
[horde\/horde_deployment](https://github.com/horde/horde-deployment/blob/master/composer.json).
