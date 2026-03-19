# SpotlightDependency — Multi-paso con Pills

Las dependencias permiten flujos multi-paso: seleccionar un item puede requerir inputs previos antes de ejecutar la acción final. Cada paso completado aparece como una "pill" en el input.

## Tipos de dependencia

### `::search(placeholder, searchUrl, method?)`

Búsqueda remota con selección de resultado:

```php
SpotlightDependency::search(
    placeholder: 'Seleccionar usuario',
    searchUrl: 'users.spotlight',   // ruta nombrada o URL
    method: 'GET'                   // opcional, default: GET
)
```

### `::input(placeholder, validation?)`

Texto libre:

```php
SpotlightDependency::input(
    placeholder: 'Motivo de la acción',
    validation: 'min:3'  // opcional, validación básica
)
```

### `::select(placeholder, options)`

Lista fija de opciones:

```php
SpotlightDependency::select(
    placeholder: 'Seleccionar rol',
    options: [
        'admin'  => 'Administrador',
        'editor' => 'Editor',
        'viewer' => 'Viewer',
    ]
)
```

## Ejemplo completo — flujo de 2 pasos

```php
SpotlightItem::make('Asignar rol a usuario')
    ->icon('shield')
    ->group('Acciones')
    ->action('assignRole')  // se ejecuta con los valores resueltos
    ->dependency(
        SpotlightDependency::search('Seleccionar usuario', 'users.spotlight')
    )
    ->dependency(
        SpotlightDependency::select('Seleccionar rol', [
            'admin'  => 'Administrador',
            'editor' => 'Editor',
            'viewer' => 'Viewer',
        ])
    );
```

## Flujo visual

```
Paso 0: Usuario busca "Asignar rol"
        ┌──────────────────────────────────────┐
        │  🔍  Asignar rol a usuario    ›       │
        └──────────────────────────────────────┘
        ↓ selecciona el item

Paso 1: SpotlightDependency::search
        ┌───────────────────────────────────────────────────┐
        │  [Asignar rol a usuario ×]  Seleccionar usuario…  │
        ├───────────────────────────────────────────────────┤
        │  👤 Juan García                                    │
        │  👤 Ana López                                      │
        └───────────────────────────────────────────────────┘
        ↓ selecciona Juan García

Paso 2: SpotlightDependency::select
        ┌──────────────────────────────────────────────────────────┐
        │  [Asignar rol ×] [Juan García ×]  Seleccionar rol        │
        ├──────────────────────────────────────────────────────────┤
        │  🛡  Administrador                                        │
        │  ✏  Editor                                               │
        │  👁  Viewer                                               │
        └──────────────────────────────────────────────────────────┘
        ↓ selecciona Editor

Ejecuta: $wire.call('assignRole', ['juan-garcia', 'editor'])
```

## Recibir valores en el método Livewire

Los valores resueltos se pasan como argumentos adicionales al método action:

```php
// En el componente Livewire
public function assignRole(string $userId, string $role): void
{
    // $userId = ID del usuario seleccionado
    // $role = 'admin' | 'editor' | 'viewer'
    User::find($userId)->assignRole($role);
    $this->toast()->success("Rol asignado")->send();
}
```

## Backspace para deshacer

El usuario puede presionar `Backspace` con el input vacío para eliminar la última pill y volver al paso anterior.

## Endpoint para dependencia de tipo search

El endpoint debe devolver un array de items compatibles con `SpotlightResult::fromArray()`:

```json
[
    {
        "id": "user-1",
        "name": "Juan García",
        "description": "juan@empresa.com",
        "icon": "user",
        "actionType": "action",
        "actionTarget": "selectUser",
        "actionParams": ["1"]
    }
]
```

```php
// routes/web.php o api.php
Route::get('/spotlight/users', function (Request $request) {
    return User::search($request->query)->limit(10)->get()
        ->map(fn($u) => [
            'id'          => (string) $u->id,
            'name'        => $u->name,
            'description' => $u->email,
            'icon'        => 'user',
        ]);
});
```
