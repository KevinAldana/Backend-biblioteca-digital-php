<?php
// Establecer el tiempo de expiración en segundos (por ejemplo, 30 minutos)
$session_expirada = 1800; 
session_set_cookie_params($session_expirada);
session_start();

// Si el usuario se ha logueado, actualizar el tiempo de la última actividad
if (isset($_SESSION['logged_in'])) {
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > $session_expirada) {
        session_unset();
        session_destroy();
        echo json_encode(['message' => 'Sesión expirada.']);
        exit;
    }
    $_SESSION['ultima_actividad'] = time();
}
?>
