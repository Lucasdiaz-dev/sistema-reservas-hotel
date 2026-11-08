<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Entity\ImagenHabitacion;
use App\Repository\HabitacionRepository;
use Doctrine\ORM\EntityManagerInterface;

class HabitacionManager
{
    private HabitacionRepository $habitacionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HabitacionRepository $habitacionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->habitacionRepository = $habitacionRepository;
        $this->entityManager = $entityManager;
    }

    public function getHabitaciones(): array
    {
        return $this->habitacionRepository->findBy([], ['numero' => 'ASC']);
    }

    public function getHabitacion(int $id): ?Habitacion
    {
        return $this->habitacionRepository->find($id);
    }

    public function existeNumero(Habitacion $habitacion): bool
    {
        $habitacionEncontrada = $this->habitacionRepository->findOneBy([
            'numero' => $habitacion->getNumero(),
        ]);

        if ($habitacionEncontrada === null) {
            return false;
        }

        if ($habitacionEncontrada->getId() === $habitacion->getId()) {
            return false;
        }

        return true;
    }

    public function guardarHabitacion(Habitacion $habitacion): void
    {
        $this->entityManager->persist($habitacion);
        $this->entityManager->flush();
    }

    public function getImagenesDisponibles(): array
    {
        $carpeta = __DIR__ . '/../../public/img/habitaciones';
        $imagenes = [];

        if (!is_dir($carpeta)) {
            return $imagenes;
        }

        $archivos = scandir($carpeta);

        if ($archivos === false) {
            return $imagenes;
        }

        foreach ($archivos as $archivo) {
            $rutaCompleta = $carpeta . '/' . $archivo;
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

            if (
                is_file($rutaCompleta) &&
                in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            ) {
                $imagenes[] = $archivo;
            }
        }

        return $imagenes;
    }

    public function validarImagenes(array $seleccionadas, string $portada): ?string
    {
        if (count($seleccionadas) === 0) {
            return 'Debe asignar al menos una imagen.';
        }

        if ($portada === '') {
            return 'Debe marcar una portada.';
        }

        if (!in_array($portada, $seleccionadas, true)) {
            return 'La portada debe ser una de las imágenes seleccionadas.';
        }

        $disponibles = $this->getImagenesDisponibles();

        foreach ($seleccionadas as $archivo) {
            if (!in_array($archivo, $disponibles, true)) {
                return 'Una de las imágenes seleccionadas no está disponible.';
            }
        }

        return null;
    }

    public function guardarNuevaHabitacionConImagenes(
        Habitacion $habitacion,
        array $seleccionadas,
        string $portada
    ): void {
        $seleccionadas = array_unique($seleccionadas);

        $this->entityManager->persist($habitacion);

        foreach ($seleccionadas as $archivo) {
            $imagen = new ImagenHabitacion();
            $imagen->setRuta($archivo);
            $imagen->setPortada($archivo === $portada);

            $habitacion->addImagenHabitacion($imagen);

            $this->entityManager->persist($imagen);
        }

        $this->entityManager->flush();
    }

    public function actualizarHabitacionConImagenes(
        Habitacion $habitacion,
        array $seleccionadas,
        string $portada
    ): void {
        $seleccionadas = array_unique($seleccionadas);
        $rutasActuales = [];

        foreach ($habitacion->getImagenHabitacions()->toArray() as $imagen) {
            $ruta = $imagen->getRuta();

            if (!in_array($ruta, $seleccionadas, true)) {
                $habitacion->removeImagenHabitacion($imagen);
            } else {
                $imagen->setPortada($ruta === $portada);
                $rutasActuales[] = $ruta;
            }
        }

        foreach ($seleccionadas as $archivo) {
            if (!in_array($archivo, $rutasActuales, true)) {
                $imagen = new ImagenHabitacion();
                $imagen->setRuta($archivo);
                $imagen->setPortada($archivo === $portada);

                $habitacion->addImagenHabitacion($imagen);

                $this->entityManager->persist($imagen);
            }
        }

        $this->guardarHabitacion($habitacion);
    }
}