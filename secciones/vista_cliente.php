<?php 
session_start();
require_once('../configuraciones/bd.php');
include('../secciones/clientes.php'); 
include('../templates/header.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso('cliente');

// Verifica si el cliente está autenticado. Si no, redirige a la página de login o registro.
if (!isset($_SESSION['cliente'])) {
    header("Location: login.php");
    exit();
}

// Recupera los datos del cliente de la sesión
$cliente = $_SESSION['cliente'];
?>
<main>
    <div class="container mt-5">
        <h1 class="text-center">Bienvenido, <?= htmlspecialchars($cliente['nombre_cliente']); ?>!</h1>
        <p class="text-center">Tu cuenta se ha creado exitosamente.</p>
        <div class="card mt-4">
            <div class="card-header">
                Mis Datos
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre_cliente']); ?></li>
                <li class="list-group-item"><strong>Apellido:</strong> <?= htmlspecialchars($cliente['apellido_cliente']); ?></li>
                <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($cliente['email']); ?></li>
                <li class="list-group-item"><strong>RUT:</strong> <?= htmlspecialchars($cliente['rut']); ?></li>
                <li class="list-group-item"><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion']); ?></li>
                <li class="list-group-item"><strong>Contacto:</strong> <?= htmlspecialchars($cliente['nro_contacto']); ?></li>
            </ul>
        </div>
        <div class="text-center mt-4">
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>
