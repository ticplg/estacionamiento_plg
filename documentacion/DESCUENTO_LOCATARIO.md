# Proyecto "Descuento Locatario": documento de traspaso

> Documento para Claude Code. Lo generó una sesión en `app_plg_laravel` el 2026-10-22. El trabajo
> se empezó por error en ese repo y se movió a **este proyecto (`estacionamiento_plg`)**, que es
> donde vive la integración con Skidata. En `app_plg_laravel` no quedó nada: se borró el código y la rama.
>
> **El código de abajo es Laravel 5.5 / AsgardCMS. Hay que adaptarlo** a la estructura de este
> proyecto (Laravel 10, `app/Models`, `routes/api.php`, Sanctum si conviene, tabla `configuraciones`).
> Es una referencia, no algo para pegar tal cual.

## 1. Qué es

Una app aparte para **locatarios** (empleados de los locales del shopping). El locatario escanea el QR
del ticket de estacionamiento de un cliente y le aplica un descuento en **Skidata**. Es **independiente
de la app general**: tiene sus propios usuarios, su propio login y su propio token, y no usa `users`.

Los requisitos salen del Excel
`C:\Users\hsamaniego\OneDrive - GARDA, S.A\Documentos\Docs\APP LOCATARIOS\Tablas_registro_desc_locatarios(Hoja1).xlsx`
y la referencia de Skidata está en `C:\Users\hsamaniego\OneDrive - GARDA, S.A\Escritorio\Skidata_monto_y_descuentos.pdf`.

## 2. Flujo de la app

1. El locatario hace login (usuario y contraseña) y recibe un token.
2. Escanea el QR del ticket. Si el QR trae una URL, el identificador es lo que viene después de la última `/` (23 caracteres).
3. Se consulta el saldo del ticket con `GetRateInfo` y se guarda en `monto_ticket`.
4. Se aplica el descuento con `InsertElectronicValidation`, usando `validationId = APT.VAL.1901198.{codigo_descuento}`, y se guarda el HTTP status en `response_status` (200 = aplicado, 500 = SOAP Fault).
5. Se vuelve a consultar el saldo con `GetRateInfo` y se guarda en `monto_final`.
6. Fin.

## 3. Entregables

| # | Entregable | Estado |
|---|---|---|
| 0 | Migraciones `usuarios_locatarios` y `registro_descuento_locatarios` | Borrador hecho (sección 5) |
| 1 | API de login de locatarios (auth separada, token propio) | Borrador hecho (sección 6) |
| 2 | API que lee el QR, consulta el saldo y aplica el descuento en Skidata | Pendiente |
| 3 | Guardar cada descuento en `registro_descuento_locatarios` | Pendiente (parte del 2) |
| 4 | Pantalla admin: CRUD de `usuarios_locatarios` | Pendiente |
| 5 | Pantalla admin: listado de descuentos aplicados | Pendiente |
| 6 | Sección de menú "Descuento Locatario" con las pantallas 4 y 5 | Pendiente |

## 4. Decisiones ya tomadas con el usuario

- **Auth independiente:** tiene su propia tabla `usuarios_locatarios`. No tiene relación con `users` ni con la app general.
- **Token nuevo en cada login:** cada login invalida la sesión anterior de ese usuario ("cada usuario independiente"). El logout borra el token.
- `codigo_descuento` es el `{codigo}` del `validationId` de Skidata (`APT.VAL.1901198.{codigo}`).
- Se agregaron dos columnas que no estaban en el Excel:
  - `monto_final`: saldo después del descuento (paso 5).
  - `response_body`: respuesta de Skidata, para auditoría.
- `nombre`, `apellido` y `nro_factura` son nullable, porque en el ejemplo del Excel venían vacíos.
- En `app_plg_laravel` el local era un FK a `locales__locals`. **Aquí hay que ver** si existe una tabla de locales. Si no existe, usar un campo `local` (string), como en el Excel.
- Formato de respuesta usado en el borrador: `{"status": "200", "message": "..."}`. Conviene seguir el formato que ya usen las APIs de este proyecto.

## 5. Migraciones (borrador)

