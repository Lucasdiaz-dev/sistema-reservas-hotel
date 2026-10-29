<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029103608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE habitacion (id INT AUTO_INCREMENT NOT NULL, numero INT NOT NULL, tipo VARCHAR(255) NOT NULL, capacidad_maxima INT NOT NULL, precio_por_noche NUMERIC(10, 2) NOT NULL, descripcion LONGTEXT NOT NULL, activa TINYINT NOT NULL, estrellas SMALLINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE imagen_habitacion (id INT AUTO_INCREMENT NOT NULL, ruta VARCHAR(255) NOT NULL, portada TINYINT NOT NULL, habitacion_id INT NOT NULL, INDEX IDX_D7485427B009290D (habitacion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reserva (id INT AUTO_INCREMENT NOT NULL, fecha_ingreso DATE NOT NULL, fecha_egreso DATE NOT NULL, cantidad_huespedes SMALLINT NOT NULL, total NUMERIC(10, 2) NOT NULL, estado VARCHAR(255) NOT NULL, usuario_id INT NOT NULL, habitacion_id INT NOT NULL, INDEX IDX_188D2E3BDB38439E (usuario_id), INDEX IDX_188D2E3BB009290D (habitacion_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) NOT NULL, telefono VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE imagen_habitacion ADD CONSTRAINT FK_D7485427B009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id)');
        $this->addSql('ALTER TABLE reserva ADD CONSTRAINT FK_188D2E3BDB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reserva ADD CONSTRAINT FK_188D2E3BB009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE imagen_habitacion DROP FOREIGN KEY FK_D7485427B009290D');
        $this->addSql('ALTER TABLE reserva DROP FOREIGN KEY FK_188D2E3BDB38439E');
        $this->addSql('ALTER TABLE reserva DROP FOREIGN KEY FK_188D2E3BB009290D');
        $this->addSql('DROP TABLE habitacion');
        $this->addSql('DROP TABLE imagen_habitacion');
        $this->addSql('DROP TABLE reserva');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
