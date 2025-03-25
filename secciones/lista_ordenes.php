<?php
session_start(); // Inicia la sesión
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
date_default_timezone_set('America/Santiago'); // Ajusta según tu ubicación
require_once('../configuraciones/bd.php');
$conexionBD = BD::crearInstancia();

// Procesar eliminación de OT si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == "eliminar") {
    $id_ot = $_POST['id_ot'];
    $id_responsable = $_SESSION['id_usuario'] ?? 1;
    try {
        if (!$conexionBD->inTransaction()) {
            $conexionBD->beginTransaction();
        }

        $sql_update_estado = "UPDATE OT SET estado = 'Eliminada' WHERE id_ot = :id_ot";
        $consulta_ot = $conexionBD->prepare($sql_update_estado);
        $consulta_ot->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
        $consulta_ot->execute();

        $valor_anterior = "Orden de Trabajo #" . $id_ot . " antes de eliminación";
        $sql_insert_hist = "INSERT INTO historial_ot (id_ot, id_responsable, campo_modificado, valor_anterior, valor_nuevo, fecha_modificacion)
                            VALUES (:id_ot, :id_responsable, 'Eliminación', :valor_anterior, 'Eliminada', NOW())";
        $stmt_hist = $conexionBD->prepare($sql_insert_hist);
        $stmt_hist->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
        $stmt_hist->bindParam(':id_responsable', $id_responsable, PDO::PARAM_INT);
        $stmt_hist->bindParam(':valor_anterior', $valor_anterior, PDO::PARAM_STR);
        $stmt_hist->execute();

        $conexionBD->commit();
        header("Location: lista_ordenes.php");
        exit();
    } catch (Exception $e) {
        if ($conexionBD->inTransaction()) {
            $conexionBD->rollBack();
        }
        die("Error al eliminar la orden: " . $e->getMessage());
    }
}
include('../templates/header_admin.php');
include('../templates/vista_admin.php');

// Obtener valores de los filtros
$filtro_cliente    = isset($_GET['filtro_cliente']) ? $_GET['filtro_cliente'] : '';
$filtro_rut        = isset($_GET['filtro_rut']) ? $_GET['filtro_rut'] : '';
$filtro_responsable = isset($_GET['filtro_responsable']) ? $_GET['filtro_responsable'] : '';
$filtro_estado     = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
$filtro_fecha_inicio = isset($_GET['filtro_fecha_inicio']) ? $_GET['filtro_fecha_inicio'] : '';
$filtro_fecha_fin    = isset($_GET['filtro_fecha_fin']) ? $_GET['filtro_fecha_fin'] : '';

// Configuración de la paginación
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Número de registros por página (puedes ajustarlo)
$offset = ($page - 1) * $limit;

// Construir la consulta base (sin LIMIT) para obtener el total de registros
$sqlBase = "FROM OT
    INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
    INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
    INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
    WHERE OT.estado != 'Eliminada'";

if (!empty($filtro_cliente)) {
    $sqlBase .= " AND Clientes.nombre_cliente LIKE :filtro_cliente";
}
if (!empty($filtro_rut)) {
    $sqlBase .= " AND Clientes.rut LIKE :filtro_rut";
}
if (!empty($filtro_responsable)) {
    $sqlBase .= " AND Usuarios.nombre LIKE :filtro_responsable";
}
if (!empty($filtro_estado)) {
    $sqlBase .= " AND Estado_OT.id_estado = :filtro_estado";
}
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $sqlBase .= " AND OT.fecha_creacion BETWEEN :filtro_fecha_inicio AND :filtro_fecha_fin";
}

