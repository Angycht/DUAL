<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

session_start();
include 'db.php'; 
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Si es una solicitud preflight, termina aquí
    http_response_code(200);
    exit();
}
// Capturar los datos enviados por JSON
$data = json_decode(file_get_contents("php://input"), true);
$password = $data['password'] ?? null;
$email = $data['email'] ?? null;
$repetir = $data['repetir'] ?? null;

if (!$data) {
    http_response_code(400); // Error de datos incorrectos
    echo json_encode(["status" => "error", "message" => "Datos no válidos."]);
    exit;
}
// Validar que los campos no estén vacíos
if (!isset($password) || !isset($email) || !isset($repetir)) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
    exit;
}

// Validar que las contraseñas coincidan
if ($password !== $repetir) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Las contraseñas no coinciden."]);
    exit;
}

// Validar la fortaleza de la contraseña
$passwordPattern = "/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}/";

if (!preg_match($passwordPattern, $password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "La contraseña debe tener al menos 8 caracteres, una letra mayúscula, una letra minúscula, un número y un carácter especial."]);
    exit;
}
// Verificar si el correo ya está registrado en la base de datos
$sqlCheckEmail = "SELECT COUNT(*) FROM users WHERE email = :email";
$stmtCheckEmail = $conexion->prepare($sqlCheckEmail);
$stmtCheckEmail->execute([":email" => $email]);
$emailExists = $stmtCheckEmail->fetchColumn();

if ($emailExists > 0) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(["status" => "error", "message" => "El correo electrónico ya está registrado."]);
    exit;
}

// Hashear la contraseña
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Crear la consulta SQL para insertar los datos
$sql = "INSERT INTO users (password, email) VALUES (:password, :email)";

try {
    $stmt = $conexion->prepare($sql);
    $isOk = $stmt->execute([
        ':password' => $hashedPassword,
        ':email' => $email
    ]);

    if ($isOk) {
        echo json_encode(["status" => "success", "message" => "Usuario registrado correctamente."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error al registrar el usuario."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $e->getMessage()]);
}
?>
