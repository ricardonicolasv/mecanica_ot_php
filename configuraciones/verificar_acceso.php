<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarAcceso($rolesPermitidos = [])
{
    // Si se pasa un string, lo convertimos en array automáticamente
    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }

    if (!isset($_SESSION['rol'])) {
        header('Location: ../secciones/login.php');
        exit();
    }

    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        echo "<!DOCTYPE html><html><head>";
        echo "<meta charset='UTF-8'>";
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "</head><body>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso denegado',
                    text: 'No tienes permisos para acceder a esta página.',
                    confirmButtonText: 'Volver',
                    allowOutsideClick: false
                }).then(() => {
                    window.history.back(); // o puedes usar window.location.href = 'otra_pagina.php';
                });
            });
        </script>";
        echo "</body></html>";
        exit();
    }
}
?>