# Action system configuration

To use the action execution system on individual resource items or batches of resource items from the API, it is necessary to implement the following components:

- An `ActionService` where the action system is configured.
- A controller that uses the `ActionHandler` trait to execute the actions.
- Specific classes for each action to be executed, which extend the `CrudAction` class.

## ActionService

To configure the action system for each entity, we use the `ActionService` class.

The most common approach is to create our own service by extending the `ActionService` class and making the appropriate configurations.

```php
namespace App\Services\User;

use App\Models\User;
use EscuelaIT\APIKit\ActionService;
use EscuelaIT\APIKit\Actions\DeleteAction;

class UserActionService extends ActionService
{
    protected string $actionModel = User::class;
    protected array $actionTypes = [
        'DeleteAction' => DeleteAction::class,
        'SetAdminUserAction' => SetAdminUserAction::class,
    ];
}
```

### ActionService Properties

The properties that can be configured in the custom `ActionService` are the following:

- `$actionModel`: Allows defining the class of the model that will be used to process the actions.
- `$actionTypes`: Aassociative array that defines the types of actions that can be performed on a model. In this array, the keys will be the action name, which corresponds to the `type` value received by the controller via POST. The value will be the action class responsible for processing it.
- `$maxModelsPerAction`: Allows specifying the maximum number of elements on which an action is allowed to be executed. The default value of this property is 100.
`$identifierField`: This is the column in the model's table where matches will be looked for to determine which elements should be processed. The default value is `id`. So it expects that when requesting action processing, the identifiers of the elements are sent in the `relatedIds` field sent via POST to the controller.

### ActionService Methods

Customize the service's behavior, overriding methods that allow performing arbitrary actions.

- `createQuery()`: initializes the query
- `queryModels()`: filters the models with the received identifiers
- `getModels()`: returns the collection of models resulting from the query

For example, this would restrict the `ActionService` to executing actions only on items that belong to the authenticated user.

```php
protected function createQuery()
{
    $user = Auth::user();
    throw_if(is_null($user), AuthenticationException::class);
    
    return $this->actionModel::query()->where('user_id', $user->id);
}
```

## Action Controller

A single controller can execute all necessary actions on an entity. This controller will receive the data to process the action via POST, such as the action type, the identifiers of the elements on which the action should be executed, and any additional data the action needs.

To use the controller, we will create a route like this:

```php
Route::post('/users/action', ActionUsersController::class);
```

### ActionHandler Trait

To implement the action controller, the `ActionHandler` trait is provided. You must implement the method configured in the POST route and invoke the `handleAction()` method provided by the `ActionHandler` trait, passing an instance of the `ActionService` that defines the action system configuration.

For convenience, it's always useful to have the `ActionService` instance injected into the method, delegating to Laravel's service container the instantiation of the corresponding service.

```php
public function __invoke(UserActionService $userActionService)
{
    return $this->handleAction($userActionService);
}
```

### Payload expected

The controller expects to receive a payload like the following:

```json
{
    "type": "SetAdminUserAction",
    "relatedIds": [
        "3",
        "5"
    ],
    "data": {
        "is_admin": false
    }
}
```

- `type`: the type of action that will be executed
- `relatedIds`: the identifiers of the models on which the action will be executed
- `data`: any set of data that the action requires to be processed

### Creating Actions

To create classes that implement actions, please consult this reference: `./creating-api-actions.md`