```php
Schema::create('usuarios_locatarios', function (Blueprint $table) {
    $table->increments('id');
    $table->string('usuario')->unique();          // email
    $table->string('password');                   // hash
    $table->integer('local_id')->unsigned()->index();   // adaptar: ¿FK a locales o string 'local'?
    // Código de validación de Skidata: APT.VAL.1901198.{codigo_descuento}
    $table->string('codigo_descuento', 20);
    $table->char('estado', 1)->default('A');
    $table->string('api_token', 80)->nullable()->unique(); // si se usa Sanctum, no hace falta
    $table->timestamps();
});

Schema::create('registro_descuento_locatarios', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('usuario_id')->unsigned()->index();
    $table->foreign('usuario_id')->references('id')->on('usuarios_locatarios');
    $table->dateTime('fecha_registro');
    $table->string('identificador', 50)->index();   // código del ticket (23)
    $table->string('nombre')->nullable();
    $table->string('apellido')->nullable();
    $table->string('nro_factura')->nullable();
    $table->bigInteger('monto_ticket')->nullable();     // saldo antes (GetRateInfo)
    $table->bigInteger('monto_descuento')->nullable();
    $table->bigInteger('monto_final')->nullable();      // saldo después (GetRateInfo)
    $table->smallInteger('response_status')->nullable();// HTTP status InsertElectronicValidation
    $table->text('response_body')->nullable();
    $table->timestamps();
});
```

En Laravel 10, usar `$table->id()` / `foreignId()->constrained()` según el estilo de este proyecto.

## 6. Modelos, middleware y login (borrador)

### Modelo `UsuarioLocatario`
```php
class UsuarioLocatario extends Model
{
    const ESTADO_ACTIVO = 'A';
    protected $table = 'usuarios_locatarios';
    protected $fillable = ['usuario', 'password', 'local_id', 'codigo_descuento', 'estado'];
    protected $hidden = ['password', 'api_token'];

    public function setPasswordAttribute($value) { $this->attributes['password'] = Hash::make($value); }
    // En Laravel 10 se puede usar: protected $casts = ['password' => 'hashed'];

    public function descuentos() { return $this->hasMany(RegistroDescuentoLocatario::class, 'usuario_id'); }
    public function estaActivo() { return $this->estado == self::ESTADO_ACTIVO; }
}
```

### Modelo `RegistroDescuentoLocatario`
```php
class RegistroDescuentoLocatario extends Model
{
    protected $table = 'registro_descuento_locatarios';
    protected $fillable = ['usuario_id', 'fecha_registro', 'identificador', 'nombre', 'apellido',
        'nro_factura', 'monto_ticket', 'monto_descuento', 'monto_final', 'response_status', 'response_body'];
    protected $casts = ['fecha_registro' => 'datetime'];
    protected $hidden = ['response_body'];

    public function usuario() { return $this->belongsTo(UsuarioLocatario::class, 'usuario_id'); }
}
```

### Middleware `VerifyLocatarioToken` (alias `locatario_token`)
Lee el header `token`, busca `UsuarioLocatario::where('api_token', $token)` y valida `estaActivo()`. Si
el token es válido, deja el usuario en `$request->attributes->set('usuario_locatario', $usuario)`. Si no,
responde `403` con uno de estos mensajes: "Token de usuario no encontrado", "Token no válido" o "Usuario inactivo".

> Alternativa en Laravel 10: hacer que `UsuarioLocatario` extienda `Authenticatable`, use `HasApiTokens`
> (Sanctum) y tenga un guard propio en `config/auth.php`. En ese caso, en cada login se borran los tokens
> anteriores (`$usuario->tokens()->delete()`) antes de crear uno nuevo.

### Endpoints de auth
| Método | Ruta | Qué hace |
|---|---|---|
| POST | `/api/locatarios/login` | Recibe `usuario` y `password`. Valida `Hash::check` y estado `A`. **Genera un token nuevo** (`Str::random(60)`, único). Devuelve `token` y `usuario {id, usuario, codigo_descuento, local}`. |
| GET | `/api/locatarios/perfil` | Requiere token. Devuelve los datos del usuario. |
| POST | `/api/locatarios/logout` | Requiere token. Pone `api_token = null`. |

