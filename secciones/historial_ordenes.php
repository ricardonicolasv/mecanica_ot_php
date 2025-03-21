<?php
include('../templates/header_admin.php');
include('../templates/vista_admin.php');
include('../configuraciones/bd.php');

$conexionBD = BD::crearInstancia();

// Pre-cargar mapeos para reemplazar IDs por nombres
$clientesMap = array_column($conexionBD->query("SELECT id_cliente, nombre_cliente FROM Clientes")->fetchAll(PDO::FETCH_ASSOC), 'nombre_cliente', 'id_cliente');
$estadosMap = array_column($conexionBD->query("SELECT id_estado, nombre_estado FROM Estado_OT")->fetchAll(PDO::FETCH_ASSOC), 'nombre_estado', 'id_estado');
$usuariosMap = array_column($conexionBD->query("SELECT id_usuario, nombre FROM Usuarios")->fetchAll(PDO::FETCH_ASSOC), 'nombre', 'id_usuario');
$serviciosMap = array_column($conexionBD->query("SELECT id_servicio, nombre_servicio FROM Servicios")->fetchAll(PDO::FETCH_ASSOC), 'nombre_servicio', 'id_servicio');

// Obtener filtros enviados (si existen)
$ot = $_GET['ot'] ?? ''; // Filtro para Orden de Trabajo
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$campo_modificado = $_GET['campo_modificado'] ?? '';

// Construir la parte base de la consulta SQL con filtros dinámicos
$filtros = "WHERE 1=1";
if (!empty($ot)) {
    $filtros .= " AND h.id_ot LIKE :ot";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $filtros .= " AND DATE(h.fecha_modificacion) BETWEEN :fecha_inicio AND :fecha_fin";
}
if (!empty($campo_modificado)) {
    $filtros .= " AND h.campo_modificado = :campo_modificado";
}

// Consultar TODOS los registros (sin LIMIT) para luego agruparlos en PHP
$sql = "SELECT h.id_historial_ot, h.id_ot, h.campo_modificado, h.valor_anterior, h.valor_nuevo, 
               h.fecha_modificacion, COALESCE(u.nombre, 'Sistema') AS usuario
        FROM historial_ot h
        LEFT JOIN Usuarios u ON h.id_responsable = u.id_usuario
        $filtros
        ORDER BY h.id_historial_ot DESC";
$consulta = $conexionBD->prepare($sql);
if (!empty($ot)) {
    $otParam = "%$ot%";
    $consulta->bindParam(':ot', $otParam, PDO::PARAM_STR);
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $consulta->bindParam(':fecha_inicio', $fecha_inicio);
    $consulta->bindParam(':fecha_fin', $fecha_fin);
}
if (!empty($campo_modificado)) {
    $consulta->bindParam(':campo_modificado', $campo_modificado, PDO::PARAM_STR);
}
$consulta->execute();
$historial = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Agrupar registros por combinación de fecha y usuario (o la llave que necesites)
$grouped = array();
foreach ($historial as $registro) {
    $key = $registro['fecha_modificacion'] . '|' . $registro['usuario'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = array(
            'id_historial' => $registro['id_historial_ot'], // Primer ID del grupo
            'id_ot' => $registro['id_ot'],
            'usuario' => $registro['usuario'],
            'campos' => array(),
            'descripciones' => array(),
            'fecha_modificacion' => $registro['fecha_modificacion']
        );
    }
    // Acumular los campos modificados
    $grouped[$key]['campos'][] = $registro['campo_modificado'];

    // Generar descripción según el campo modificado (ajusta según tus casos)
    switch ($registro['campo_modificado']) {
        case 'Cliente':
            $desc = "Cliente de " 
                . ($registro['nombre_cliente_anterior'] ?? $registro['valor_anterior'])
                . " a " 
                . ($registro['nombre_cliente_nuevo'] ?? $registro['valor_nuevo']);
            break;
        case 'Responsable':
            $desc = "Responsable de " 
                . ($registro['nombre_responsable_anterior'] ?? $registro['valor_anterior'])
                . " a " 
                . ($registro['nombre_responsable_nuevo'] ?? $registro['valor_nuevo']);
            break;
        case 'Estado':
            $desc = "Estado de " 
                . ($registro['nombre_estado_anterior'] ?? $registro['valor_anterior'])
                . " a " 
                . ($registro['nombre_estado_nuevo'] ?? $registro['valor_nuevo']);
            break;
        case 'Tipo de Trabajo':
            $decodedAnterior = json_decode($registro['valor_anterior'], true);
            $decodedNuevo = json_decode($registro['valor_nuevo'], true);
            $textoAnterior = is_array($decodedAnterior) ? implode(', ', $decodedAnterior) : ($serviciosMap[$registro['valor_anterior']] ?? $registro['valor_anterior']);
            $textoNuevo = is_array($decodedNuevo) ? implode(', ', $decodedNuevo) : ($serviciosMap[$registro['valor_nuevo']] ?? $registro['valor_nuevo']);
            $desc = "Tipo de trabajo de " . $textoAnterior . " a " . $textoNuevo;
            break;
        case 'Producto Nuevo':
            $desc = "Producto nuevo: " . $registro['valor_nuevo'];
            break;
        case 'Producto Reemplazado':
            $desc = "Producto reemplazado: de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
            break;
        case 'Producto Eliminado':
            $desc = "Producto eliminado: " . $registro['valor_anterior'];
            break;
        case 'Cantidad Producto':
            $desc = "Cantidad de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
            break;
        case 'Costo Total':
            $desc = "Costo Total de $" . number_format($registro['valor_anterior'], 0, ',', '.')
                . " a $" . number_format($registro['valor_nuevo'], 0, ',', '.');
            break;
        case 'Descripción':
            $desc = "Descripción de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
            break;
        default:
            $desc = $registro['campo_modificado'] . " de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
    }
    $grouped[$key]['descripciones'][] = $desc;
}

