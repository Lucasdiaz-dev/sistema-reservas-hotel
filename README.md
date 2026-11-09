# Sistema de Reservas — Hotel Litoral

Sistema de reservas hoteleras para el **Hotel Litoral** (Santa Fe), desarrollado como trabajo práctico de la materia **PP1**.

Permite a visitantes buscar habitaciones disponibles, a clientes reservar y gestionar sus reservas, y a administradores operar habitaciones, reservas y usuarios.

- **Repositorio:** https://github.com/Lucasdiaz-dev/sistema-reservas-hotel
- **Mockup de interfaz:** https://lucasdiaz-dev.github.io/pp1-hotel-mockup/
- **Documentación / seguimiento:** [Linear PP1](https://linear.app/thehubforlucasdev/team/PP1/overview)
- **Convenciones de código:** ver [`CONVENCIONES.md`](CONVENCIONES.md)

---

## Equipo

| Integrante | Áreas principales |
|---|---|
| **Lucas** | Búsqueda y detalle de habitaciones, alta de reservas, cancelación del cliente, estados de reserva, panel de reservas del admin, integración de la interfaz |
| **Matías** | Administración de habitaciones e imágenes |
| **Luciano** | Perfil, Mis reservas, administración de usuarios |

---

## Stack

| Componente | Versión / detalle |
|---|---|
| PHP | **≥ 8.4** (el `composer.json` declara `>=8.1`, pero el `composer.lock` fija paquetes que requieren PHP 8.4+: Doctrine Bundle 3.3, Doctrine Collections 3.1, Symfony UX Turbo 3.6, etc.) |
| Symfony | `6.4.*` |
| Doctrine ORM | `^3.7` + Doctrine Migrations Bundle + Fixtures Bundle (dev) |
| Twig | `^2.12\|^3.0` + Twig Extra Bundle |
| Seguridad | Symfony Security Bundle (form login + CSRF) |
| Front | CSS propio en `public/css/styles.css` (basado en el mockup); Asset Mapper / Stimulus / UX Turbo del skeleton |
| Base de datos | **MySQL 8** vía `DATABASE_URL` en `.env.local` |

---

## Requisitos

- PHP **8.4 o superior** (extensiones habituales de Symfony: `ctype`, `iconv`, etc.)
- Composer
- MySQL 8 (recomendado; ajustar `serverVersion` si usás MariaDB o MySQL 5.7)
- [Symfony CLI](https://symfony.com/download) (opcional, para `symfony serve`)

---

## Instalación y ejecución

```bash
git clone https://github.com/Lucasdiaz-dev/sistema-reservas-hotel.git
cd sistema-reservas-hotel
composer install
```

Creá `.env.local` (no se versiona) con tu base:

```env
DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/sistema_reservas_hotel?serverVersion=8.0&charset=utf8mb4"
```

Reemplazá `USER` y `PASSWORD` por tu usuario de MySQL.

Después:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

Las migraciones viven en `src/Migrations/` (namespace `App\Migrations`), no en la carpeta `migrations/` del skeleton.

Levantá el servidor:

```bash
symfony serve
```

Alternativa sin Symfony CLI:

```bash
php -S 127.0.0.1:8000 -t public
```

Abrí la URL que indique el servidor (típicamente `https://127.0.0.1:8000` con Symfony CLI o `http://127.0.0.1:8000` con `php -S`).

Para resetear los datos de demo:

```bash
php bin/console doctrine:fixtures:load
```

### Instalación en Windows (Laragon)

1. Abrí la **terminal de Laragon** y elegí una versión de PHP **8.4+** (Menú → PHP).
2. Cloná el repo dentro de la carpeta `www` de Laragon (por ejemplo `C:\laragon\www\sistema-reservas-hotel`).
3. En esa carpeta: `composer install`.
4. Creá `.env.local` con MySQL de Laragon (usuario `root` sin contraseña por defecto):

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/sistema_reservas_hotel?serverVersion=8.0&charset=utf8mb4"
```

Si Laragon usa MariaDB o MySQL 5.7, ajustá `serverVersion` (por ejemplo `10.4.32-MariaDB` o `5.7`).

5. Creá la base, migrá y cargá fixtures:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

6. Servidor embebido:

```bash
php -S 127.0.0.1:8000 -t public
```

Abrí `http://127.0.0.1:8000`.

### Subida de imágenes (`public/.user.ini`)

El archivo `public/.user.ini` sube los límites de PHP para la carga de fotos de habitación:

```ini
upload_max_filesize = 6M
post_max_size = 12M
```

La validación de la aplicación acepta JPG, PNG o WebP de hasta **5 MB**. Con el servidor embebido (`php -S`) o PHP-FPM, `.user.ini` suele aplicarse; si usás otra configuración, revisá esos valores en el `php.ini` activo.

---

## Datos de demo

Definidos en `src/DataFixtures/AppFixtures.php`. La contraseña común de los usuarios demo está en esa fixture (constante `PASSWORD`); no se documenta acá.

### Usuarios

| Email | Nombre | Rol efectivo |
|---|---|---|
| `admin@hotel.com` | Admin Hotel | `ROLE_ADMIN` (+ `ROLE_USER` implícito) |
| `juan@mail.com` | Juan Pérez | `ROLE_USER` |
| `maria@mail.com` | María Gómez | `ROLE_USER` |
| `carlos@mail.com` | Carlos Rodríguez | `ROLE_USER` |

### Habitaciones

- Simples: 101–104 (104 inactiva / fuera de servicio)
- Dobles: 201–205
- Suites: 301–303
- 3 imágenes por habitación (fotos versionadas en `public/img/habitaciones/`; `ImagenHabitacion.ruta` guarda solo el nombre del archivo)

### Reservas

8 reservas de ejemplo cubriendo los cuatro estados: `PENDIENTE`, `CONFIRMADA`, `CANCELACION_PENDIENTE`, `CANCELADA`.

---

## Roles y funcionalidades

### Visitante (sin sesión)

- Ver inicio / búsqueda de habitaciones (`/`) y filtrar por fechas, huéspedes y tipo
- Ver detalle de habitación (`/habitacion/{id}`)
- Registrarse (`/register`) e iniciar sesión (`/login`)
- Al tocar **Reservar** sin sesión: pantalla de pasos (iniciar sesión o registrarse → confirmar → revisar) y, tras autenticarse, vuelta a confirmar la reserva con las mismas fechas y huéspedes

### Cliente (`ROLE_USER`)

- Tras el registro: auto-login y pantalla de bienvenida en `/bienvenida` (si no venía del flujo de reserva)
- Mensajes de login en español; si intentaba abrir una página protegida, vuelve ahí después de entrar
- Confirmar y registrar una reserva (queda en **Pendiente**) con pantalla de resultado
- **Mis reservas** (`/reservas`): listado y cancelación hasta **24 h antes del check-in** (ingreso a las 14:00), con pantalla de resultado en `/reservas/{id}/cancelacion`
- **Mi perfil** (`/perfil`): editar datos y contraseña (solo guardar; sin botón cancelar)

### Administrador (`ROLE_ADMIN`)

Acceso a `/admin/*`. Tras el login, si no había otra URL pendiente interna (o venía del flujo de reserva / inicio), redirige a `/admin/reservas`.

- **Consultar reservas:** filtro por período y por estado; confirmar, rechazar, cancelar una confirmada; confirmar o revertir una cancelación pendiente
- **Gestionar habitaciones:** listado, alta y edición; subida de imagen (JPG/PNG/WebP, máx. 5 MB) a `public/img/habitaciones/`; selector con «Aceptar»; tras un alta exitosa, pantalla «Habitación creada» en `/admin/habitaciones/{id}/creada`
- **Administrar usuarios:** buscador por nombre, apellido o email (`?q=`); edición en modal centrado (datos y rol), con protección para no quitarte tu propio rol de admin

### Notificaciones

Los mensajes flash del sitio se muestran como toasts flotantes (fondo negro semitransparente, texto blanco, desaparecen a los 3 s). Las pantallas de resultado propias (bienvenida, pasos para reservar, reserva pendiente, resultado de cancelación, habitación creada) no dependen de flashes: usan sesión u otra clave cuando hace falta.

---

## Rutas principales

| Nombre | Método | Path | Quién |
|---|---|---|---|
| `app_busqueda` | GET | `/` | Todos |
| `app_habitacion_detalle` | GET | `/habitacion/{id}` | Todos |
| `app_reserva_confirmar` | GET | `/habitacion/{id}/reservar` | Todos (sin sesión: pasos; con sesión: confirmar) |
| `app_reserva_registrar` | POST | `/habitacion/{id}/reservar` | Cliente |
| `app_reserva_pendiente` | GET | `/reservas/{id}/pendiente` | Cliente |
| `app_mis_reservas` | GET | `/reservas` | Cliente |
| `app_reserva_cancelar` | POST | `/reservas/{id}/cancelar` | Cliente |
| `app_reserva_cancellation` | GET | `/reservas/{id}/cancelacion` | Cliente |
| `app_perfil` | GET\|POST | `/perfil` | Cliente |
| `app_register` | GET\|POST | `/register` | Visitante |
| `app_bienvenida` | GET | `/bienvenida` | Tras registro (sesión) |
| `app_login` / `app_logout` | — | `/login` / `/logout` | Todos |
| `admin_reservas` | GET | `/admin/reservas` | Admin |
| `admin_reserva_*` | POST | `/admin/reservas/{id}/…` | Admin |
| `admin_habitaciones` | GET | `/admin/habitaciones` | Admin |
| `admin_habitacion_nueva` | GET\|POST | `/admin/habitaciones/nueva` | Admin |
| `admin_habitacion_creada` | GET | `/admin/habitaciones/{id}/creada` | Admin |
| `admin_habitacion_editar` | GET\|POST | `/admin/habitaciones/{id}/editar` | Admin |
| `admin_usuarios` | GET | `/admin/usuarios` | Admin |
| `admin_usuario_editar` | POST | `/admin/usuarios/{id}/editar` | Admin |

Control de acceso (`config/packages/security.yaml`):

- `^/admin` → `ROLE_ADMIN`
- `^/(perfil|reservas)` → `ROLE_USER`

---

## Estructura y convenciones

Resumen alineado con `CONVENCIONES.md`:

```
src/Controller/     Controllers finos (reciben request, llaman al Manager, renderizan / redirigen)
src/Manager/        Lógica de negocio (validaciones, persistencia)
src/Repository/     Consultas a la base
src/Form/           FormTypes + constraints
src/Entity/         Habitacion, ImagenHabitacion, Reserva, User
src/Enum/           EstadoReserva, TipoHabitacion
src/Security/       Destino interno post-login / registro (InternalTargetPath)
src/EventSubscriber/ Login (admin, credenciales vacías)
src/DataFixtures/   Datos de demo
src/Migrations/     Migraciones Doctrine
templates/          Twig por sección (busqueda, habitacion, reserva, mis_reservas, perfil, admin/…)
public/css/         styles.css del mockup
public/img/         banners e imágenes de habitaciones
translations/       Mensajes de seguridad en español
```

Reglas clave:

- Idioma en español en dominio propio (`Habitacion`, `listarHabitaciones()`, etc.); lo generado por Symfony queda como viene (`User`, `app_login`).
- Controllers con atributos `#[Route]`, sin lógica de negocio.
- POST que modifica: CSRF + flash (cuando corresponde) + `redirectToRoute` (PRG).
- Twig siempre extiende `base.html.twig`; links con `path()`, assets con `asset()`.
- Estilos: preferir clases ya existentes en `public/css/styles.css`.

Managers: `HabitacionManager`, `ReservaManager`, `PerfilManager`, `UsuarioManager`.

---

## Estados de una reserva

```
PENDIENTE → CONFIRMADA | CANCELADA (rechazo admin / cancelación cliente si aplica)
CONFIRMADA → CANCELACION_PENDIENTE (pedido del cliente) | CANCELADA (admin)
CANCELACION_PENDIENTE → CANCELADA (admin confirma) | CONFIRMADA (admin revierte)
```

En la interfaz, el texto del estado puede acompañarse de un punto de color (pendiente, confirmada, cancelación pendiente, cancelada).

---

## Enlaces útiles

- Mockup: https://lucasdiaz-dev.github.io/pp1-hotel-mockup/
- Linear PP1: https://linear.app/thehubforlucasdev/team/PP1/overview
- Convenciones: [`CONVENCIONES.md`](CONVENCIONES.md)
