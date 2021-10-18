# Conjoon\Mail\Client\Message

This package provides various entities such as `MessageItem`, `MessageItemDraft`, `MessageBody` and `MessageBodyDraft`.
A client should always create a `MessageBody` first, and then update its header information.

## MessageItems

### `Conjoon\Mail\Client\Message\MessageItem`

This class represents simple envelope information for an Email Message. it is used for retrieving informations when
listing contents of mailboxes and when message flags are updated, but never for creating an Email Message.

### `Conjoon\Mail\Client\Message\MessageItemDraft`

This class represents envelope information for an Email Message which is requested by the client for editing or
composing. This entity has always a `MessageKey`.

## MessageBodies

### `Conjoon\Mail\Client\Message\MessageBody`

This class represents `textHtml` and `textPlain` information of an Email Message. It is used for retrieving data when
reading an Email Message.

### `Conjoon\Mail\Client\Message\MessageBodyDraft`

This class represents a MessageBody with a `textHtml` and `textPlain` property. This entity is used when messages are
created or updated, or if messages are requested for editing. A `MessageBodyDraft` has only a `MessageKey` once it was
created.  
