<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Entity\ImagenHabitacion;
use App\Repository\HabitacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HabitacionManager
{
    public const TAMANO_MAXIMO_BYTES = 5242880;

    private const MENSAJE_SIN_ARCHIVO = 'Debe seleccionar una imagen.';
    private const MENSAJE_TAMANO = 'La imagen supera el tamaño máximo de 5 MB.';
    private const MENSAJE_TIPO = 'El archivo debe ser una imagen JPG, PNG o WebP.';
    private const MENSAJE_INCOMPLETA = 'La imagen se subió de forma incompleta. Intentá de nuevo.';
    private const MENSAJE_GENERICO = 'No se pudo subir la imagen.';
    private const MENSAJE_GUARDADO = 'No se pudo guardar la imagen.';

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

    public function mensajeTamanoMaximo(): string
    {
        return self::MENSAJE_TAMANO;
    }

    public function subirImagen(mixed $archivo): string
    {
        if (!$archivo instanceof UploadedFile) {
            throw new \DomainException(self::MENSAJE_SIN_ARCHIVO);
        }

        if (!$archivo->isValid()) {
            throw new \DomainException($this->mensajeDeErrorDeCarga($archivo->getError()));
        }

        $rutaTemporal = $archivo->getPathname();
        if (!is_uploaded_file($rutaTemporal)) {
            throw new \DomainException(self::MENSAJE_GENERICO);
        }

        $tamano = $archivo->getSize();
        if ($tamano === false || $tamano < 1) {
            throw new \DomainException(self::MENSAJE_SIN_ARCHIVO);
        }

        if ($tamano > self::TAMANO_MAXIMO_BYTES) {
            throw new \DomainException(self::MENSAJE_TAMANO);
        }

        $extension = $this->extensionDeImagen($rutaTemporal);
        if ($extension === null) {
            throw new \DomainException(self::MENSAJE_TIPO);
        }

        $carpeta = $this->directorioImagenes();
        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
            throw new \DomainException(self::MENSAJE_GUARDADO);
        }

        $nombre = $this->nombreUnico($carpeta, $extension);

        try {
            $archivo->move($carpeta, $nombre);
        } catch (FileException) {
            throw new \DomainException(self::MENSAJE_GUARDADO);
        }

        $carpetaReal = realpath($carpeta);
        $destinoReal = realpath($carpeta . DIRECTORY_SEPARATOR . $nombre);
        $prefijo = $carpetaReal === false ? '' : $carpetaReal . DIRECTORY_SEPARATOR;
        if (
            $carpetaReal === false
            || $destinoReal === false
            || !is_file($destinoReal)
            || !str_starts_with($destinoReal, $prefijo)
        ) {
            if (is_string($destinoReal) && is_file($destinoReal)) {
                unlink($destinoReal);
            }

            throw new \DomainException(self::MENSAJE_GUARDADO);
        }

        return $nombre;
    }

    public function getImagenesDisponibles(): array
    {
        $carpeta = $this->directorioImagenes();
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

    public function filtrarImagenesSeleccionadas(array $seleccionadas): array
{
    $disponibles = $this->getImagenesDisponibles();
    $imagenesValidas = [];

    foreach ($seleccionadas as $archivo) {
        if (in_array($archivo, $disponibles, true)) {
            $imagenesValidas[] = $archivo;
        }
    }

    return array_values(array_unique($imagenesValidas));
}

public function validarImagenes(array $seleccionadas, string $portada): ?string
{
    if (count($seleccionadas) === 0) {
        return 'Debe asignar al menos una imagen.';
    }

    if ($portada === '' || !in_array($portada, $seleccionadas, true)) {
        return 'Debe marcar una portada.';
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

    private function directorioImagenes(): string
    {
        return __DIR__ . '/../../public/img/habitaciones';
    }

    private function mensajeDeErrorDeCarga(int $codigo): string
    {
        return match ($codigo) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => self::MENSAJE_TAMANO,
            \UPLOAD_ERR_NO_FILE => self::MENSAJE_SIN_ARCHIVO,
            \UPLOAD_ERR_PARTIAL => self::MENSAJE_INCOMPLETA,
            default => self::MENSAJE_GENERICO,
        };
    }

    private function extensionDeImagen(string $ruta): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        $extensionPorMime = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extensionPorMime === null) {
            return null;
        }

        $info = @getimagesize($ruta);
        if ($info === false) {
            return null;
        }

        $extensionPorImagen = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => null,
        };

        if ($extensionPorImagen !== $extensionPorMime) {
            return null;
        }

        $ancho = $info[0] ?? 0;
        $alto = $info[1] ?? 0;
        if ($ancho < 1 || $alto < 1) {
            return null;
        }

        return $extensionPorImagen;
    }

    private function nombreUnico(string $carpeta, string $extension): string
    {
        do {
            $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
        } while (is_file($carpeta . DIRECTORY_SEPARATOR . $nombre));

        return $nombre;
    }
}