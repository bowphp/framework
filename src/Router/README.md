# Bow Router

Bow Framework's routing system is very simple with:

- Route naming support
- Route prefix support
- Route parameter catcher support

Let's show a little exemple:

```php
$app->get('/', function () {
    return "Hello guy!";
});
```

## Diagramme de flux du routage

```mermaid
sequenceDiagram
    participant Client as Client HTTP
    participant Router as Router
    participant Route as Route
    participant Middleware as Middleware
    participant Controller as Controller/Callback
    participant Response as Response

    Note over Client,Response: Traitement d'une requête HTTP
    
    Client->>Router: Requête HTTP (GET /users)
    
    Router->>Router: match(uri)
    
    alt Route trouvée
        Router->>Route: match(uri)
        Route->>Route: checkRequestUri()
        
        alt Avec Middleware
            Route->>Middleware: process(request)
            Middleware-->>Route: next(request)
        end
        
        Route->>Route: getParameters()
        Route->>Controller: call(parameters)
        Controller-->>Response: return response
        Response-->>Client: Envoie réponse HTTP
    else Route non trouvée
        Router-->>Response: 404 Not Found
        Response-->>Client: Erreur 404
    end

    Note over Client,Response: Exemple de définition de route
    
    Note right of Router: $app->get('/users/:id', function($id) { ... })
    Note right of Router: $app->post('/users', [UserController::class, 'store'])
```

Is very joyful api
