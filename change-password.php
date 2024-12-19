<?php
session_start();
require_once 'db.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


// Responder a solicitudes OPTIONS (esto es necesario para que CORS funcione correctamente en algunos navegadores)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

    // Obtener datos del cuerpo de la solicitud
    $email = $_POST['email'] ?? '';
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validar que los campos no estén vacíos
    if (empty($email) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
        exit;
    }

    // Verificar si las contraseñas coinciden
    if ($newPassword !== $confirmPassword) {
        echo json_encode(["status" => "error", "message" => "Las nuevas contraseñas no coinciden."]);
        exit;
    }


    // Verificar el correo y la contraseña actual en la base de datos
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':email' => $email]);
    //Busca en cada línea de la base de datos
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
//Si la variable $user es false (es decir, no se encontró un registro en la base de datos con el correo proporcionado), 
//significa que el correo no existe en la base de datos.
    if (!$user || !password_verify($oldPassword, $user['password'])) {
        echo json_encode(["status" => "error", "message" => "Correo o contraseña actual incorrectos."]);
        exit;
    }

    // Hashear la nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Actualizar la contraseña en la base de datos
    try {
        $updateSql = "UPDATE users SET password = :newPassword WHERE email = :email";
        $updateStmt = $conexion->prepare($updateSql);
        $updateStmt->execute([
            ':newPassword' => $hashedPassword,
            ':email' => $email,
        ]);

        if ($updateStmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Contraseña actualizada con éxito."]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo actualizar la contraseña."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
    }

?>