// Consulta para obtener el total de registros
$sqlTotal = "SELECT COUNT(*) as total " . $sqlBase;
$consultaTotal = $conexionBD->prepare($sqlTotal);
if (!empty($filtro_cliente)) {
    $buscarCliente = "%$filtro_cliente%";
    $consultaTotal->bindParam(':filtro_cliente', $buscarCliente, PDO::PARAM_STR);
}
if (!empty($filtro_rut)) {
    $buscarRut = "%$filtro_rut%";
    $consultaTotal->bindParam(':filtro_rut', $buscarRut, PDO::PARAM_STR);
}
if (!empty($filtro_responsable)) {
    $buscarResponsable = "%$filtro_responsable%";
    $consultaTotal->bindParam(':filtro_responsable', $buscarResponsable, PDO::PARAM_STR);
}
if (!empty($filtro_estado)) {
    $consultaTotal->bindParam(':filtro_estado', $filtro_estado, PDO::PARAM_INT);
}
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $consultaTotal->bindParam(':filtro_fecha_inicio', $filtro_fecha_inicio);
    $consultaTotal->bindParam(':filtro_fecha_fin', $filtro_fecha_fin);
}
$consultaTotal->execute();
$totalRecords = $consultaTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Construir la consulta principal con LIMIT
$sql = "SELECT OT.id_ot, Clientes.nombre_cliente, Clientes.rut, Usuarios.nombre AS responsable, 
               Estado_OT.nombre_estado, 
               DATE_FORMAT(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00'), '%Y-%m-%d %H:%i:%s') AS fecha_creacion, 
               OT.costo_total
        " . $sqlBase . " ORDER BY OT.id_ot DESC LIMIT $offset, $limit";

$consulta = $conexionBD->prepare($sql);
if (!empty($filtro_cliente)) {
    $consulta->bindParam(':filtro_cliente', $buscarCliente, PDO::PARAM_STR);
}
if (!empty($filtro_rut)) {
    $consulta->bindParam(':filtro_rut', $buscarRut, PDO::PARAM_STR);
}
if (!empty($filtro_responsable)) {
    $consulta->bindParam(':filtro_responsable', $buscarResponsable, PDO::PARAM_STR);
}
if (!empty($filtro_estado)) {
    $consulta->bindParam(':filtro_estado', $filtro_estado, PDO::PARAM_INT);
}
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $consulta->bindParam(':filtro_fecha_inicio', $filtro_fecha_inicio);
    $consulta->bindParam(':filtro_fecha_fin', $filtro_fecha_fin);
}
$consulta->execute();
$lista_ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Obtener listas para los filtros desplegables
$estados = $conexionBD->query("SELECT id_estado, nombre_estado FROM Estado_OT")->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container">
        <h1 class="text-center">Órdenes de Trabajo</h1>

        <!-- Formulario de Filtros -->
        <form method="GET" class="mb-4">
            <div class="row">
                <!-- Campo para buscar por RUT -->
                <div class="col-md-2">
                    <input type="text" name="filtro_rut" class="form-control" placeholder="Buscar por RUT" value="<?php echo htmlspecialchars($filtro_rut); ?>">
                </div>
                <!-- Campo para buscar por Cliente -->
                <div class="col-md-2">
                    <input type="text" name="filtro_cliente" class="form-control" placeholder="Buscar por Cliente" value="<?php echo htmlspecialchars($filtro_cliente); ?>">
                </div>
                <!-- Filtro por Responsable -->
                <div class="col-md-2">
                    <input type="text" name="filtro_responsable" class="form-control" placeholder="Buscar Responsable" value="<?= htmlspecialchars($filtro_responsable); ?>">
                </div>
                <!-- Filtro por Estado -->
                <div class="col-md-2">
                    <select name="filtro_estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado):
                            $selected = ($filtro_estado == $estado['id_estado']) ? 'selected' : '';
                        ?>
                            <option value="<?= $estado['id_estado'] ?>" <?= $selected ?>><?= $estado['nombre_estado'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Filtros de fecha -->
                <div class="col-md-2">
                    <input type="date" name="filtro_fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="filtro_fecha_fin" class="form-control" value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                </div>
                <!-- Botones -->
                <div class="col-md-12 mt-2 text-center">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="lista_ordenes.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>

        <!-- Tabla de Órdenes -->
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>RUT Cliente</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Costo Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lista_ordenes)): ?>
                    <?php foreach ($lista_ordenes as $orden): ?>
                        <tr>
                            <td>
                                <a href="detalle_orden.php?id=<?= $orden['id_ot'] ?>" class="text-decoration-none">
                                    <?= $orden['id_ot'] ?>
                                </a>
                            </td>
                            <td><?= $orden['nombre_cliente'] ?></td>
                            <td><?= $orden['rut'] ?></td>
                            <td><?= $orden['responsable'] ?></td>
                            <td>
                                <?php if ($orden['nombre_estado'] === "Completada"): ?>
                                    <!-- Se muestra un badge con el ícono de ticket para las OT completadas -->
                                    <span class="badge bg-info">
                                        <i class="fa fa-ticket"></i> Completada
                                    </span>
                                <?php else: ?>
                                    <?php echo $orden['nombre_estado']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $orden['fecha_creacion'] ?></td>
                            <td><?php echo '$' . number_format($orden['costo_total'], 0, ',', '.'); ?></td>
                            <td>
                                <a href="editar_orden.php?id=<?= $orden['id_ot'] ?>" class="btn btn-info">Editar</a>
                                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                    <form action="lista_ordenes.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacion()">
                                        <input type="hidden" name="id_ot" value="<?= $orden['id_ot'] ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No se encontraron órdenes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Controles de paginación -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <!-- Botón "Anterior" -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a>
                    </li>
                <?php endif; ?>

                <?php
                // Si el total de páginas es menor o igual a 10 se muestran todas
                if ($totalPages <= 10) {
                    for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor;
                } else {
                    if ($page <= 5) {
                        for ($i = 1; $i <= 5; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php for ($i = $totalPages - 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor;
                    } elseif ($page > $totalPages - 4) {
                        for ($i = 1; $i <= 2; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php for ($i = $totalPages - 4; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor;
                    } else {
                        ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php
                        for ($i = $page - 1; $i <= $page + 1; $i++):
                            if ($i > 1 && $i < $totalPages): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                        <?php endif;
                        endfor;
                        ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a>
                        </li>
                <?php }
                }
                ?>

                <!-- Botón "Siguiente" -->
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Siguiente</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Buscador de página -->
        <div class="d-flex justify-content-center mt-3">
            <form method="GET" class="form-inline">
                <?php
                foreach ($_GET as $key => $value) {
                    if ($key !== 'page' && $key !== 'buscador') {
                        echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
                    }
                }
                ?>
                <div class="input-group">
                    <span class="input-group-text">Ir a página</span>
                    <input type="number" name="page" min="1" max="<?php echo $totalPages; ?>" value="<?php echo $page; ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">Ir</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function confirmarEliminacion() {
        return confirm("¿Estás seguro de que deseas eliminar esta Orden de Trabajo?");
    }
</script>

<?php include('../templates/footer.php'); ?>