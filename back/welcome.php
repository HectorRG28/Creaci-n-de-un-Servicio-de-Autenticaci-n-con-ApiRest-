<?php
header("Content-Type: application/json");

$headers = getallheaders();
$auth = $headers["Authorization"] ?? "";

if (!$auth || !str_starts_with($auth, "Bearer ")) {
    http_response_code(403);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace("Bearer ", "", $auth);

// Validación simple: token base64 con username al inicio
$decoded = base64_decode($token);
$username = explode(":", $decoded)[0] ?? "";

if (!$username) {
    http_response_code(403);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}

echo json_encode([
    "usuario" => $username,
    "hora" => date("H:i:s"),
    "mensaje" => "Bienvenido a tu área personal"
]);
