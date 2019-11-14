# php-cn_imapuser
Simplistic RESTful PHP backend created with [Lumen](https://github.com/laravel/lumen/) for [conjoon](https://github.com/conjoon), supporting [app-cn_mail](https://github.com/conjoon/app-cn_mail) with [app-cn_imapuser](https://github.com/conjoon/app-cn_imapuser).


## Troubleshooting
In case you cannot run tests from within this folder with your phpunit-installation, try running the tests with
phpunit included in the vendor directory:
```./vendor/bin/phpunit```


## Supported Routes
- ```cn_imapuser/auth``` **POST** (*app-cn_imapuser*)
- ```cn_mail/MailAccounts``` **GET** (*app-cn_mail*) 
- ```cn_mail/MailAccounts/{mailAccountId}/MailFolders``` **GET** (*app-cn_mail*)
- ```cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems``` **GET/POST** (*app-cn_mail*)
- ```cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItemId}``` **GET/PUT** (*app-cn_mail*)