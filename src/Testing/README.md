# Bow Testing

Bow Framework's testing system is the extension of PHPUnit and add the route testing like baby.

Let's show a little exemple:

```php
use Bow\Testing\TestCase;

class HelloWorldTest extends TestCase
{
    public function test_a_user_can_show_landing_page()
    {
        $response = $this->get('/landing');
        
        $response->assertStatus(200);
        $response->assertContentType('text/html');
    }
}
```
