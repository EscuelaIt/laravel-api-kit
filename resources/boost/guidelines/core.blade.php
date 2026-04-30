## Escuela IT Laravel API Kit

escuelait/laravel-api-kit provides a structured, service-based approach to building RESTful APIs with resource listing, filtering, pagination, and response adaptation. 

It also provides a simple mechanism to execute batch actions on resource items, streamlining the validation of data required to execute the actions and the access to the involved models.

### Core Concepts

**ListService**: An abstract service class that handles querying, filtering, sorting, and pagination for resource listings. Encapsulates business logic away from controllers.

**ResourceListable Trait**: Used in controllers to delegate list operations to a ListService, keeping controllers thin and focused.

**ActionService**: Service to configuring the action execution system.

**ActionHandler trait**: which is responsible for invoking the requested action and passing the required data.

**Actions**: Classes yo define particular behaviors for each type of action

Always activate the `laravel-api-kit-development` skill when working with resource listing or resource action endpoints.