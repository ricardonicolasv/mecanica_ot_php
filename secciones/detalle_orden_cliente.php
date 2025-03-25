<?php
session_start();
require_once('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['cliente']);

$conexionBD = BD::crearInstancia();
$id_cliente = $_SESSION['id_cliente'] ?? null;
$id_ot = $_GET['id'] ?? null;

if (!$id_cliente || !$id_ot) {
    die("Acceso no autorizado.");
}

$sql = "SELECT OT.*, Estado_OT.nombre_estado, Usuarios.nombre AS responsable
        FROM OT
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        WHERE OT.id_ot = :id_ot AND OT.id_cliente = :id_cliente";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("No se encontró la orden o no tienes permiso para verla.");
}
?>

<?php include('../templates/header_cliente.php'); ?>

<div class="container mt-4">
    <h2>Detalle de Orden de Trabajo #<?= $orden['id_ot'] ?></h2>
    <p><strong>Fecha de Creación:</strong> <?= $orden['fecha_creacion'] ?></p>
    <p><strong>Estado:</strong> <?= $orden['nombre_estado'] ?></p>
    <p><strong>Responsable:</strong> <?= $orden['responsable'] ?></p>
    <p><strong>Descripción:</strong> <?= $orden['descripcion'] ?></p>
    <p><strong>Costo Total:</strong> <?= '$' . number_format($orden['costo_total'], 0, ',', '.') ?></p>

    <!-- Puedes agregar aquí detalles de productos y servicios si lo deseas -->
</div>

<?php include('../templates/footer.php'); ?>
