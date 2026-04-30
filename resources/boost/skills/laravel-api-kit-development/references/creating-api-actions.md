# Develop Resource Actions

To define particular behaviors for each type of action, we use action classes. Actions must extend the abstract `CrudAction` class.

The `ActionHandler` trait will call the corresponding action, deduced from the `type` value and according to the corresponding class in the `$actionTypes` array of the `ActionService`.

When building the action, three pieces of data will be available:

- The user authenticated in this request, or `null` if none exists. This user will be stored as the `$user` property.
- The related models based on the query, available in the `$models` property.
- The data sent in the request to the controller in the `data` field of the payload. This data will be available in the `$data` property.

With this data, an action can be defined as shown in the following example:

```php
namespace App\Actions\User;

use EscuelaIT\APIKit\CrudAction;
use EscuelaIT\APIKit\ActionResult;

class SetAdminUserAction extends CrudAction {

    protected function validationRules(): array {
        return [
            'is_admin' => 'nullable|boolean',
        ];
    }

    public function handle(): ActionResult
    {
        foreach($this->models as $model) {
            if($this->user->can('update', $model)) {
                $model->is_admin = $this->data['is_admin'];
                $model->save();
            }
        }
        return $this->createActionResultSuccess('Admin updated', [
            'new_value' => $this->data['is_admin'],
        ]);
    }
}
```

- The `validationRules()` method allows defining validation rules for the data set contained in the `data` field of the payload. The action processing flow defined in `CrudAction` already provides all the mechanisms to validate the action data and return the validation errors.
- The `handle()` method is responsible for processing the action. Typically in this method, you iterate over each of the models in the `$models` property. For each model, you can check if the user has permission to perform the actions and then execute the action if possible.

## Action responses in handle() method

The action must return a response by sending an instance of the `ActionResult` class. To facilitate creating the response in the appropriate format, the `CrudAction` class provides:

- `createActionResultSuccess($message, $data)`: Allows generating an `ActionResult` instance for a positive response.
- `createActionResultError($message, $errors)`: Allows generating an `ActionResult` instance for a negative response

### Action Response Formats

Once the action is processed, a response will be sent to the client. The format is consistent with other response formats from other types of API requests.

In case of success, a JSON will be sent to the client in this format:

```json
{
    "status": 200,
    "message": "Admin updated",
    "data": {
        "msg": "Admin updated",
        "action": "SetAdminUserAction",
        "data": {
            "new_value": false
        }
    },
    "errors": [],
    "execution": "84ms",
    "version": "1"
}
```

In case of a validation error, the response format would be the following:

```json
{
    "status": 422,
    "message": "The provided data is not valid.",
    "errors": {
        "foo": [
            "The foo field is required."
        ]
    },
    "data": [],
    "execution": "197ms",
    "version": "1"
}
```

