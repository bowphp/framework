# Bow HTTP

Bow Framework's http system is provider all you need to provider HTTP response.

- Custom header
- Automatique response type detection
- File downloading
- etc.

Let's show a little exemple:

```php
use Bow\Http\Request;

$app->post('/', function (Request $request) {
    $name = $request->get('name');

    response()->addHeader("X-Custom-Header", "Bow Framework");

    return response()->json(["data" => "Hello $name!"]);
});
```

Is very enjoyful api
