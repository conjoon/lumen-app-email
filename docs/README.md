# conjoon/lumen-app-email Documentation

# Installation
The recommended way to install **lumen-app-email** is by using `composer create-project`:

`composer create-project {packageName} {targetDirectory} {version}`

The following command will install an instance of **lumen-app-email** along with its dependencies into the directory
`htdocs` relative to the current working directory:

```shell
$ composer create-project conjoon/lumen-app-email htdocs "1.*" 
```

Once `composer` has finished downloading and installing the project, the `post-create-project-cmd` will automatically invoke
`php artisan install`, the installation script for **lumen-app-email**. Please refer to the subsequent documentation for
further details about the configuration options available:

## Further Documentation
 1. [Available CLI commands](./commands.md)
    1. [Setting up CORS](./cors.md)
    2. [Configuring IMAP servers](./imapserver.md)
 3. [Troubleshooting & Known Issues](./troubleshooting.md)

## Related Resources
A pre-configured container for running an instance of **lumen-app-email** is also available and can be found at 
[conjoon\/ddev-ms-email](https://github.com/conjoon/ddev-ms-email).
