<?php
session_start();
require_once('../configuraciones/bd.php');
include('../secciones/clientes.php');
include('../templates/header_cliente.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso('cliente');

// Validación defensiva: asegura que las variables existen
$nombre_cliente = $_SESSION['nombre'] ?? 'Cliente';
$apellido_cliente = $_SESSION['apellido'] ?? '';
$email = $_SESSION['email'] ?? '';
$rut = $_SESSION['rut'] ?? '';
$direccion = $_SESSION['direccion'] ?? '';
$nro_contacto = $_SESSION['nro_contacto'] ?? '';
?>

<main>
    <div class="container mt-5">
        <h1 class="text-center">¡Bienvenid@, <?= htmlspecialchars($nombre_cliente); ?>!</h1>
        <div class="card mt-4">
            <div class="card-header">
                Mis Datos
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Nombre:</strong> <?= htmlspecialchars($nombre_cliente); ?></li>
                <li class="list-group-item"><strong>Apellido:</strong> <?= htmlspecialchars($apellido_cliente); ?></li>
                <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($email); ?></li>
                <li class="list-group-item"><strong>RUT:</strong> <?= htmlspecialchars($rut); ?></li>
                <li class="list-group-item"><strong>Dirección:</strong> <?= htmlspecialchars($direccion); ?></li>
                <li class="list-group-item"><strong>Contacto:</strong> <?= htmlspecialchars($nro_contacto); ?></li>
            </ul>
        </div>
        <div class="mt-4 text-center">
        <td>
            <form action="editar_cuenta.php" method="get">
                <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                    <button type="submit" class="btn btn-info">Editar</button>
                    <a href="../index.php" class="btn btn-danger">Cerrar Sesión</a>
            </form>
        </td>
        </div>
    </div>
</main>

<?php include('../templates/footer.php'); ?>