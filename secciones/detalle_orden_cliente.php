<?php
session_start();
require_once('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['cliente']);

date_default_timezone_set('America/Santiago');
$conexionBD = BD::crearInstancia();

$id_cliente = $_SESSION['id_cliente'] ?? null;
$id_ot = $_GET['id'] ?? null;

if (!$id_cliente || !$id_ot) {
    die("Acceso no autorizado.");
}

// Verificar que la OT pertenezca al cliente
$sql = "SELECT OT.*, 
               Estado_OT.nombre_estado, 
               Usuarios.nombre AS responsable, 
               Clientes.nombre_cliente, 
               Clientes.rut,
               Clientes.email AS cliente_email,
               Clientes.nro_contacto AS cliente_contacto,
               CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00') AS fecha_creacion_local,
               (SELECT descripcion_actividad FROM Detalle_OT WHERE id_ot = OT.id_ot AND id_producto IS NULL LIMIT 1) AS descripcion_actividad
        FROM OT
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
        WHERE OT.id_ot = :id_ot AND OT.id_cliente = :id_cliente";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("No se encontró la orden o no tienes permiso para verla.");
}

// Productos asociados
$sql_productos = "SELECT Productos.marca, Productos.modelo, 
                         Detalle_OT.cantidad, 
                         Productos.costo_unitario, 
                         (Detalle_OT.cantidad * Productos.costo_unitario) AS costo_total_producto
                  FROM Detalle_OT
                  INNER JOIN Productos ON Detalle_OT.id_producto = Productos.id_producto
                  WHERE Detalle_OT.id_ot = :id_ot";
$consulta_productos = $conexionBD->prepare($sql_productos);
$consulta_productos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_productos->execute();
$productos = $consulta_productos->fetchAll(PDO::FETCH_ASSOC);

// Servicios asociados
$sql_servicios = "SELECT s.nombre_servicio, s.costo_servicio
                  FROM Servicios_OT s_ot
                  INNER JOIN Servicios s ON s_ot.id_servicio = s.id_servicio
                  WHERE s_ot.id_ot = :id_ot";
$consulta_servicios = $conexionBD->prepare($sql_servicios);
$consulta_servicios->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_servicios->execute();
$servicios = $consulta_servicios->fetchAll(PDO::FETCH_ASSOC);

// Archivos adjuntos
$sql_archivos = "SELECT * FROM ArchivosAdjuntos_OT WHERE id_ot = :id_ot";
$consulta_archivos = $conexionBD->prepare($sql_archivos);
$consulta_archivos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_archivos->execute();
$archivos = $consulta_archivos->fetchAll(PDO::FETCH_ASSOC);

include('../templates/header_cliente.php');
?>

<div class="container mt-4">
    <h2 class="text-center">Detalle de Orden de Trabajo #<?= htmlspecialchars($orden['id_ot']) ?></h2>

    <div class="card mt-3">
        <div class="card-body">
            <p><strong>Fecha de Creación:</strong> <?= $orden['fecha_creacion_local'] ?></p>
            <p><strong>Estado:</strong> <?= $orden['nombre_estado'] ?></p>
            <p><strong>Responsable:</strong> <?= $orden['responsable'] ?></p>
            <p><strong>Email Cliente:</strong> <?= $orden['cliente_email'] ?></p>
            <p><strong>Contacto:</strong> <?= $orden['cliente_contacto'] ?></p>
            <p><strong>Descripción:</strong> <?= !empty($orden['descripcion_actividad']) ? htmlspecialchars($orden['descripcion_actividad']) : 'No especificada' ?></p>
            <p><strong>Costo Total:</strong> <?= '$' . number_format($orden['costo_total'], 0, ',', '.') ?></p>

            <?php if (!empty($servicios)): ?>
                <h5 class="mt-4">Servicios Asociados</h5>
                <ul>
                    <?php foreach ($servicios as $servicio): ?>
                        <li><?= htmlspecialchars($servicio['nombre_servicio']) ?> - $<?= number_format($servicio['costo_servicio'], 0, ',', '.') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($productos)): ?>
                <h5 class="mt-4">Productos Asociados</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Cantidad</th>
                            <th>Costo Unitario</th>
                            <th>Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['marca']) ?></td>
                                <td><?= htmlspecialchars($producto['modelo']) ?></td>
                                <td><?= $producto['cantidad'] ?></td>
                                <td><?= '$' . number_format($producto['costo_unitario'], 0, ',', '.') ?></td>
                                <td><?= '$' . number_format($producto['costo_total_producto'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($archivos)): ?>
                <h5 class="mt-4">Archivos Adjuntos</h5>
                <ul>
                    <?php foreach ($archivos as $archivo): ?>
                        <li>
                            <a href="<?= $archivo['ruta_archivo'] ?>" target="_blank"><?= basename($archivo['ruta_archivo']) ?></a>
                            (<?= htmlspecialchars($archivo['tipo_archivo']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="lista_ordenes_cliente.php" class="btn btn-secondary mt-3">Volver</a>
        </div>
    </div>
</div>

<?php include('../templates/footer.php'); ?>
