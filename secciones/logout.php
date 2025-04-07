<?php
session_start();
session_unset();    // Elimina todas las variables de sesión
session_destroy();  // Destruye la sesión
include('../templates/header.php');
header("Location: login.php"); // Redirige al login (ajusta la ruta si es necesario)
exit();
include('../templates/footer.php');
?>