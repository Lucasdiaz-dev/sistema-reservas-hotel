# Convenciones del proyecto (basadas en el carrito de clase)

Base: repo de clase `ldiazudi/pp1` → carpeta `carrito/`. Escribimos el hotel **igual que el carrito**. Si algo no está acá, copiá cómo lo hace el carrito. Si tampoco está ahí, preguntale a Lucas.

## 1. Idioma y nombres
- Todo en **español**: entidades, métodos, variables, rutas, templates (`Habitacion`, `listarHabitaciones()`, `$listaDeHabitaciones`).
- Lo que genera Symfony queda como viene (`User`, `getUser()`, `app_login`, `app_logout`).
- Clases en PascalCase, métodos y variables en camelCase, templates en minúscula (`habitacion/lista.html.twig`).

## 2. Carpetas
```
src/Controller/   controllers finitos (reciben, llaman al manager, renderizan)
src/Manager/      lógica de negocio (servicios)  ← como OrdenManager/ProductoManager
src/Repository/   consultas a la base
src/Form/         FormTypes
templates/<seccion>/*.html.twig
```

## 3. Controllers
- Extienden `AbstractController`, rutas con **atributos** `#[Route]`, siempre con `name`.
- El controller **no tiene lógica**: la pide al Manager, que se inyecta por constructor.
```php
class HabitacionController extends AbstractController
{
    private HabitacionManager $habitacionManager;

    public function __construct(HabitacionManager $habitacionManager){
        $this->habitacionManager = $habitacionManager;
    }

    #[Route('/admin/habitaciones', name: 'admin_habitaciones')]
    public function listarHabitaciones(): Response
    {
        $listaDeHabitaciones = $this->habitacionManager->getHabitaciones();
        return $this->render('admin/habitaciones/index.html.twig', [
            'habitaciones' => $listaDeHabitaciones,
        ]);
    }
}
```
- Acciones que modifican: `methods: ['POST']`, al final `addFlash(...)` + `redirectToRoute(...)` (nunca render después de un POST OK).
- Usuario logueado: `$this->getUser()`. Nunca por id de la URL si es "mi" dato.

## 4. Managers (lógica)
- `src/Manager/XxxManager.php`, dependencias por constructor (`XxxRepository`, `EntityManagerInterface`).
- Acá van validaciones de negocio, `persist()` y `flush()`.
```php
public function guardarHabitacion(Habitacion $habitacion): void
{
    $this->entityManager->persist($habitacion);
    $this->entityManager->flush();
}
```
- Comentarios cortos en español donde aporten (`// buscamos la habitación`).

## 5. Repositories y consultas
- Primero los métodos que ya vienen: `find($id)`, `findAll()`, `findBy([...], ['numero' => 'ASC'])`, `findOneBy([...])`.
- Si no alcanza, QueryBuilder con parámetros (**nunca** concatenar valores en el string):
```php
return $this->createQueryBuilder('r')
    ->andWhere('r.usuario = :usuario')
    ->setParameter('usuario', $usuario)
    ->orderBy('r.fechaIngreso', 'ASC')
    ->getQuery()
    ->getResult();
```

## 6. Forms y validación
- Formularios con FormType (`make:form`) cuando hay campos a editar. Validaciones de la tarea en el **form** (`constraints`) o en el Manager, **no** en la entidad.
```php
->add('precioPorNoche', NumberType::class, [
    'constraints' => [new Positive(message: 'El precio por noche debe ser mayor que cero.')],
])
```

## 7. Twig
- Siempre `{% extends 'base.html.twig' %}` + `{% block title %}` + `{% block body %}`. El header ya lo incluye `base` (no lo incluyas de nuevo).
- Links con `path('nombre_ruta', {'id': x.id})`, nunca URLs a mano. Archivos con `asset()`.
- Listas con `{% for %} ... {% else %} mensaje vacío {% endfor %}`.
- Flashes:
```twig
{% for message in app.flashes('success') %}
    <p class="muted">{{ message }}</p>
{% endfor %}
```
- Permisos en vista: `is_granted('ROLE_ADMIN')`, `app.user.nombre`.
- Solo clases que ya existen en `public/css/styles.css` (las del mockup). Cero CSS nuevo.

## 8. Seguridad
- La protección de rutas está en `security.yaml` (`^/admin` → ROLE_ADMIN, `/perfil` y `/reservas` → ROLE_USER). No lo toques.
- Contraseñas siempre con `UserPasswordHasherInterface`.

## 9. Flujo de trabajo Git
1. Rama por tarea desde main:
   `git checkout main && git pull && git checkout -b feature/<tarea>`
2. **Un commit por paso** de tu TAREA (ver los pasos numerados). Nada de un commit gigante al final.
   Formato: `<Sección>: <qué hiciste>` en español, presente. Ej: `Admin: listado de habitaciones`.
   Antes de cada commit: `git status` → solo archivos permitidos.
3. Subí seguido: `git push -u origin feature/<tarea>` (después solo `git push`).
4. Antes del PR: `git pull --rebase origin main` y probá que siga andando.
5. PR a `main`. **Nunca** push a `main`, **nunca** merge. Lucas revisa y mergea.

## 10. Setup local (una vez)
```
git clone https://github.com/Lucasdiaz-dev/sistema-reservas-hotel.git && cd sistema-reservas-hotel
composer install
```
Creá `.env.local` (no se commitea) con tu base:
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/sistema_reservas_hotel?serverVersion=8.0&charset=utf8mb4"
```
(ajustá usuario/contraseña de tu MySQL). Después:
```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony serve
```
**Usuarios demo** (contraseña `demo1234`): `admin@hotel.com` (admin), `juan@mail.com`, `maria@mail.com`, `carlos@mail.com`.
**Habitaciones demo:** 101–104 simples (104 inactiva), 201–205 dobles, 301–303 suites; 3 fotos cada una; 8 reservas con los 4 estados.
Las fotos ya están commiteadas en `public/img/habitaciones/`. `ImagenHabitacion.ruta` guarda solo el nombre → `asset('img/habitaciones/' ~ img.ruta)`.
Si rompés los datos: `php bin/console doctrine:fixtures:load` y vuelven a estar.
