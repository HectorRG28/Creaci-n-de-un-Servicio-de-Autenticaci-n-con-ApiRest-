<?php
header("Content-Type: application/json");

// Usuarios simulados
$users = [
    "carlos" => "1234",
    "ana" => "abcd",
    "juan" => "pass"
];

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"] ?? "";
$password = $data["password"] ?? "";

if (isset($users[$username]) && $users[$username] === $password) {
    $token = base64_encode($username . ":" . time());

    echo json_encode(["token" => $token]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Credenciales incorrectas"]);
}
