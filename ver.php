<?php
/**
 * Visor de registro.log — companion de log.php (laboratorio XSS con DVWA).
 * Muestra los datos capturados como texto plano (nunca como HTML, para no
 * inyectar markup en la propia herramienta del laboratorio).
 */

$log = __DIR__ . '/registro.log';

header('Content-Type: text/plain; charset=utf-8');

if (!is_file($log) || filesize($log) === 0) {
    echo "Todavía no se capturó nada.\n";
    echo "Ejecutá el payload en DVWA y volvé a abrir esta página (F5).\n";
    exit;
}

echo "== Datos capturados por log.php ==\n\n";
echo file_get_contents($log);
echo "\nPara la próxima demostración: borrar registro.log (o vaciarlo) y recargar.\n";
