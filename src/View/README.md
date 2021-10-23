# Bow View

Bow Framework's view system is powerful and big simple template engine.

We take this template file `template.tintin.php`

```html
Show the legend message of programming learn
<h1>Hello world</h1>
```

Rendre the template file:

```php
use Bow\View\View;

$content = View::parse("template");
```
