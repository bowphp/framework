# Bow Security

Bow Framework's security system protect you to CSRF, XSS and add the powerful data encryption system where you can change easily the encryption algorithm.

Create the new hash. Usualy, it's use for make user password

```php
Hash::make("hash content");
```

Encrypt data

```php
$encoded = encrypt("value");
// out it's the encrypted value
```