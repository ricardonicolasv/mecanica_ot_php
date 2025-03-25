<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarAcceso($rolesPermitidos = []) {
    // Si se pasa un string, lo convertimos en array automáticamente
    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }

    if (!isset($_SESSION['rol'])) {
        header('Location: ../secciones/login.php');
        exit();
    }

    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        echo "<div style='margin: 30px;'><h2>Acceso denegado</h2><p>No tienes permisos para acceder a esta página.</p></div>";
        exit();
    }
}
