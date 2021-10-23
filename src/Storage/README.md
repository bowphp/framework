# Bow Storage

Bow Framework's storage system is beautiful interface to manage file access. He support:

- File System
- FTP
- Simple Storage System (S3)

```php
// Get the content of code.js file
mount("public")->get("code.js");
```

Load some service for work on.

```php
// Get the content of code.js file
service("ftp")->get("code.js");
```
