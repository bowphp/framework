# Bow Event

Bow Framework's event driver system is very simple event subscription and interpage page event:

Let's show a little exemple:

```php
add_event("send.email", function ($payload) {
	$name = $payload['name'];
	echo "An email was sent to $name"
	doSomething();
});

sendEmailAction();

emit_event("send.email", ['name' => "Franck DAKIA"]);
```

NB: Is recommanded to write all event listener into simple class and define the event to the app Kernel file in boot method.

```php

use App\Models\Activity;

class ActivityEvent
{
	/**
	 * Listener method
	 * 
	 * @param array payload
	 * @return mixed
	 */
	public function process($payload)
	{
		Activity::create($payload);
	}
}
```

Into Kernel file

```php
public function boot()
{
	parent::boot();

	add_event("user.activity", ActivityEvent::class);
}
```