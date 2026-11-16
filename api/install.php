<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Solo desde localhost.'); }

$root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

if (isset($_GET['reset'])) { // BORRA todo (solo para desarrollo)
    db()->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['retro', 'fichas', 'users'] as $t) db()->exec("DROP TABLE IF EXISTS $t");
    db()->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Tablas anteriores eliminadas.\n";
}

db()->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    pass_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','docente','alumno') NOT NULL,
    ver_fichas TINYINT(1) NOT NULL DEFAULT 0,
    ingresar_pacientes TINYINT(1) NOT NULL DEFAULT 0,
    retroalimentar TINYINT(1) NOT NULL DEFAULT 0,
    gestionar_alumnos TINYINT(1) NOT NULL DEFAULT 0,
    registrar_fichas TINYINT(1) NOT NULL DEFAULT 0,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
db()->exec("CREATE TABLE IF NOT EXISTS fichas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    asignatura VARCHAR(120) NOT NULL,
    estado ENUM('abierta','archivada') NOT NULL DEFAULT 'abierta',
    datos LONGTEXT NOT NULL,
    evaluacion LONGTEXT NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    creado_por INT UNSIGNED NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB");
db()->exec("CREATE TABLE IF NOT EXISTS retro (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ficha_id INT UNSIGNED NOT NULL,
    autor_id INT UNSIGNED NULL,
    autor VARCHAR(120) NOT NULL,
    texto TEXT NOT NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ficha_id) REFERENCES fichas(id) ON DELETE CASCADE
) ENGINE=InnoDB");

echo "Base de datos '" . DB_NAME . "' lista.\n";
if (db()->query("SELECT COUNT(*) FROM users WHERE rol='admin'")->fetchColumn() == 0) {
    $pass = bin2hex(random_bytes(5));
    db()->prepare("INSERT INTO users (nombre,email,pass_hash,rol) VALUES ('Jefa de carrera','admin@cftohiggins.cl',?,'admin')")
        ->execute([password_hash($pass, PASSWORD_DEFAULT)]);
    echo "Cuenta de jefatura creada.\nCorreo: admin@cftohiggins.cl\nContraseña: $pass\n(Guárdala: no se vuelve a mostrar.)\n";
} else {
    echo "La cuenta de jefatura ya existe.\n";
}
