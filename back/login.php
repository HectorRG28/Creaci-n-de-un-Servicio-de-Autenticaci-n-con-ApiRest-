<?php
//Permite que los HTML que esten en otros sitios se puedan comunicar con el back
header("Access-Control-Allow-Origin: *"); 
//Avisa que la rspuesta que dara es un mensaje estructurado en JSON
header("Content-Type: application/json; charset=UTF-8");
//Dice las respuestas que tipos de solicitudes acepta(enviar o pedir datos)
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
//Especifica que espere hasta que le envie la clave
header("Access-Control-Allow-Headers: Content-Type, Authorization");

//Si le pregunta un HTML si puede enviar datos, responde que si y no mas de 200S
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

//Lista de nombres y contraseña que conoce el php
$usuarios = [
    ["username" => "admin", "password" => "1234"],
    ["username" => "user", "password" => "abcd"]
];
//Indica no si hay un error con el código
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(["message" => $message]);
    exit();
}

// Herramienta para buscar el token en la cabecera Authorization
function get_token_from_header() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

//¿Que le piden al php?
$request_uri = $_SERVER['REQUEST_URI'];

//Si la página le pide entrar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($request_uri, '/api/login') !== false) {
    //Lee el nombre y contraseña que envió la recepción
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $authenticated = false;
    $user_name = '';

    // Revisa si el nombre y contraseña están en su lista
    foreach ($usuarios as $user) {
        if ($user['username'] === $username && $user['password'] === $password) {
            $authenticated = true;
            $user_name = $user['username'];
            break;
        }
    }

    if ($authenticated) {
        //Fabrica la llave Digital, codifica el nombre y la hora actual
        $token_data = [
            'username' => $user_name,
            'iat' => time() 
        ];
        $token = base64_encode(json_encode($token_data)); 
        // Respuesta exitosa con el token
        http_response_code(200);
        echo json_encode([
            "message" => "Login exitoso",
            "token" => $token
        ]);
    } else {
        // Código de error 401 Unauthorized (Requisito)
        send_error(401, "Credenciales incorrectas. Acceso no autorizado."); 
    }
}

// Si la pagina pide entrar a la BD (GET/ api/welcome)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($request_uri, '/api/welcome') !== false) {
    $token = get_token_from_header();

    // Verifica si tiene llave, si no dice error
    if (!$token) {
        // Código de error 403 Forbidden si no hay token (Requisito)
        send_error(403, "Se requiere un token de autenticación para acceder a este recurso.");
    }

    // Si tiene la llave, la decodifica y revisa si es válida
    $decoded_data = json_decode(base64_decode($token), true);
    $is_token_valid = false;
    $username_from_token = '';
    //Logica para ver si la llave es válida
    if (is_array($decoded_data) && isset($decoded_data['username'])) {
        $username_from_token = $decoded_data['username'];
        $found = false;
        foreach ($usuarios as $user) {
            if ($user['username'] === $username_from_token) {
                $found = true; 
                break;
            }
        }
        if ($found) {
            $is_token_valid = true;
        }
    }
    //Si la llave no sirve da error
    if (!$is_token_valid) {
        send_error(403, "Token inválido o no reconocido. Acceso prohibido.");
    }

    // Si la llave es correcta da OK y devuelve los datos
    http_response_code(200);
    echo json_encode([
        "message" => "Datos de bienvenida obtenidos con éxito.",
        "username" => $username_from_token,
        "time" => date("H:i:s"), // La hora actual (Requisito)
        "welcome_message" => "¡Has accedido al sistema con tu token de seguridad!"
    ]);
}

//Si piden algo que no está en la lista de tareas
else {
    http_response_code(404);
    echo json_encode(["message" => "Ruta de API no encontrada."]);
}
?>