Mensajes: "El usuario es obligatorio.", "La contraseña es obligatoria.", "Usuario o contraseña incorrectos.",
"Usuario inactivo. Contactar con el administrador.", "Has ingresado correctamente.", "Sesión cerrada."

## 7. Skidata (resumen del manual)

- Es un servicio **SOAP 1.1** (Ticket Management v4). Todo se manda por `POST {url}` con
  `Content-Type: text/xml; charset=utf-8` y `Authorization: Basic base64(usuario:password)`.
- Los parámetros están en la tabla **`configuraciones`** de este proyecto (columna `nombre_parametro`):
  `url`, `usuario`, `password`, `facilityId`, `externalDeviceId`, `parkingDeviceId`.
- El ticket se manda como `GenericIdentification`, con `Identificator` (23 caracteres) y `Type` = `PARK`.
- **`GetRateInfo`** (saldo): recibe facilityId, ticketId, externalDeviceId y parkingDeviceId. El monto
  viene en `//ns3:Price/ns2:Amount`, en PYG y sin decimales, y ya incluye los descuentos aplicados.
- **`InsertElectronicValidation`** (descuento): recibe validationId, ticketId, externalDeviceId y
  parkingDeviceId, **sin facilityId**. Solo importa el HTTP status: 200 = aplicado, 500 = SOAP Fault.
- Namespaces para DOMXPath:
  - `ns2` = `http://www.skidata.com/contractor/dtaservice/v7/common`
  - `ns3` = `http://www.skidata.com/interfaces/parking/ticketManagement/v4/data`
- **Reutilizar lo que ya existe:** `app/Http/Controllers/ApiEstacionamiento.php` (`rateInfo()`,
  `obtener_monto()`, `aplicar_descuento_ticket()`), y los comandos `app:aplicar-exoneracion-ticket` y
  `app:tarifa-unica`. También está el modelo `RegistroDescuentoAplicado`, que guarda request y response para auditoría.
- Códigos de validación que ya se usan:

  | Código | Uso |
  |---|---|
  | 32 | ATC |
  | 33 | Proveedores |
  | 35 | Zona Fit (también es el default de cine) |
  | 39 | Eventos escalonados |
  | 40 | Exoneración de eventos |
  | 5001 | Hotel / tarifa única |

## 8. Dudas abiertas (preguntar al usuario)

1. **¿Qué código de validación usan los locatarios?** El ejemplo del Excel usa 35, que ya es el de **Zona Fit**. ¿Cada local tiene su propio código en Skidata?
2. ¿Qué `externalDeviceId` / `parkingDeviceId` se usan para la app de locatarios (los configurados, 458/81 o 456/82)?
3. El usuario habló de "aplicar el pago". El manual menciona `BookPayment` (`/api/pagar/estacionamiento`) sin detallarlo. Para el descuento alcanza con `InsertElectronicValidation`. ¿También hay que registrar un pago?
4. ¿`nombre`, `apellido` y `nro_factura` los carga el locatario o son opcionales?
5. ¿Se registran también los intentos fallidos (status ≠ 200)? En el ejemplo del Excel hay uno con 500.
6. ¿Se bloquea un segundo descuento sobre el mismo ticket? ¿Y si el ticket tiene saldo 0 o ya está pagado?
7. ¿Qué es `monto_descuento`: lo que se descontó (`monto_ticket - monto_final`) o un valor configurado?
8. ¿Dónde van las pantallas admin (4, 5 y 6) en este proyecto? Hay que ver qué panel o layout usa.

## 9. Próximos pasos sugeridos

1. Revisar la estructura de este proyecto: modelos, `routes/api.php`, `config/auth.php`, la tabla `configuraciones`, `ApiEstacionamiento.php` y el panel admin.
2. Crear una rama nueva desde la rama de trabajo que corresponda (preguntar al usuario).
3. Adaptar las migraciones y los modelos, y hacer el login (sección 6).
4. Hacer la API del QR y el descuento, reutilizando las funciones de Skidata que ya existen.
5. Hacer las pantallas admin y el menú.
