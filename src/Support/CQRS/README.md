# Bow CQRS

CQRS (Command Query Responsibility Segregation). It's a pattern that I first heard described by Greg Young. At its heart is the notion that you can use a different model to update information than the model you use to read information. For some situations, this separation can be valuable, but beware that for most systems CQRS adds risky complexity.

[For more information](https://www.martinfowler.com/bliki/CQRS.html)

Create the example command:

```php
class CreateUserCommand implements CommandInterface
{
    public function __construct(public string $username, public string $email) {}
}
```

Create the handler here:

```php
class CreateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(public UserService $userService) {}

    public function process(CommandInterface $command): mixed
    {
        if ($this->userService->exists($command->email)) {
            throw new UserServiceException("The user already exists");
        }

        return $this->userService->create([
            "username" => $command->username,
            "email" => $command->email
        ]);
    }
}
```

Execute the command in controller:

```php
class UserController extends BaseController
{
    public function __construct(private CommandBus $commandBus) {}

    public function __invoke(Request $request)
    {
        $command = new CreateUserCommand($request->get('username'), $request->get('email'));

        $result = $this->commandBus->execute($command);

        return redirect()->back()->withFlash("message", "User created");
    }
}
```

Put a new route:

```php
$app->post("/create-user", UserController::class);
```