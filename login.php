<?php
header("Content-Type: application/json");  // Definir que la respuesta será en formato JSON
header("Access-Control-Allow-Origin: *");  // Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Encabezados permitidos
session_start();
include 'db.php'; 
// Responder a solicitudes OPTIONS (esto es necesario para que CORS funcione correctamente en algunos navegadores)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Recuperar los datos del formulario (en formato JSON)
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

// Verificar que los campos no estén vacíos
if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "El email y la contraseña son obligatorios."]);
    exit;
}

// Preparar la consulta SQL para buscar el email
$sql = "SELECT * FROM users WHERE email = :email LIMIT 1";

// Ejecutar la consulta
$stmt = $conexion->prepare($sql);
$stmt->execute([':email' => $email]);

// Verificar si el usuario existe
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // El usuario existe, ahora verificamos la contraseña
    if (password_verify($password, $user['password'])) {
        // La contraseña es correcta, iniciar sesión
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(["status" => "success", "message" => "¡Bienvenido!"]);
    } else {
        // Contraseña incorrecta
        echo json_encode(["status" => "error", "message" => "La contraseña es incorrecta."]);
    }
} else {
    // El email no existe
    echo json_encode(["status" => "error", "message" => "El email no está registrado."]);
}
?>