// Convertir a arreglo indexado para la paginación
$grouped = array_values($grouped);

// Configuración de la paginación sobre los grupos
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Se mostrarán 10 historias consolidadas por página
$offset = ($page - 1) * $limit;
$totalGroups = count($grouped);
$totalPages = ceil($totalGroups / $limit);

// Obtener solo los grupos para la página actual
$groupedPaginated = array_slice($grouped, $offset, $limit);
?>

<main class="container">
    <h1 class="text-center">Historial de Cambios en Órdenes de Trabajo</h1>

    <!-- Formulario de Filtros -->
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="ot" class="form-label">Orden de Trabajo</label>
                <input type="text" name="ot" class="form-control" placeholder="Buscar OT..." value="<?php echo htmlspecialchars($ot); ?>">
            </div>
            <div class="col-md-2">
                <label for="fecha_inicio" class="form-label">Desde</label>
                <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-2">
                <label for="fecha_fin" class="form-label">Hasta</label>
                <input type="date" class="form-control" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-3">
                <label for="campo_modificado" class="form-label">Campo Modificado</label>
                <?php
                $opcionesCampo = [
                    "Costo total",
                    "Responsable",
                    "Cliente",
                    "Estado",
                    "Descripción",
                    "Tipo de trabajo",
                    "Producto Nuevo",
                    "Producto Reemplazado",
                    "Producto Eliminado",
                    "Producto Total",
                    "Eliminación"
                ];
                ?>
                <select name="campo_modificado" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($opcionesCampo as $opcion): ?>
                        <option value="<?php echo $opcion; ?>" <?php echo ($opcion == $campo_modificado) ? 'selected' : ''; ?>>
                            <?php echo ucfirst($opcion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                <a href="historial_ordenes.php" class="btn btn-secondary w-100 ms-2">Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Tabla de historial consolidado -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID Historial</th>
                <th>ID OT</th>
                <th>Usuario</th>
                <th>Campo Modificado</th>
                <th>Fecha Modificación</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($groupedPaginated)) : ?>
                <?php foreach ($groupedPaginated as $group): ?>
                    <tr>
                        <td><?php echo $group['id_historial']; ?></td>
                        <td><?php echo $group['id_ot']; ?></td>
                        <td><?php echo htmlspecialchars($group['usuario']); ?></td>
                        <td><?php echo implode(", ", array_unique($group['campos'])); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($group['fecha_modificacion'])); ?></td>
                        <td>
                            <ul class="mb-0">
                                <?php foreach ($group['descripciones'] as $desc): ?>
                                    <li><?php echo $desc; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No se encontraron registros.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Controles de paginación -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php
            // Mostrar solo un subconjunto de páginas en lugar de todas
            if ($totalPages <= 10) {
                for ($i = 1; $i <= $totalPages; $i++):
            ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php
                endfor;
            } else {
                // Si estamos en las primeras 5 páginas
                if ($page <= 5) {
                    for ($i = 1; $i <= 5; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php
                    for ($i = $totalPages - 4; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php
                    endfor;
                }
                // Si estamos en las últimas 5 páginas
                elseif ($page > $totalPages - 5) {
                    for ($i = 1; $i <= 5; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php
                    for ($i = $totalPages - 4; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php
                    endfor;
                }
                // Página actual en medio
                else {
                    for ($i = 1; $i <= 2; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php
                    for ($i = $page - 1; $i <= $page + 1; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php
                    for ($i = $totalPages - 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php
                    endfor;
                }
            }
            ?>

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
            // Mantenemos los otros filtros en la paginación
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
</main>

<?php include('../templates/footer.php'); ?>