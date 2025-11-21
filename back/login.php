<?php
// Configuración de encabezados para permitir solicitudes de cualquier origen (CORS) y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- SIMULACIÓN DE USUARIOS ---
$usuarios = [
    ["username" => "admin", "password" => "1234", "name" => "Administrador"],
    ["username" => "user", "password" => "abcd", "name" => "Usuario Básico"]
];

$request_method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($uri, '/'));
$endpoint = end($path_parts);

// ===============================================
// ENDPOINT: /api/login (POST)
// ===============================================
if ($endpoint === 'login' && $request_method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->username) && isset($data->password)) {
        $found = false;
        foreach ($usuarios as $user) {
            if ($user['username'] === $data->username && $user['password'] === $data->password) {
                // Generar un token simple (simulación JWT)
                $payload = json_encode(['username' => $user['username'], 'exp' => time() + 3600]); // Expira en 1 hora
                $token = base64_encode($payload);
                
                http_response_code(200);
                echo json_encode(["message" => "Autenticación exitosa.", "token" => $token]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            // 401 Unauthorized
            http_response_code(401);
            echo json_encode(["message" => "Credenciales incorrectas."]);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(["message" => "Faltan credenciales."]);
    }
    exit();
}

// ===============================================
// ENDPOINT: /api/welcome (GET - PROTEGIDO)
// ===============================================
if ($endpoint === 'welcome' && $request_method === 'GET') {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // 1. Verificar si el token está presente
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
        
        // 2. Decodificar y validar el token
        $payload = base64_decode($token);
        $data = json_decode($payload, true);

        if (isset($data['username']) && isset($data['exp']) && $data['exp'] > time()) {
            // Token es válido y no ha expirado
            $current_user = null;
            foreach ($usuarios as $user) {
                if ($user['username'] === $data['username']) {
                    $current_user = $user;
                    break;
                }
            }
            
            if ($current_user) {
                http_response_code(200);
                echo json_encode([
                    "message" => "Datos del usuario obtenidos con éxito.", 
                    "user" => [
                        "name" => $current_user['name'],
                        "current_time" => date("H:i:s")
                    ]
                ]);
            } else {
                // 403 Forbidden (Token válido, pero usuario no encontrado)
                http_response_code(403);
                echo json_encode(["message" => "Token de usuario no válido."]);
            }

        } else {
            // 403 Forbidden (Token expirado o inválido)
            http_response_code(403);
            echo json_encode(["message" => "Token de autenticación expirado o inválido."]);
        }
    } else {
        // 403 Forbidden (Token no proporcionado)
        http_response_code(403);
        echo json_encode(["message" => "No se proporcionó token de autenticación."]);
    }
    exit();
}

// Si no coincide ninguna ruta API
if (strpos($uri, 'api/') !== false) {
    http_response_code(404);
    echo json_encode(["message" => "Ruta API no encontrada."]);
    exit();
}
// NOTA: Para que este script funcione como un router simple, 
// podrías necesitar configurar .htaccess si estás usando Apache,
// o configurar las rutas directamente en tu servidor web.
// Por ejemplo, para Apache, un archivo .htaccess simple podría ser:
// RewriteEngine On
// RewriteRule ^api/([a-zA-Z0-9_/]+)$ index.php [QSA,L]
?>