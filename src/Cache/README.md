# Bow Cache

Bow Framework's cache system is very simple cache manager

- Filesystem
- Database
- Redis
- With extended driver interface

Let's show a little exemple:

```php
$content = cache("name");
```

By specifying the driver:

```
$content = Cache::store('redis')->get('name');
```

Is very enjoyful api
