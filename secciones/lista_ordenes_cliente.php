<?php
session_start();
require_once('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['cliente']); // Solo clientes

date_default_timezone_set('America/Santiago');
$conexionBD = BD::crearInstancia();

// Obtener ID del cliente desde la sesión
$id_cliente = $_SESSION['id_cliente'] ?? null;
if (!$id_cliente) {
    die("Acceso no autorizado.");
}

// Paginación
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Obtener total de órdenes para este cliente
$sqlTotal = "SELECT COUNT(*) as total FROM OT WHERE estado != 'Eliminada' AND id_cliente = :id_cliente";
$consultaTotal = $conexionBD->prepare($sqlTotal);
$consultaTotal->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
$consultaTotal->execute();
$totalRecords = $consultaTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Obtener órdenes del cliente
$sql = "SELECT OT.id_ot, 
               Estado_OT.nombre_estado,
               DATE_FORMAT(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00'), '%Y-%m-%d %H:%i:%s') AS fecha_creacion, 
               OT.costo_total,
               Usuarios.nombre AS responsable
        FROM OT
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        WHERE OT.estado != 'Eliminada' AND OT.id_cliente = :id_cliente
        ORDER BY OT.id_ot DESC
        LIMIT $offset, $limit";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
$consulta->execute();
$ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../templates/header_cliente.php'); ?>

<div class="container mt-5">
    <h2 class="text-center">Mis Órdenes de Trabajo</h2>

    <?php if (!empty($ordenes)): ?>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Costo Total</th>
                    <th>Ver Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ordenes as $orden): ?>
                    <tr>
                        <td><?= $orden['id_ot'] ?></td>
                        <td><?= $orden['responsable'] ?></td>
                        <td><?= $orden['nombre_estado'] ?></td>
                        <td><?= $orden['fecha_creacion'] ?></td>
                        <td>$<?= number_format($orden['costo_total'], 0, ',', '.') ?></td>
                        <td>
                            <a href="detalle_orden.php?id=<?= $orden['id_ot'] ?>" class="btn btn-sm btn-primary">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Anterior</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Siguiente</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-info text-center">No tienes órdenes de trabajo registradas.</div>
    <?php endif; ?>
</div>

<?php include('../templates/footer.php'); ?>
