# conjoon/lumen-app-email Documentation

# Installation

## Via GIT & composer
```shell
$ git clone https://github.com/conjoon/lumen-app-email
$ cd ./lumen-app-email
$ composer i
```

## Via Docker (DDEV)
For a quick start, we suggest to use a pre-configured container for running the backend: [conjoon\/ddev-ms-email](https://github.com/conjoon/ddev-ms-email) provides a `.**Docker (DDEV)** configuration for a container running **lumen-app-email** out-of-the-box and is easy to install.

## From Scratch
Since **lumen-app-webmail** is a Lumen/Laravel application, detailed information on how to set up a webserver for it can be found in the official [Lumen\-documentation](https://lumen.laravel.com/docs/).


#### .env - Environment Variables
The root directory of the project contains a [dotenv-configuration](https://github.com/vlucas/phpdotenv) file (`.env.example`).
Settings may be adjusted on your own to match your desired configuration for the environment the microservice runs in. Copy and rename this file to `.env` and configure away!


## Further Documentation
 1. Configuration
    1. [Setting up CORS](./cors.md)
    2. [Configuring allowed IMAP servers](./serverconfig.md)
 2. [Troubleshooting & Known Issues](./troubleshooting.md)
