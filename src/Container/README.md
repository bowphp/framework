# Bow Container

Bow Framework's container system is very simple and powerful class dependences building:

Let's show a little exemple:

```php
use App\Application;
use Bow\Containers\Capsule;

$capsule = Capsule::getInstance();

$capsule->bind(Application::class, function ($config) {
    return Application::make($config);
});

$app = $capsule->make(Application::class);
$app->run();
```

Is very enjoyful api
