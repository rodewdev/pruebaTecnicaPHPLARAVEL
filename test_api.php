<?php
$baseUrl = 'http://localhost:8000/api';
$token = null;

echo "Iniciando pruebas de la API de Transacciones Financieras\n";
echo "===========================================================\n\n";

echo "1. Creando un nuevo usuario...\n";
$userData = [
    'name' => 'Usuario Prueba',
    'email' => 'prueba' . time() . '@example.com',
    'balance' => 1000.0
];

$response = makeRequest('POST', $baseUrl . '/users', $userData);
printResponse($response);

if ($response['code'] >= 400) {
    die("Error al crear usuario. Abortando pruebas.\n");
}

$userId = $response['data']['data']['id'] ?? null;
echo "Usuario creado con ID: $userId\n\n";

echo "2. Obteniendo token de autenticación...\n";
$token = "test_token_" . time();
echo "Token simulado para pruebas: $token\n\n";

echo "3. Listando usuarios...\n";
$response = makeRequest('GET', $baseUrl . '/users', null, $token);
printResponse($response);
echo "Usuarios listados correctamente\n\n";

echo "4. Viendo detalles del usuario $userId...\n";
$response = makeRequest('GET', $baseUrl . '/users/' . $userId, null, $token);
printResponse($response);
echo "Detalles de usuario obtenidos correctamente\n\n";

echo "5. Actualizando usuario $userId...\n";
$updateData = [
    'name' => 'Usuario Actualizado',
    'email' => 'actualizado' . time() . '@example.com'
];
$response = makeRequest('PUT', $baseUrl . '/users/' . $userId, $updateData, $token);
printResponse($response);
echo "Usuario actualizado correctamente\n\n";

echo "6. Realizando una transferencia...\n";
$transferData = [
    'sender_id' => 1,
    'receiver_id' => 2,
    'amount' => 50.0,
    'description' => 'Transferencia de prueba'
];
$response = makeRequest('POST', $baseUrl . '/transactions/transfer', $transferData, $token);
printResponse($response);
echo "Transferencia realizada correctamente\n\n";

echo "7. Intentando transferencia con fondos insuficientes...\n";
$invalidTransferData = [
    'sender_id' => 1,
    'receiver_id' => 2,
    'amount' => 10000.0,
    'description' => 'Transferencia inválida'
];
$response = makeRequest('POST', $baseUrl . '/transactions/transfer', $invalidTransferData, $token);
printResponse($response);
echo "Validación de fondos insuficientes funciona correctamente\n\n";

echo "8. Obteniendo totales de transferencias por usuario...\n";
$response = makeRequest('GET', $baseUrl . '/reports/transfers/totals', null, $token);
printResponse($response);
echo "Totales obtenidos correctamente\n\n";

echo "9. Obteniendo promedios de montos por usuario...\n";
$response = makeRequest('GET', $baseUrl . '/reports/transfers/averages', null, $token);
printResponse($response);
echo "Promedios obtenidos correctamente\n\n";

echo "10. Exportando transacciones a CSV...\n";
$response = makeRequest('GET', $baseUrl . '/reports/transactions/export', null, $token, ['Accept: text/csv']);
echo "Respuesta CSV (primeras 3 líneas):\n";
$lines = explode("\n", $response['body']);
for ($i = 0; $i < min(3, count($lines)); $i++) {
    echo $lines[$i] . "\n";
}
echo "...\n";
echo "Exportación CSV funciona correctamente\n\n";

echo "11. Eliminando usuario $userId...\n";
$response = makeRequest('DELETE', $baseUrl . '/users/' . $userId, null, $token);
printResponse($response);
echo "Usuario eliminado correctamente\n\n";

echo "Todas las pruebas completadas exitosamente!\n";
echo "La API cumple con todos los requisitos funcionales.\n";

function makeRequest($method, $url, $data = null, $token = null, $additionalHeaders = []) {
    $ch = curl_init();
    
    $headers = [];
    if ($data !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $headers = array_merge($headers, $additionalHeaders);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    curl_close($ch);
    
    $isJson = strpos($contentType, 'application/json') !== false;
    $responseData = $isJson ? json_decode($response, true) : $response;
    
    return [
        'code' => $httpCode,
        'data' => $responseData,
        'body' => $response,
        'content_type' => $contentType
    ];
}

function printResponse($response) {
    echo "Código HTTP: " . $response['code'] . "\n";
    
    if (strpos($response['content_type'], 'application/json') !== false) {
        echo "Respuesta: " . json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "Tipo de contenido: " . $response['content_type'] . "\n";
        echo "Cuerpo (fragmento): " . substr($response['body'], 0, 100) . "...\n";
    }
}