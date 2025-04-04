<?php
session_start();
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
date_default_timezone_set('America/Santiago');
require_once('../configuraciones/bd.php');
$conexionBD = BD::crearInstancia();

include('../templates/header_admin.php');
include('../templates/vista_admin.php');

// Filtros
$filtro_cliente    = $_GET['filtro_cliente'] ?? '';
$filtro_rut        = $_GET['filtro_rut'] ?? '';
$filtro_responsable = $_GET['filtro_responsable'] ?? '';
$filtro_estado     = $_GET['filtro_estado'] ?? '';
$filtro_fecha_inicio = $_GET['filtro_fecha_inicio'] ?? '';
$filtro_fecha_fin    = $_GET['filtro_fecha_fin'] ?? '';

// Ordenamiento
$orden_por = $_GET['orden_por'] ?? 'id_ot';
$orden     = strtoupper($_GET['orden'] ?? 'DESC');
$orden     = ($orden === 'ASC') ? 'ASC' : 'DESC';

$columnas_permitidas = [
    'id_ot' => 'OT.id_ot',
    'cliente' => 'Clientes.nombre_cliente',
    'rut' => 'Clientes.rut',
    'responsable' => 'Usuarios.nombre',
    'estado' => 'Estado_OT.nombre_estado',
    'fecha' => 'OT.fecha_creacion',
    'costo' => 'OT.costo_total'
];
$columna_orden_sql = $columnas_permitidas[$orden_por] ?? 'OT.id_ot';

// Paginación
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Consulta base
$sqlBase = "FROM OT
    INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
    INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
    INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
    WHERE OT.estado != 'Eliminada'";

if (!empty($filtro_cliente)) {
    $sqlBase .= " AND (Clientes.nombre_cliente LIKE :filtro_cliente OR Clientes.apellido_cliente LIKE :filtro_cliente)";
}
if (!empty($filtro_rut)) {
    $sqlBase .= " AND REPLACE(REPLACE(REPLACE(Clientes.rut, '.', ''), '-', ''), ' ', '') LIKE :filtro_rut";
}
if (!empty($filtro_responsable)) {
    $sqlBase .= " AND (Usuarios.nombre LIKE :filtro_responsable OR Usuarios.apellido LIKE :filtro_responsable)";
}
if (!empty($filtro_estado)) {
    $sqlBase .= " AND Estado_OT.id_estado = :filtro_estado";
}
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $sqlBase .= " AND DATE(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00')) BETWEEN :filtro_fecha_inicio AND :filtro_fecha_fin";
}

$sqlTotal = "SELECT COUNT(*) as total " . $sqlBase;
$consultaTotal = $conexionBD->prepare($sqlTotal);
if (!empty($filtro_cliente)) {
    $buscarCliente = "%$filtro_cliente%";
    $consultaTotal->bindParam(':filtro_cliente', $buscarCliente, PDO::PARAM_STR);
}
if (!empty($filtro_rut)) {
    $buscarRut = "%" . preg_replace('/[^0-9kK]/', '', $filtro_rut) . "%";
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

// Consulta principal
$sql = "SELECT OT.id_ot, Clientes.nombre_cliente, Clientes.apellido_cliente, Clientes.rut,
               Usuarios.nombre AS responsable, Usuarios.apellido AS responsable_apellido, Estado_OT.nombre_estado,
               DATE_FORMAT(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00'), '%Y-%m-%d %H:%i:%s') AS fecha_creacion,
               OT.costo_total
        $sqlBase ORDER BY $columna_orden_sql $orden LIMIT $offset, $limit";

$consulta = $conexionBD->prepare($sql);
if (!empty($filtro_cliente)) $consulta->bindParam(':filtro_cliente', $buscarCliente);
if (!empty($filtro_rut)) $consulta->bindParam(':filtro_rut', $buscarRut);
if (!empty($filtro_responsable)) $consulta->bindParam(':filtro_responsable', $buscarResponsable);
if (!empty($filtro_estado)) $consulta->bindParam(':filtro_estado', $filtro_estado, PDO::PARAM_INT);
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $consulta->bindParam(':filtro_fecha_inicio', $filtro_fecha_inicio);
    $consulta->bindParam(':filtro_fecha_fin', $filtro_fecha_fin);
}
$consulta->execute();
$lista_ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);

$estados = $conexionBD->query("SELECT id_estado, nombre_estado FROM Estado_OT")->fetchAll(PDO::FETCH_ASSOC);

function generarLinkOrden($columna, $texto, $ordenActual, $columnaActual) {
    $icono = '';
    if ($columna === $columnaActual) {
        $icono = $ordenActual === 'ASC' ? '↑' : '↓';
    }
    $nuevoOrden = ($columna === $columnaActual && $ordenActual === 'ASC') ? 'DESC' : 'ASC';
    $query = array_merge($_GET, ['orden_por' => $columna, 'orden' => $nuevoOrden]);
    $url = '?' . http_build_query($query);
    return "<a href=\"$url\">$texto $icono</a>";
}
?>

<main class="container">
    <h1 class="text-center">Reporte de Órdenes de Trabajo</h1>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-2">
                <input type="text" name="filtro_rut" class="form-control" placeholder="Buscar RUT" value="<?= htmlspecialchars($filtro_rut) ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="filtro_cliente" class="form-control" placeholder="Buscar Cliente" value="<?= htmlspecialchars($filtro_cliente) ?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="filtro_responsable" class="form-control" placeholder="Buscar Responsable" value="<?= htmlspecialchars($filtro_responsable) ?>">
            </div>
            <div class="col-md-2">
                <select name="filtro_estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= $estado['id_estado'] ?>" <?= $filtro_estado == $estado['id_estado'] ? 'selected' : '' ?>>
                            <?= $estado['nombre_estado'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="filtro_fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtro_fecha_inicio) ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="filtro_fecha_fin" class="form-control" value="<?= htmlspecialchars($filtro_fecha_fin) ?>">
            </div>
            <div class="col-md-12 mt-2 text-center">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="reportes_ordenes.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="text-end mb-3">
        <a href="reporte_ordenes_pdf.php?<?= http_build_query($_GET) ?>" class="btn btn-success" target="_blank">
            Generar Reporte PDF
        </a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th><?= generarLinkOrden('id_ot', 'ID', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('cliente', 'Cliente', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('rut', 'RUT Cliente', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('responsable', 'Responsable', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('estado', 'Estado', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('fecha', 'Fecha Creación', $orden, $orden_por) ?></th>
                <th><?= generarLinkOrden('costo', 'Costo Total', $orden, $orden_por) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($lista_ordenes): ?>
                <?php foreach ($lista_ordenes as $orden): ?>
                    <tr>
                        <td><?= $orden['id_ot'] ?></td>
                        <td><?= $orden['nombre_cliente'] . ' ' . $orden['apellido_cliente'] ?></td>
                        <td><?= $orden['rut'] ?></td>
                        <td><?= $orden['responsable'] . ' ' . $orden['responsable_apellido'] ?></td>
                        <td><?= $orden['nombre_estado'] ?></td>
                        <td><?= $orden['fecha_creacion'] ?></td>
                        <td><?= '$' . number_format($orden['costo_total'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No se encontraron órdenes.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</main>

<?php include('../templates/footer.php'); ?>
