# Bow Session

Bow Framework's session system is beautiful interface to manage PHP Session feature and Custom session.
He support:

- Native PHP Session
- Database session driver

```php
// Get the content of name key
session("name");
```

We can set value with key `name`.

```php
session(["name", "value"]);
```

NB: You can add your custom session support
