# Bow Queue

Bow Framework's queue system help you to make a simple and powerful queue/job (consumer/producer) for your process whish
take a low of time.

```php
use App\Producers\EmailProducer;

queue(new EmailProducer($email));
```

Launch the worker/consumer.

```bash
php bow run:worker --retry=3 --queue=mailing
```
