# Bow Auth

Bow Framework auth is a native authentification system

```php
use Bow\Http\Exception\UnauthorizedException;

$auth = app_auth();

$logged = $auth->attempts(["username" => "name@example.com", "password" => "password"]);

if (!$logged) {
    throw new UnauthorizedException("Access denied");
}

$user = $auth->user();
```

Enjoy!

## Remember me

The session guard supports a persistent "remember me" cookie so users stay authenticated across browser sessions.

### Required migration

The framework cannot modify your application's database, so you must add a nullable token column to your user table:

```sql
ALTER TABLE users ADD COLUMN remember_token VARCHAR(100) NULL;
```

The default column name is `remember_token`. Override it on your user model if needed:

```php
public function getRememberTokenName(): string
{
    return 'my_token_column';
}
```

### Usage

Pass `true` as the second argument to `attempts()` or `login()`:

```php
// Authenticate with credentials and remember the user
Auth::attempts(['username' => $username, 'password' => $password], true);

// Or, for an already-resolved user instance
Auth::login($user, true);
```

### Automatic session restore

When the session has expired, the next call to `Auth::check()` or `Auth::user()` transparently restores the session from the encrypted `remember_<guard>` cookie (e.g. `remember_web`). No changes are needed in `AuthMiddleware`.

### Logout

`Auth::logout()` regenerates the user's token (invalidating any outstanding remember cookie) and clears the cookie:

```php
Auth::logout();
```

Because all devices share the same `remember_token` column, logging out on one device invalidates remember-me for that user everywhere.

### Configuration

The cookie lifetime is read from `config('auth.remember_lifetime')` in seconds and defaults to 30 days. Add the key to your `config/auth.php` to override it:

```php
'remember_lifetime' => 2592000, // 30 days (default)
```

### Scope and security notes

- Remember-me applies to the **session guard only**; the JWT guard ignores the flag.
- The token is high-entropy (`bin2hex(random_bytes(30))`) and compared timing-safely with `hash_equals`; the cookie is encrypted.
- A single shared `remember_token` column means logging out invalidates remember-me for that user on all devices.
