# Bow Mail

Bow Framework's mail system is very simple email delivery system with support:

- Native PHP mail api
- SMTP setting
- SES (Simple Email System)
- With extended driver interface

Let's show a little exemple:

```php
use Bow\Mail\Message;

email('view.template', function (Message $message) {
    $message->to("papac@bowphp.com");
    $message->subject("Hello Franck DAKIA");
});
```

Is very enjoyful api
