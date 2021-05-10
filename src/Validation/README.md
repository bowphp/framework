# Bow Validator

Bow Framework's validator system help developer to make data validation delightful.

Let's show a little exemple:

```php
$data = ["name" => "Franck DAKIA"];

$validation = validator($data, [
    "name" => "required|max:50"
]);

if ($validation->fails()) {
    doSomething();
    $validation->getMessages();
}
```
