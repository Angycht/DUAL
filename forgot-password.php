<?php
session_start();
include 'db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$email = isset($data->email) ? $data->email : null;

if (!$email) {
    echo json_encode(["error" => "No se proporcionó un correo."]);
    exit;
}
try {
    // Iniciar la transacción
    $conexion->beginTransaction();

    // Verificar si el correo existe en la base de datos
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $conexion->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $newPassword = generateRandomPassword();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos
        $updateSql = "UPDATE users SET password = :password WHERE email = :email";
        $updateStmt = $conexion->prepare($updateSql);
        $updateStmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $updateStmt->bindValue(':email', $email, PDO::PARAM_STR);

        if ($updateStmt->execute()) {
            if (sendEmail($email, $newPassword)) {
                // Si todo es correcto, confirmar la transacción
                $conexion->commit();
                echo json_encode(["message" => "Te hemos enviado un correo con la nueva contraseña."]);
            } else {
                // Si no se puede enviar el correo, revertir la transacción
                $conexion->rollBack();
                echo json_encode(["error" => "No se pudo enviar el correo."]);
            }
        } else {
            // Si hay un error al actualizar la contraseña, revertir la transacción
            $conexion->rollBack();
            echo json_encode(["error" => "Hubo un error al actualizar la contraseña."]);
        }
    } else {
        echo json_encode(["error" => "El correo no está registrado."]);
    }
} catch (PDOException $e) {
    // Si ocurre un error en cualquier parte, revertir la transacción
    $conexion->rollBack();
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
}


$conexion = null;

// Función para generar una contraseña aleatoria
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $password;
}

// Función para enviar el correo sin PHPMailer
function sendEmail($to, $newPassword) {
    $subject = "Recuperación de Contraseña";
    $message = "<p>Tu nueva contraseña es: <strong>$newPassword</strong></p><p>Por favor, cámbiala tan pronto como sea posible.</p>";
  
    
    return mail($to, $subject, $message);
}