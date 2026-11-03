<?php

namespace App\DataFixtures;

use App\Entity\Habitacion;
use App\Entity\ImagenHabitacion;
use App\Entity\Reserva;
use App\Entity\User;
use App\Enum\EstadoReserva;
use App\Enum\TipoHabitacion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    // Contraseña de todos los usuarios demo
    private const PASSWORD = 'demo1234';

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // ---------- Usuarios ----------
        $usuarios = [];
        $datosUsuarios = [
            ['admin@hotel.com',  'Admin',  'Hotel',     '3510000000', ['ROLE_ADMIN']],
            ['juan@mail.com',    'Juan',   'Pérez',     '3511111111', []],
            ['maria@mail.com',   'María',  'Gómez',     '3512222222', []],
            ['carlos@mail.com',  'Carlos', 'Rodríguez', '3513333333', []],
        ];
        foreach ($datosUsuarios as [$email, $nombre, $apellido, $tel, $roles]) {
            $u = (new User())
                ->setEmail($email)
                ->setNombre($nombre)
                ->setApellido($apellido)
                ->setTelefono($tel)
                ->setRoles($roles);
            $u->setPassword($this->hasher->hashPassword($u, self::PASSWORD));
            $manager->persist($u);
            $usuarios[] = $u;
        }

        // ---------- Habitaciones ----------
        // [numero, tipo, capacidad, precio, estrellas, activa, descripcion]
        $datosHab = [
            [101, TipoHabitacion::SIMPLE, 1, '35000', 3, true,  'Habitación simple con cama de una plaza, escritorio, aire acondicionado y wifi.'],
            [102, TipoHabitacion::SIMPLE, 1, '35000', 3, true,  'Habitación simple luminosa, ideal para viajes de trabajo.'],
            [103, TipoHabitacion::SIMPLE, 2, '40000', 3, true,  'Habitación simple amplia con cama de dos plazas.'],
            [104, TipoHabitacion::SIMPLE, 1, '35000', 3, false, 'Habitación simple (fuera de servicio por mantenimiento).'],
            [201, TipoHabitacion::DOBLE,  2, '55000', 4, true,  'Habitación doble con cama king, iluminación cálida y vista a la ciudad.'],
            [202, TipoHabitacion::DOBLE,  2, '55000', 4, true,  'Habitación doble con dos camas, escritorio y smart TV.'],
            [203, TipoHabitacion::DOBLE,  3, '62000', 4, true,  'Habitación doble con cama king y sofá cama.'],
            [204, TipoHabitacion::DOBLE,  2, '58000', 4, true,  'Habitación doble con balcón y cafetera.'],
            [205, TipoHabitacion::DOBLE,  4, '70000', 4, true,  'Habitación doble familiar con dos camas queen.'],
            [301, TipoHabitacion::SUITE,  2, '95000', 5, true,  'Suite con living separado, bañera y vista panorámica.'],
            [302, TipoHabitacion::SUITE,  4, '120000', 5, true, 'Suite familiar con dos ambientes y kitchenette.'],
            [303, TipoHabitacion::SUITE,  2, '110000', 5, true, 'Suite premium con cama king, living y minibar.'],
        ];

        // Fotos disponibles en public/img/habitaciones/
        $fotos = [
            'SIMPLE' => array_map(fn ($i) => sprintf('simple-%02d.jpg', $i), range(1, 6)),
            'DOBLE'  => array_map(fn ($i) => sprintf('doble-%02d.jpg', $i), range(1, 8)),
            'SUITE'  => array_map(fn ($i) => sprintf('suite-%02d.jpg', $i), range(1, 8)),
        ];
        $banos = ['bano-01.jpg', 'bano-02.jpg', 'bano-03.jpg', 'bano-04.jpg'];

        $habitaciones = [];
        $contador = ['SIMPLE' => 0, 'DOBLE' => 0, 'SUITE' => 0];
        foreach ($datosHab as [$numero, $tipo, $cap, $precio, $estrellas, $activa, $desc]) {
            $h = (new Habitacion())
                ->setNumero($numero)
                ->setTipo($tipo)
                ->setCapacidadMaxima($cap)
                ->setPrecioPorNoche($precio)
                ->setEstrellas($estrellas)
                ->setActiva($activa)
                ->setDescripcion($desc);

            // 2 fotos del tipo + 1 baño. La primera es la portada.
            $pool = $fotos[$tipo->name];
            $i = $contador[$tipo->name]++;
            $rutas = [
                $pool[(2 * $i) % count($pool)],
                $pool[(2 * $i + 1) % count($pool)],
                $banos[$numero % count($banos)],
            ];
            foreach ($rutas as $k => $ruta) {
                $img = (new ImagenHabitacion())
                    ->setRuta($ruta)
                    ->setPortada($k === 0);
                $h->addImagenHabitacion($img);
                $manager->persist($img);
            }

            $manager->persist($h);
            $habitaciones[$numero] = $h;
        }

        // ---------- Reservas ----------
        // [usuario, habitacion, dias desde hoy (ingreso), noches, huespedes, estado]
        $hoy = new \DateTimeImmutable('today');
        $datosRes = [
            [1, 201,  10, 3, 2, EstadoReserva::CONFIRMADA],
            [1, 101, -20, 2, 1, EstadoReserva::CONFIRMADA],
            [1, 301,  30, 4, 2, EstadoReserva::PENDIENTE],
            [2, 202,   5, 2, 2, EstadoReserva::PENDIENTE],
            [2, 302,  15, 5, 4, EstadoReserva::CANCELACION_PENDIENTE],
            [2, 102, -10, 1, 1, EstadoReserva::CANCELADA],
            [3, 203,  20, 3, 3, EstadoReserva::CONFIRMADA],
            [3, 303,  45, 2, 2, EstadoReserva::CANCELADA],
        ];
        foreach ($datosRes as [$u, $num, $dias, $noches, $huespedes, $estado]) {
            $hab = $habitaciones[$num];
            $ingreso = $hoy->modify(sprintf('%+d days', $dias));
            $r = (new Reserva())
                ->setUsuario($usuarios[$u])
                ->setHabitacion($hab)
                ->setFechaIngreso($ingreso)
                ->setFechaEgreso($ingreso->modify(sprintf('+%d days', $noches)))
                ->setCantidadHuespedes($huespedes)
                ->setTotal(number_format((float) $hab->getPrecioPorNoche() * $noches, 2, '.', ''))
                ->setEstado($estado);
            $manager->persist($r);
        }

        $manager->flush();
    }
}
