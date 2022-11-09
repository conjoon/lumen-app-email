# Troubleshooting

:::info Found an issue?

If you find any issue not listed here, please let us know!
For reporting issues, please file a ticket over at [GitHub](https://github.com/conjoon/lumen-app-email)!

:::

## Authentication
The following explains the most common issues that can occur when trying to connect to your email account of a free email service from 3rd party clients.

### Allow IMAP and SMTP with your free email account
Due to security reasons, some free email services (such as [Freenet](https://freenet.de)) require you to explicitly allow external access of their IMAP and/or SMTP servers with your account. This setting is most often found directly in the user interface of the service's webmail client.

### Using a one-time password with your free email account
When using 2FA with your account for a free email service (such as [Yahoo! Mail](https://yahoo.com) or [Google Mail](https://gmail.com)), you most likely won't be able to connect directly to your IMAP account by providing the configured username (email address) and password. Those free email services allow for setting a one-time-password for accessing email operations with 3rd party clients. It is most often directly found in the account settings page of the service and should be used if you experience problems when signing-in. 

## Known issues
Here is a list of known issues with free email services when using [**lumen-app-email**](https://github.com/conjoon/lumen-app-email)/[**php-lib-conjoon**](https://github.com/conjoon/php-lib-conjoon); most of the time, these issues are caused by special implementations of the communication protocols being used.

### Google Mail
###### Experiencing "Moving the message to the message to the Sent-folder failed"-messages
[Google Mail](https://gmail.com) uses labels for messages, effectively replacing mailboxes. Representing the root-layout of a mailbox-hierarchy, Google Mail has so called "system labels" for special operations related to messages, such as labels for messages that were sent, or marked as spam, or a part of the inbox of a mail account. 
<br>
When accessing Google Mail servers and sending emails, **lumen-app-email** cannot move a message into the Google Mail's "sent" mailbox: The label used here internally will already be automatically added to messages that were sent. This is why you are getting notices on the screen indicating that moving a sent email failed. This does not break functionality; you will still find the messages in the "sent" folder.

### iCloud
###### Experiencing empty mailboxes when they should not be empty
**horde/imap_client** - which is internally used with [**lumen-app-email**](https://github.com/conjoon/lumen-app-email) and [**php-lib-conjoon**](https://github.com/conjoon/php-lib-conjoon) - has some troubles querying message items from iCloud servers. Messages can be indexed, but the PHP-library fails to retrieve them. 
