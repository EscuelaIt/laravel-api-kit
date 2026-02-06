# laravel-api-kit

Utilidades diversas para crear APIs en Laravel con mayor rapidez y consistencia. 

## Installation

Puedes instalar el paquete vía Composer.

```bash
composer require escuelait/laravel-api-kit
```

Una vez instalado el package Laravel registrará automáticamente el service provider.

**Compatibilidad con Laravel y PHP**: Laravel 9.0+ y PHP 8.1+. 

## Utilidades incorporadas en este paquete

Este paquete ofrece dos utilidades fundamentales para desarrollar APIs, que suelen causar bastante trabajo y que además resulta bastante repetitivo a lo largo de los distitos recursos de un API. 

1. Búsquedas y listados sobre recursos con o sin paginación
2. Ejecución de acciones en lotes de elementos

El paquete ofrece mecanismos que permiten resumir sensiblemente estas dos operativas, pudiendo personalizar su funcionamiento de manera fina, lo que elimina mucho del trabajo tedioso de desarrollo de los recursos. 

## Respuestas consistentes del API

Usando los mecanismos propuestos por este kit de utilidades de API conseguirás que tus recursos funcionen de manera consistente, de modo que tus clientes pueden trabajar de forma más predecible.

Una de las claves para conseguirlo es el uso de una bibliteca que ofrece una interfaz común al generar los JSON de las respuestas: [Laravel API Response Wrapper](https://github.com/negartarh/apiwrapper). Gracias a ella usa usa siempre un esquema de respuestas del API homogéneo.

Te animamos a usarla a ti también en el desarrollo de otras operativas de tu API en los que este kit no aporta soluciones ya listas.

## Componentes de frontend

Esta biblioteca se puede combinar perfectamente con el catálogo de [Componentes de CRUD para frontend de Dile Components](https://dile-components.com/crud/). 

Gracias a los componentes frontend de CRUD de Dile Components puedes construir de una manera sencilla interfaces de usuario para proporcionar listados, filtrados, órdenes, procesamiento de acciones en lotes, aparte de otras operativas como inserciones, ediciones y borrados. 

Si ya usas los componentes de Crud de Dile Components verás que aplicar el kit de soluciones de Laravel te permite construir la parte del backend mucho más rápido.

## Búsquedas en resource index

Laravel Api Kit ofrece funcionalidades de búsqueda y filtrado que se pueden implementar de manera cómoda en recursos de un API o en cualquier situación donde necesites devolver colecciones de elementos en JSON.

Para implementar esta utilidad se necesita implementar dos componentes:

- El trait `ResourceListable` en un controlador, que proporciona un método `list()` para realizar listados
- Un servicio de listado, basado en `ListService` para implementar búsquedas en los modelos

### trait ResourceListable

Para conseguir la funcionalidad de listados simplemente necesitamos implementar el trait ResourceListable en un controlador.

```php
namespace App\Http\Controllers;

use EscuelaIT\APIKit\ResourceListable;

class ListUsersController extends Controller
{
    use ResourceListable;
}
```

Gracias a la implementación del trait ResourceListable en el controlador se obtienen varias utilidades, principalmente:

- Método `list()` que devuelve datos de un modelo en JSON, realizando las funcionalidades de filtrado y orden de los elementos, además de la paginación opcional.
- Método `allIds()` que permite obtener la lista completa de identificadores de modelos dada una consulta, una vez aplicados los filtrados. Es útil cuando se quiere conocer todos los elementos que forman parte de un set de resultados, sin tener en cuenta la paginación para poder solicitar acciones en lote sobre ellos.

Ambos métodos requieren recibir un servicio que permite configurar de manera detallada aspectos de las consultas, como el modelo sobre el que se tiene que operar o los tipos de filtrados, entre otras cosas.

A continuación se puede ver un ejemplo de controlador que aporta funcionalidades ofrecidas por el trait:

```php
namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use EscuelaIT\APIKit\ResourceListable;
use App\Services\User\UserListService;

class ListUsersController extends Controller
{
    use ResourceListable;

    public function index()
    {
        $service = new UserListService();
        return $this->list($service);
    }

    public function ids()
    {
        $service = new UserListService();
        return $this->allIds($service);
    }
}
```

El servicio de personalización de los listados es lo que permite hacer que el trait ResourceListable funcione de manera genérica, pudiendo operar sobre cualquier entidad que se desee incorporar como recurso en el API. 

### ListService

ListService es la clase base que usamos para construir el servicio que nos sirve para configurar el comportamiento de los listados en los recursos. Para configurar el comportamiento del listado del recurso tenemos que entregar al método list() un servicio personalizado que se realiza heredando de la clase ListService que ofrece este paquete.

En ListService se pueden configurar diversos comportamientos de búsquedas o filtrados a implementar en el controlador del listado. Sin embargo, para un funcionamiento básico simplemente debemos indicar el modelo sobre el cual queremos trabajar.

Para ello definimos una propiedad $listModel a la que asignamos la clase del modelo que queremos usar para los listados.

```php
namespace App\Http\Controllers\User;

use App\Models\User;
use EscuelaIT\APIKit\ListService;

class UserListService extends ListService
{
    protected string $listModel = User::class;
}
```

Para hacer más testeable nuestro controlador podemos delegar en el contenedor de servicios de Laravel la instanciación del servicio, inyectando la instancia en el constructor del controlador.

```php
class ListUsersController extends Controller
{
    use ResourceListable;

    public function __construct(
        private UserListService $userListService
    ) {}

    public function index()
    {
        return $this->list($this->userListService);
    }
}
```

### JSON de respuesta en los listados

Con la configuración predeterminada, al invocar el método del controlador se obtendrá una respuesta JSON como la siguiente:

```json
{
  "status": 200,
  "message": "3 items found",
  "data": {
    "countItems": 3,
    "result": {
      "current_page": 1,
      "current_page_url": "http:\/\/localhost\/api\/users?page=1",
      "data": [
        {
          "id": 1,
          "name": "Dr. Richie Considine MD",
          "email": "lizeth21@example.com"
        },
        {
          "id": 2,
          "name": "Prof. Erling Harris",
          "email": "nikita.skiles@example.com"
        },
        {
          "id": 3,
          "name": "Sabrina Lubowitz",
          "email": "tanya54@example.com"
        }
      ],
      "first_page_url": "http:\/\/localhost\/api\/users?page=1",
      "from": 1,
      "next_page_url": null,
      "path": "http:\/\/localhost\/api\/users",
      "per_page": 10,
      "prev_page_url": null,
      "to": 3
    }
  },
  "errors": [],
  "execution": "64ms",
  "version": "1"
}
```

### Configuraciones de ListService vía propiedades

En el `ListService` del recurso se pueden configurar algunas propiedades para personalizar el comportamiento de los listados:

- **`$listModel`**  Indica la clase del modelo que vamos a usar para los listados.  
- **`$identifierField`** El campo de la tabla que sirve para identificar los elementos del recurso. De manera predeterminada es 'id'.
- **`$paginated`** Define si el resultado del recurso se enviará con o sin paginación. El valor predeterminado es true, lo que configura la paginación.
- **`$maxPerPage`** Sets the maximum page size allowed for paginated results. The default value is `null`, meaning no limit is enforced. When set to a positive integer, any `per_page` request exceeding this value will be automatically capped to the configured maximum. This is useful for preventing performance issues from excessively large page requests, e.g.:  
  ```php
  protected ?int $maxPerPage = 100;
  ```
  You can also configure this using the `setMaxPerPage()` method:  
  ```php
  $service->setMaxPerPage(50);
  ```
- **`$availableFilterColumns`** indica qué columnas del recurso permiten búsquedas para filtrados. El valor predeterminado es null, lo que indica que permite filtrar por cualquier campo. Por seguridad se recomienda limitar este valor para restringir la búsqueda en las columnas que se desee de manera explícita. Las columnas se entregan en un array de cadenas:
  ```php
  protected array $availableFilterColumns = ['is_admin', 'country'];
  ```
- **`$availableScopes`** Specifies which scopes are allowed to be applied via the `belongsTo` and `relationId` configurations. The default value is `null`, allowing any scope to be applied. For security reasons, it's recommended to explicitly restrict this array to only the scopes that should be allowed, e.g.:  
  ```php
  protected ?array $availableScopes = ['byTeam', 'published'];
  ```
- **`$availableIncludes`** Specifies which relationships are allowed to be included via the `include` QueryString parameter. The default value is `null`, allowing any relationship to be included. For security and performance reasons, it's recommended to explicitly restrict this array to only the relationships that should be loadable, e.g.:  
  ```php
  protected ?array $availableIncludes = ['comments', 'author', 'tags'];
  ```
- **`$searchConfiguration`** Es un array de datos de configuración de la búsqueda en los listados que está pensado para personalizar de manera fina las búsquedas con múltiples parámetros. Esta propiedad en el `ListService` almacena inicialmente la configuración predeterminada y existe un método `setSearchConfiguration()` que permite enviarle una configuración personalizada para un listado en particular, que hace un merge de los datos predeterminados y los nuevos datos pasados al método.
- **`$maxFilters`** Sets the maximum number of filters allowed per query. The default value is `null`, meaning no limit is enforced. When set to a positive integer, any number of active filters exceeding this value will be automatically capped to the configured maximum without raising an error.

### Configuraciones en el QueryString de la URL de la operación del listado


Cuando trabajamos con el trait `ResourceListable` se recuperan las configuraciones de los listados a través de variables del QueryString (enviadas mediante la URL). Esas configuraciones se envían directamente al ListService para personalizar su comportamiento de una manera automática. De este modo resulta sencillo introducir numerosas configuraciones para personalizar los listados, que pueden ir cambiando en cada solicitud de listado al recurso.

Un ejemplo de consulta de listado con variables enviadas por QueryString:

```
https://example.com/api/users?sortField=email&sortDirection=desc&per_page=25&keyword=miss&filters[0][name]=is_admin&filters[0][active]=true&filters[0][value]=true
```

#### Configuración "keyword"

Permite enviar una keyword para buscar en el modelo por esa keyword.

```
example.com/users?keyword=paul
```

Esta configuración en realidad no realiza ninguna búsqueda por si sola. La tienes que activar en el `ListService`, sobreescribiendo el método `createQuery()`:

```php
protected function applyKeywordFilter(?string $keyword): void 
{
    if (!empty($keyword)) {
        $keyword = '%' . $keyword . '%';
        return $query->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword);
    }
}
```

Una recomendación es delegar la búsqueda al modelo por medio de un scope. En este caso siguiente se usa el scope `similar()`, que tendrías que impementar con la lógica de tu consulta en el modelo correspondiente.


```php
protected function applyKeywordFilter(?string $keyword): void 
{
    $this->query->similar($keyword);
}
```

Por supuesto, necesitarás implementar el scope en el correspondiente modelo, indicando la lógica para esta consulta. Aquí puedes ver una implementación del scope similar que podrías usar en tu modelo. Consulta la documentación de Laravel para encontrar más información sobre la creación de scopes.

```php
public function scopeSimilar($query, $keyword)
{
    if (empty($keyword)) {
        return $query;
    }

    $keyword = '%' . $keyword . '%';
    return $query->where(function ($q) use ($keyword) {
        $q->where('title', 'like', $keyword)
            ->orWhere('status', 'like', $keyword);
    });
}
```

#### Configuración "sortField" y "sortDirection"

Estas dos configuraciones en conjunto permiten definir el órden de los resultados de búsqueda.

```
example.com/users?sortField=name&sortDirection=desc
```

Puedes indicar cualquier campo del modelo para conseguir que el orden deseado en el listado.

Los valores de `sortDirection` posibles son "`asc`" y "`desc`", para orden ascendente y descendente.

#### Configuración "per_page"

Permite especificar un tamaño de página distinto en el listado del recurso.

```
example.com/users?per_page=25
```

Solo se tendrá en cuenta si el recurso se entrega paginado. Si no se desea paginar se puede asignar `false` a la variable boleana `$paginated` de `ListService`.

```php
protected bool $paginated = true;
```

Si se ha configurado paginación, de manera predeterminada se toma tamaño de página 10.

#### Configuración "filters"

La configuración "`filters`" permite activar cualquier número de filtros mediante un array. Para cada uno de esos filtros se espera recibir los siguientes datos:

- `active`: indica si el filtro se debe aplicar o no. Si no se recibe el valor active en el filtro no se procesará.
- `name`: columna para el filtrado
- `value`: valor que se busca en esa columna

Para que el filtro se procese, además de tener active a true, es necesario que la columna esté listada en el array `$availableFilterColumns` del `ListService`, o que esa propiedad sea `null`, en cuyo caso no estará restringiendo las columnas posibles para el listado.


> Para garantizar una mayor privacidad y seguridad es muy recomendable realizar la configuración del array `$availableFilterColumns` del `ListService` para evitar que el usuario pueda activar más filtros de los deseados.

Por ejemplo, si el array de filtros enviado por QueryString es como este:

```json
[
  {
    "name": "is_admin",
    "active": "true",
    "value": "true"
  },
  {
    "name": "country",
    "active": "true",
    "value": "Spain"
  },
  {
    "name": "continent",
    "active": "false",
    "value": "Asia"
  }
]
```

Estaría filtrando elementos que tienen la columna `is_admin` con el valor `true` y que tienen la columna `country` con el valor `Spain`. No estaría filtrando por la columna `continent` porque el valor de `active` es `false`.

#### Implementación de filtros personalizados

El filtrado por nombres de columnas de la base de datos puede ser bastante cómodo pero insuficiente para muchos casos. Por ello este package está abierto a la implementación de filtros personalizados definidos por el desarrollador.

Para ello necesitamos realizar los siguientes pasos:

- Crear una clase con el filtro personalizado
- Indicar los filtros personalizados en el método customFilters() del ListService
- Enviar por querystring los datos necesarios para activar o configurar el filtro.

Los filtros personalizados ofrecen toda la potencia de Eloquent y permiten realizar consultas tan complejas como sea necesario en los datos de la aplicación. Por tanto, no hay ninguna restricción a la hora de usar Eloquent para acceder a cualquier columna de un modelo o a cualquier dato relacionado que sea necesario.

##### Crear una clase del filtro

Primero es necesario implementar una clase para definir el comportamiento del filtro personalizado. Esta clase debe de extender CustomFilter.

```php
use EscuelaIT\APIKit\CustomFilter;

class EuropeFilter extends CustomFilter
{
    // ...
}
```

La clase debe definir una propiedad `$filterName` con el nombre que queramos asignar a este filtro.


Además, es necesario definir un método `apply()` en el que recibimos una instancia de `\Illuminate\Database\Eloquent\Builder` mediante podremos realizar la configuración de la consulta mediante Eloquent.

```php
class EuropeFilter extends CustomFilter
{
    protected $filterName = 'europe';

    public function apply(Builder $query): void
    {
        $query->where(function (Builder $subQuery) {
            $subQuery
                ->where('country', 'Spain')
                ->orWhere('country', 'France');
        });
    }
}
```

El filtro anterior acotaría la búsqueda a los elementos cuyo país es `Spain` o `France`.


Es posible que necesites el valor del filtro enviado por QueryString. Para ello puedes usar el método `getFilterValue()`, como se puede ver en el siguiente filtro personalizado:



```php
class TitleContainsFilter extends CustomFilter
{
    protected $filterName = 'title_contains';
    
    public function apply(Builder $query): void
    {
        $value = (string) $this->getFilterValue();
        if ($value !== '') {
            $query->where('title', 'like', '%' . $value . '%');
        }
    }
}
```

El anterior filtro permite buscar por parecidos, elementos que tienen una columna 'title' que contiene el valor pasado en el filtro.


Otros métodos, aparte de getFilterValue(), que ofrece CustomFilter y que podemos usar en nuestro filtro personalizado son:


- getFilterName(): Devuelve el nombre del filtro
- getFilterData(): Devuelve los datos completos del filtro enviados por QueryString
- isFilterActive(): Indica si el filtro está activo


##### Añadir la instancia del filtro a customFilters


En el objeto ListService que usamos para configurar los listados debemos definir qué filtros personalizados se deben aplicar.


Para ello se debe sobreescribir el método customFilters() del ListService, devolviendo un array con las instancias de los filtros personalizados que deseamos aplicar.


```php
protected function customFilters(): array
{
    return [new TitleContainsFilter()];
}
```

##### Enviar por QueryString los datos del filtro

Entre las variables enviadas por querystring necesitamos definir el filtro, con sus correspondientes datos.

```json
[
  {
    "name": "title_contains",
    "active": true,
    "value": "foo"
  }
]
```

- La propiedad name corresponde con el nombre del filtro personalizado que hayamos indicado en la clase del filtro.
- La propiedad active debe estar a true para que el filtro se aplique. Es decir, no se invocará el método apply() de la clase del fitro personalizado si no se recibe la propiedad active con el valor true.
- La propiedad value es algún dato que se puede enviar al filtro para aplicarse.


#### Configuraciones belongsTo y relationId

Las configuraciones de filtrado están pensadas para que puedan alterarse mediante una entrada de usuario, de modo que en cada solicitud al listado del recurso la consulta pueda ser muy variable.

Sin embargo, hay ocasiones en las que queremos fijar de una manera independiente ciertos comportamientos del listado, posiblemente sin dejar al usuario que los modifique vía filtros. Por ejemplo, es posible que queramos mostrar un listado donde solamente haya facturas de un cliente y ese cliente no se quiere que se cambie vía filtros. Entonces podemos hace una configuración de filtrado vía configuraciones belongsTo y relationId.

Veamos el funcionamiento de esta propiedad con un ejemplo. Supongamos que estamos trabajando con un modelo de recurso User. Los usuarios pertenecen a un modelo Team mediante una relación BelongsToMany:

```php
public function teams(): BelongsToMany
{
    return $this->belongsToMany(Team::class);
}
```

Entonces, en el modelo de User podemos tener un scope que permite filtrar usuarios por un identificador de team, así:

```php
public function scopeByTeam($query, $teamId) {
    return $query->whereHas('teams', function($query) use ($teamId) {
        $query->where('team_id', $teamId);
    });
}
```

Si queremos activar este filtrado vía scope enviaríamos las siguientes configuraciones vía query string:

- Variable belongsTo con el valor "byTeam"
- Variable relationId como un entero

Por ejemplo, la URL de consulta podría quedar así:

```
https://example.com/api/users?belongsTo=byTeam&relationId=2
```

Entonces Se activará un scope llamado byTeam en el modelo del recurso y se le pasará el valor 2 como dato, obteniendo únicamente los usuarios que pertenecen al team con id=2

##### Restricting Allowed Scopes with $availableScopes

Similar to `$availableFilterColumns`, you can restrict which scopes are allowed to be applied via the `belongsTo` configuration by setting the `$availableScopes` property.

By default, `$availableScopes` is `null`, which means any scope can be applied via QueryString. For security reasons, it's recommended to explicitly define which scopes are allowed:

```php
protected ?array $availableScopes = ['byTeam', 'published'];
```

With this configuration, only the `byTeam` and `published` scopes can be applied. Any attempt to apply a different scope via QueryString will be ignored.

> **For enhanced security, it's highly recommended to configure the `$availableScopes` array in `ListService`** to prevent users from enabling unintended scopes that might expose sensitive data or cause undesired filtering behavior.

#### "include" Configuration

The `"include"` configuration allows eager loading of related entities in listing results. This is useful for including associated data (like comments on posts, or payments for users) without requiring additional queries.

```
example.com/posts?include=comments,author
```

You can specify multiple relationships separated by commas. The data will be eager-loaded using Laravel's `with()` method.

##### Restricting Allowed Includes with $availableIncludes

Similar to `$availableFilterColumns` and `$availableScopes`, you can restrict which relationships are allowed to be included via the `include` parameter by setting the `$availableIncludes` property.

By default, `$availableIncludes` is `null`, which means any relationship can be included via QueryString. For security and performance reasons, it's recommended to explicitly define which relationships are allowed:

```php
protected ?array $availableIncludes = ['comments', 'author', 'tags'];
```

With this configuration, only the `comments`, `author`, and `tags` relationships can be included. Any attempt to include other relationships via QueryString will be ignored.

You can also set the available includes using the `setAvailableIncludes()` method:

```php
$service = (new ListService())
    ->setListModel(Post::class)
    ->setAvailableIncludes(['comments', 'author'])
    ->setSearchConfiguration($config);
```

**Example URL with allowed includes:**

```
https://example.com/api/posts?include=comments,author
```

This will return posts with their related comments and authors eager-loaded.

**Example with restricted includes:**

If `$availableIncludes` is set to `['comments']` but you request `?include=comments,author`, only the `comments` relationship will be included. The `author` relationship will be ignored because it's not in the allowed list.

> **For enhanced security and performance, it's highly recommended to configure the `$availableIncludes` array in `ListService`** to prevent users from eager-loading potentially expensive relationships that might impact application performance or expose sensitive data through related entities.

## Resource Actions

Aparte de las típicas operaciones del CRUD a menudo las aplicaciones requieren otros comportamientos que ayuden a completar operativas personalizadas del negocio. Por ejemplo, para una factura es posible que requieras una acción para construir un duplicado, para un presupuesto podrías necesitar una acción para generar una factura a partir de él.

Además, algunas de estas operativas a veces se necesitan hacer en lotes. Por ejemplo, marcar como pagadas una serie de facturas, o mandar una notificación a una serie de usuarios, y no deseas hacer esa misma acción de uno en uno.

Para ello existen las acciones de los recursos.

Para construir estas acciones necesitamos definir un par de componentes:

- Un controlador único para recibir de forma unificada todas las solicitudes de ejecución de una acción sobre un modelo.
- Un servicio que se encarga de definir cómo han de ejecutarse las acciones, indicando sobre qué modelo se ejecutan, cuáles son las acciones posibles, etc.
- La acción en sí que tiene el código con la lógica necesaria para ejecutarla. Cada acción se resuelve en su propia clase dedicada a implementarla.

### Controlador para las acciones

Necesitaremos un único controlador para ejecutar todas las acciones necesarias sobre una entidad. Ese controlador recibirá por POST los datos para procesar la acción como el tipo de acción, los identificadores de los elementos sobre los que se debe ejecutar y los datos adicionales que la acción necesite.

Para definir el controlador haremos una ruta como esta:

```php 
Route::post('/users/action', ActionUsersController::class);
```

### Trait ActionHandler

Para implementar de manera sencilla el controlador de acciones se proporciona el trait ActionHandler, que se debe usar en el controlador que recibe los datos de la acción a procesar. 

```php 
namespace App\Http\Controllers\User;

use EscuelaIT\APIKit\ActionHandler;

class ActionUsersController extends Controller
{
    use ActionHandler;
}
```

Dentro del controlador se ha de implementar el método que hayamos configurado en la ruta POST e invocar al método handleAction() que nos proporciona el trait ActionHandler enviando una instancia del ActionService que define la configuración del sistema de acciones.

```php 
public function __invoke()
{
    $userActionService = new UserActionService();
    return $this->handleAction($userActionService);
}
```

El método anterior __invoke es porque hemos elegido usar un controlador invokable, aunque esto no es un requisito para que funcione el sistema de acciones.

Por conveniencia siempre es útil hacer que se inyecte la instancia del ActionService en el método, delegando al contenedor de servicios de Laravel la instanciación del servicio correspondiente.

```php 
public function __invoke(UserActionService $userActionService)
{
    return $this->handleAction($userActionService);
}
```

El controlador espera recibir un payload como el siguiente:

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

- type: es el tipo de la acción que será ejecutada
- relatedIds: son los identificadores de los modelos sobre los que se ejecutará la acción
- data: es cualquier conjunto de datos que requiera la acción para poder procesarse

### ActionService

Para configurar el sistema de acciones para cada entidad usamos la clase ActionService que nos ofrece el package Laravel Api Kit.

Lo más habitual es que creemos nuestro propio servicio extendiendo la clase ActionService y realizando las configuraciones oportunas.

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
    ];
}
```

#### Propiedades de ActionService

Las propiedades que podemos configurar en el ActionService personalizado son las siguientes:

##### Propiedad $actionModel

Permite definir la clase del modelo que vamos a usar para procesar las acciones.

```php 
protected string $actionModel = User::class;
```

##### Propiedad $actionTypes

Es un array asociativo que define los tipos de acciones que se pueden realizar sobre un modelo. En este array las claves serán el nombre de la acción, que corresponde con el valor 'type' que recibe por post el controlador. El valor será la clase de la acción que se encargará de procesarla.

```php 
protected array $actionTypes = [
    'DeleteAction' => DeleteAction::class,
    'SetAdminUserAction' => SetAdminUserAction::class,
];
```

##### Propiedad $maxModelsPerAction

Permite decir cuál es el número máximo de elementos sobre los cuales se permite ejecutar una acción. 

El valor predeterminado de esta propiedad es 100, de modo que si se solicita ejecutar una acción sobre más de 100 elementos se producirá un error de validación, enviando un código de respuesta HTTP 422 con un mensaje.

```php
protected int $maxModelsPerAction = 50;
```

##### Propiedad $identifierField

Este es el campo de la tabla del modelo en el que se buscarán coincidencias para saber qué elementos deben procesarse.

El valor predeterminados es 'id'. De modo que se espera que al solicitar el procesamiento de acciones se envíen los identificadores de los elementos en el campo 'relatedIds' enviado por POST al controlador. 

Por ejemplo, podría asignarse 'slug' si lo que vamos a recibir en el campo 'relatedIds' son los slug de los elementos sobre los que queremos procesar las acciones.

```php
protected string $identifierField = 'slug';
```

#### Métodos de ActionService

Aunque en principio no sea frecuente si se desea personalizar aún más el comportamiento del servicio, aparte de las propiedades anteriores, se pueden sobreescribir métodos que permitan realizar acciones arbitrarias.

- createQuery(): inicializa la consulta
- queryModels(): filtra los modelos con los identificadores recibidos
- getModels(): devuelve la colección de los modelos resultantes de la consulta

Por ejemplo, esto produciría que el ActionService se restringiera a ejecutar acciones en usuarios que no sean administradores.

```php
protected function createQuery()
{
    return User::where('is_admin', false);
}
```

### Acciones

Para poder definir los comportamientos particulares para cada tipo de acción usamos clases de acciones. Las acciones deben extender la clase abstracta CrudAction.

El trait ActionHandler se encargará de llamar a la acción correspondiente, deducida a partir del valor "type" y según la clase correspondiente en el array de $actionTypes del ActionService.

Al construir la acción se pasarán tres datos:

- El usuario que está autenticado en esta solicitud, o null si no existe. Este usuario se guardará como propiedad $user.
- Los identificadores de los modelos, que se usarán para consultar con la base de datos y en base a la consulta se construirá la colección de modelos relacionados, que tendremos disponible en la propiedad $models.
- Los datos enviados en la request al controlador en el campo 'data' del payload. Esos datos estarán disponibles en la propiedad $data.

Con esos datos se podrá definir una acción tal como se puede ver en el ejemplo siguiente:

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

El método validationRules() permite definir las reglas de validación del juego de datos contenido en el campo 'data' del payload.

El método handle() se encarga de procesar la acción. Lo normal en este método es iterar por cada uno de los modelos que tenemos en la propiedad $models. Para cada modelo podemos verificar si el usuario tiene permisos para realizar las acciones y luego realizar la acción en caso que sea posible.

Por último la acción tendrá que devolver una respuesta enviando una instancia de la clase ActionResult. Para facilitar la creación de la respuesta en el formato adecuado la clase CrudAction proporciona un método de utilidad:

- createActionResultSuccess($message, $data): Permite generar una instancia de ActionResult para una respuesta positiva, enviando un mensaje de respuesta y un array de datos que se deba informar al cliente que solicitó la acción.

El flujo de procesamiento de las acciones ya ofrece todos los mecanismos para verificar los datos de la acción, tanto el formato de la acción en sí como el formato de los datos particulares que requiera cada tipo de acción específica, por tanto, lo normal es que si se llega a ejecutar el método handle se pueda procesar la acción. No obstante, si por algún motivo es necesario realizar alguna comprobación adicional y enviar algún error extra, se puede realizar en el método handle y usar otro método de utilidad que permite producir un mensaje de respuesta de error.

- createActionResultError($message, $errors): Permite generar una instancia de ActionResult para una respuesta negativa, enviando un mensaje y un array asociativo de errores.

El método handle() de la acción tendrá que retornar la instancia de ActionResult, creada por el método createActionResultSuccess() o el método createActionResultError(). No obstante, si eses métodos no son suficientes, es posible también usar directamente los métodos estáticos success() o error() de la clase ActionResult.

#### Formatos de respuesta de las acciones

Una vez procesada la acción se enviará una respuesta al cliente. El formato es consistente con respecto a otros formatos de respuesta de otros tipos de solicitudes del API.

En caso de éxito se enviará un JSON al cliente con este formato:

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

En caso de un error de validación, el formato de respuesta sería el siguiente:

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

### Clase ActionResult

Esta clase se usa internamente para conseguir crear respuestas homogéneas de las acciones. Esta preparada para crear tanto mensajes positivos como negativos.

Para crear las instancias los únicos métodos disponibles son los siguientes:

#### Método success()

```php
static function success(string $message = 'Ok', array $data = []): ActionResult
```

Devuelve una instancia de ActionResult configurada como respuesta de éxito.

```php
$actionSuccessInstance = ActionResult::success('Action completed successfully', [
    'total_received' => 5, 
    'successfully_processed' => 4,
]);
```

#### Método error()

```php
static function error(array $errors = [], string $message = 'Error'): ActionResult
```

Devuelve una instancia de ActionResult configurada como respuesta de error. 

Como $errors se espera recibir un array asociativo de errores donde las claves son los campos donde se ha producido el error y los valores los errores en particular encontrados.

```php
$actionErrorInstance = ActionResult::error([
    'count' => ['The field count must be a number']
], $message);
```
