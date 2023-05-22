# Bow Translate

Bow Framework's translation system is a very simple translation API. Also supports pluralization.

This is the sample configuration

```php
// frontend/lang/en/messages.php
return [
  'welcome' => 'Welcome to our application'
];
```

Let's show a little example:

```php
use Bow\Translate\Translator;

echo Translator::translate('messages.welcome');
// Or
echo trans('messages.welcome');
```
