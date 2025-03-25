<?php
session_start();
include('../configuraciones/bd.php');
include('../templates/header_admin.php'); 
include('../templates/vista_admin.php'); 
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
$conexionBD = BD::crearInstancia();

// Obtener filtros
$id_producto = $_GET['id_producto'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$tipo_cambio = $_GET['tipo_cambio'] ?? '';

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Construcción base
$sqlBase = "FROM historial_inventario h 
            JOIN productos p ON h.id_producto = p.id_producto 
            WHERE 1=1";

if (!empty($id_producto)) {
    $sqlBase .= " AND h.id_producto = :id_producto";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sqlBase .= " AND DATE(h.fecha_modificacion) BETWEEN :fecha_inicio AND :fecha_fin";
}
if (!empty($tipo_cambio)) {
    if ($tipo_cambio == 'cantidad') {
        $sqlBase .= " AND h.cantidad_anterior != h.cantidad_nueva";
    } elseif ($tipo_cambio == 'fecha_ingreso') {
        $sqlBase .= " AND h.fecha_ingreso_anterior != h.fecha_ingreso_nueva";
    } elseif ($tipo_cambio == 'fecha_salida') {
        $sqlBase .= " AND h.fecha_salida_anterior != h.fecha_salida_nueva";
    }
}

// Consulta total para paginación
$sqlTotal = "SELECT COUNT(*) as total " . $sqlBase;
$consultaTotal = $conexionBD->prepare($sqlTotal);
if (!empty($id_producto)) {
    $consultaTotal->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $consultaTotal->bindParam(':fecha_inicio', $fecha_inicio);
    $consultaTotal->bindParam(':fecha_fin', $fecha_fin);
}
$consultaTotal->execute();
$totalRecords = $consultaTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Consulta principal con paginación
$sql = "SELECT h.id_historial, h.id_producto, h.cantidad_anterior, h.cantidad_nueva, 
               h.fecha_ingreso_anterior, h.fecha_ingreso_nueva, 
               h.fecha_salida_anterior, h.fecha_salida_nueva, 
               h.fecha_modificacion, h.descripcion, 
               p.marca, p.modelo 
        " . $sqlBase . "
        ORDER BY h.fecha_modificacion DESC
        LIMIT $offset, $limit";

$consulta = $conexionBD->prepare($sql);
if (!empty($id_producto)) {
    $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $consulta->bindParam(':fecha_inicio', $fecha_inicio);
    $consulta->bindParam(':fecha_fin', $fecha_fin);
}
$consulta->execute();
$historial = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Productos para filtro
$productos = $conexionBD->query("SELECT id_producto, marca, modelo FROM productos")->fetchAll(PDO::FETCH_ASSOC);
?>


<main class="container">
    <h1 class="text-center">Historial de Modificaciones</h1>

    <!-- Formulario de Filtros -->
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="id_producto" class="form-label">Producto</label>
                <select name="id_producto" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $producto) : ?>
                        <option value="<?php echo $producto['id_producto']; ?>"
                            <?php echo ($producto['id_producto'] == $id_producto) ? 'selected' : ''; ?>>
                            <?php echo $producto['marca'] . ' ' . $producto['modelo']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="fecha_inicio" class="form-label">Desde</label>
                <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            </div>

            <div class="col-md-2">
                <label for="fecha_fin" class="form-label">Hasta</label>
                <input type="date" class="form-control" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            </div>

            <div class="col-md-2">
                <label for="tipo_cambio" class="form-label">Tipo de Cambio</label>
                <select name="tipo_cambio" class="form-control">
                    <option value="">Todos</option>
                    <option value="cantidad" <?php echo ($tipo_cambio == 'cantidad') ? 'selected' : ''; ?>>Cantidad</option>
                    <option value="fecha_ingreso" <?php echo ($tipo_cambio == 'fecha_ingreso') ? 'selected' : ''; ?>>Fecha de Ingreso</option>
                    <option value="fecha_salida" <?php echo ($tipo_cambio == 'fecha_salida') ? 'selected' : ''; ?>>Fecha de Salida</option>
                </select>
            </div>

            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                <a href="historial_inventario.php" class="btn btn-secondary w-100 ms-2">Limpiar</a>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID Historial</th>
                <th>Producto</th>
                <th>Cantidad Anterior</th>
                <th>Cantidad Nueva</th>
                <th>Fecha Ingreso Anterior</th>
                <th>Fecha Ingreso Nueva</th>
                <th>Fecha Salida Anterior</th>
                <th>Fecha Salida Nueva</th>
                <th>Fecha Modificación</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historial as $registro) {
                // Convertir fechas a solo "YYYY-MM-DD" excepto fecha_modificacion
                $fecha_ingreso_anterior = !empty($registro['fecha_ingreso_anterior']) ? date('Y-m-d', strtotime($registro['fecha_ingreso_anterior'])) : 'N/A';
                $fecha_ingreso_nueva = !empty($registro['fecha_ingreso_nueva']) ? date('Y-m-d', strtotime($registro['fecha_ingreso_nueva'])) : 'N/A';
                $fecha_salida_anterior = !empty($registro['fecha_salida_anterior']) ? date('Y-m-d', strtotime($registro['fecha_salida_anterior'])) : 'N/A';
                $fecha_salida_nueva = !empty($registro['fecha_salida_nueva']) ? date('Y-m-d', strtotime($registro['fecha_salida_nueva'])) : 'N/A';
                $fecha_modificacion = date('Y-m-d H:i:s', strtotime($registro['fecha_modificacion']));

                $descripcion = $registro['descripcion'] ?: "Sin cambios relevantes";
            ?>
                <tr>
                    <td><?php echo $registro['id_historial']; ?></td>
                    <td><?php echo $registro['marca'] . ' ' . $registro['modelo']; ?></td>
                    <td><?php echo $registro['cantidad_anterior']; ?></td>
                    <td><?php echo $registro['cantidad_nueva']; ?></td>
                    <td><?php echo $fecha_ingreso_anterior; ?></td>
                    <td><?php echo $fecha_ingreso_nueva; ?></td>
                    <td><?php echo $fecha_salida_anterior; ?></td>
                    <td><?php echo $fecha_salida_nueva; ?></td>
                    <td><?php echo $fecha_modificacion; ?></td>
                    <td><?php echo $descripcion; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <!-- Controles de paginación -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

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
                if ($key !== 'page') {
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
</main>

<?php include('../templates/footer.php'); ?>