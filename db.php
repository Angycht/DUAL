<?php
// Configuración de la conexión a la base de datos
$host = "localhost";
$dbname = "usuario"; // Nombre de la base de datos
$username = "root"; // Usuario de la base de datos
$passwordDB = ""; // Contraseña de la base de datos

// Conectar a la base de datos
try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname", $username, $passwordDB);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { // Error de conexión
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos. " . $e->getMessage()]);
    exit;
}
