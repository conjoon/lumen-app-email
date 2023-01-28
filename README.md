# conjoon/lumen-app-email ![MIT](https://img.shields.io/github/license/conjoon/lumen-app-email) ![Tests](https://github.com/conjoon/lumen-app-email/actions/workflows/run.tests.yml/badge.svg)
Backend service for IMAP/SMTP email messaging.

## What is lumen-app-email?
**conjoon/lumen-app-email** is a standalone **PHP🐘** application built with [Lumen](https://lumen.laravel.com).
It provides a backend for Email messaging according to [https://github.com/conjoon/rest-api-description](conjoon/rest-api-description)
and serves endpoints for reading, writing and sending email messages with **IMAP**/**SMTP**.

**lumen-app-email** has minimal footprint and is easy to install. 

## Installation

```shell
$ composer create-project conjoon/lumen-app-email htdocs "1.*" 
```
Please refer to the official [documentation](./docs) of **lumen-app-email** for further information on installation and configuration.



## Features

**lumen-app-email** follows a service oriented approach. 
Services are easily replaceable with the help of upfront DI configurations and related bindings. 

**Use lumen-app-email, if you...**
- need a fully functional middleware for communicating with IMAP / SMTP servers
- want to provide webmail solutions with various authentication methods, such as 
  - domain-specific sign-in to IMAP accounts, orchestrated by the server 
  - connecting to IMAP accounts solely configured by the users 
- are looking for an email messaging solution with minimal footprint that's easy to distribute 
- require a headless, service oriented standalone application in your infrastructure that can also be run frontend agnostic

## API Examples

````http request
# Return a list of available MailAccounts for the requesting client
GET /MailAccounts HTTP/1.1
Content-Type: application/json
Authorization: Basic Y29uam9vbjpIZWxsb1dvcmxk
Host: hostname

# Return the envelope data of the first 50 MessageItems w/o previewText of the INBOX mailbox
# for the MailAccount identified by "gmail"
GET /MailAccounts/gmail/MailFolders/INBOX/MessageItems?start=0&limit=50&attributes=*,previewText HTTP/1.1
Content-Type: application/json
Authorization: Basic Y29uam9vbjpIZWxsb1dvcmxk
Host: hostname

# Return the email identified with the uid 4356 of the INBOX mailbox for the MailAccount identified by "gmail"
GET /MailAccounts/gmail/MailFolders/INBOX/MessageItems/4356 HTTP/1.1
Content-Type: application/json
Authorization: Basic Y29uam9vbjpIZWxsb1dvcmxk
Host: hostname
````


## Backend API documentation
  <br>For the list of endpoints this service provides, please refer to the 
  [OpenApi-documentation of `rest-api-email`](https://github.com/conjoon/rest-api-description), available as OpenAPI documentation at [conjoon.stoplight.io](https://conjoon.stoplight.io/docs/rest-api-description/)

## Additional Notes
### Official Documentation
The official documentation can be found at the project page for [conjoon](https://www.conjoon.org/docs/api/backends/@conjoon/lumen-app-email).
This documentation is in line with the offical project page.

### WIP
**lumen-app-email** is a work in progress. We are constantly improving the API and strive for a RESTful implementation.
You can follow us on [Github](https://github.com/conjoon/lumen-app-email) for updates related to this and other related projects.

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
