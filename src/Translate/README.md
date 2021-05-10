# Bow Translate

Bow Framework's translate system is a very simple translate api. He support plurialize.

This the sample configuration

```php
// frontend/lang/en/messages.php
return [
  'welcome' => 'Welcome to our application'
];
```

Let's show a little exemple:

```php
use Bow\Translate\Translator;

echo Translator::translate('messages.welcome');

// Or

echo trans('messages.welcome');
```
