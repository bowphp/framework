# Bow Auth

Bow Framework auth is a native authentification system

```php
use Bow\Http\Exception\UnauthorizedException;

$auth = auth();

$logged = $auth->attempts(["username" => "name@example.com", "password" => "password"]);

if (!$logged) {
    throw new UnauthorizedException("Access denied");
}

$user = $auth->user();
```

Enjoy!
