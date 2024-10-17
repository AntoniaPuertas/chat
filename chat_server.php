<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$chatFile = __DIR__ . '/chat_messages.txt';

// Asegurarse de que el archivo de chat existe
if (!file_exists($chatFile)) {
    file_put_contents($chatFile, "");
}

// Función para registrar mensajes de depuración
function debug_log($message) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejar nuevos mensajes
    $message = $_POST['message'] ?? '';
    if (!empty($message)) {
        $username = $_SESSION['username'] ?? 'Usuario';
        $newMessage = date('Y-m-d H:i:s') . " - $username: " . htmlspecialchars($message) . "\n";
        $result = file_put_contents($chatFile, $newMessage, FILE_APPEND);
        if ($result === false) {
            debug_log("Error al escribir en el archivo: $chatFile");
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo guardar el mensaje']);
        } else {
            echo json_encode(['success' => true]);
        }
    } else {
        debug_log("Intento de enviar mensaje vacío");
        http_response_code(400);
        echo json_encode(['error' => 'El mensaje está vacío']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Long polling para obtener nuevos mensajes
    $lastModified = isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : 0;
    $currentModified = filemtime($chatFile);

    // Esperar hasta que haya nuevos mensajes o hasta que pasen 30 segundos
    $timeout = 30;
    $start = time();
    while ($currentModified <= $lastModified) {
        usleep(100000); // Esperar 0.1 segundos
        clearstatcache();
        $currentModified = filemtime($chatFile);
        if ((time() - $start) > $timeout) {
            break;
        }
    }

    // Enviar nuevos mensajes
    $messages = file_get_contents($chatFile);
    echo json_encode([
        'messages' => $messages,
        'timestamp' => $currentModified
    ]);
}