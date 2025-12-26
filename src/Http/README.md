# Bow HTTP

Bow Framework's http system is provider all you need to provider HTTP response.

- Custom header
- Automatique response type detection
- File downloading
- etc.

Let's show a little exemple:

```php
use Bow\Http\Request;

$router->post('/', function (Request $request) {
    $name = $request->get('name');
    return response()
        ->withHeader("X-Custom-Header", "Bow Framework")
        ->json(["data" => "Hello $name!"]);
});
```

Is very enjoyful api
