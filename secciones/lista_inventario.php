<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>
<main>
    <?php include '../secciones/inventario.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Lista de Inventario</h1>

                <!-- Formulario de Búsqueda y Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar producto..."
                                value="<?php echo $_GET['buscar'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="cantidad_min" class="form-control" placeholder="Cantidad mínima"
                                value="<?php echo $_GET['cantidad_min'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="fecha_ingreso" class="form-control"
                                value="<?php echo $_GET['fecha_ingreso'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="fecha_salida" class="form-control"
                                value="<?php echo $_GET['fecha_salida'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="eliminado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'eliminado') ? 'selected' : ''; ?>>Eliminado</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            <a href="lista_inventario.php" class="btn btn-secondary w-100 ms-2">Limpiar</a>
                        </div>
                    </div>
                </form>

                <?php
                // Obtener filtros
                $buscar = $_GET['buscar'] ?? '';
                $cantidad_min = $_GET['cantidad_min'] ?? '';
                $fecha_ingreso = $_GET['fecha_ingreso'] ?? '';
                $fecha_salida = $_GET['fecha_salida'] ?? '';
                $estado = $_GET['estado'] ?? '';

                // Paginación
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                // Consulta base para contar registros
                $sqlBase = "FROM inventario i 
                            LEFT JOIN productos p ON i.id_producto = p.id_producto 
                            WHERE 1=1";

                if ($estado !== '') {
                    $sqlBase .= " AND i.estado = :estado";
                }
                if (!empty($buscar)) {
                    $sqlBase .= " AND (p.marca LIKE :buscar OR p.modelo LIKE :buscar)";
                }
                if (!empty($cantidad_min)) {
                    $sqlBase .= " AND i.cantidad >= :cantidad_min";
                }
                if (!empty($fecha_ingreso)) {
                    $sqlBase .= " AND i.fecha_ingreso = :fecha_ingreso";
                }
                if (!empty($fecha_salida)) {
                    $sqlBase .= " AND i.fecha_salida = :fecha_salida";
                }

                // Consulta para contar
                $sqlTotal = "SELECT COUNT(*) as total " . $sqlBase;
                $stmtTotal = $conexionBD->prepare($sqlTotal);

                if ($estado !== '') {
                    $stmtTotal->bindParam(':estado', $estado);
                }
                if (!empty($buscar)) {
                    $paramBuscar = "%$buscar%";
                    $stmtTotal->bindParam(':buscar', $paramBuscar);
                }
                if (!empty($cantidad_min)) {
                    $stmtTotal->bindParam(':cantidad_min', $cantidad_min);
                }
                if (!empty($fecha_ingreso)) {
                    $stmtTotal->bindParam(':fecha_ingreso', $fecha_ingreso);
                }
                if (!empty($fecha_salida)) {
                    $stmtTotal->bindParam(':fecha_salida', $fecha_salida);
                }

                $stmtTotal->execute();
                $totalRecords = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
                $totalPages = ceil($totalRecords / $limit);

                // Consulta con paginación
                $sql = "SELECT i.id_inventario, p.marca, p.modelo, i.cantidad, i.fecha_ingreso, 
                               i.fecha_salida, i.estado " . $sqlBase . " 
                        ORDER BY i.id_inventario DESC LIMIT $offset, $limit";

                $consulta = $conexionBD->prepare($sql);

                if ($estado !== '') {
                    $consulta->bindParam(':estado', $estado);
                }
                if (!empty($buscar)) {
                    $consulta->bindParam(':buscar', $paramBuscar);
                }
                if (!empty($cantidad_min)) {
                    $consulta->bindParam(':cantidad_min', $cantidad_min);
                }
                if (!empty($fecha_ingreso)) {
                    $consulta->bindParam(':fecha_ingreso', $fecha_ingreso);
                }
                if (!empty($fecha_salida)) {
                    $consulta->bindParam(':fecha_salida', $fecha_salida);
                }

                $consulta->execute();
                $inventario = $consulta->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID Inventario</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha Ingreso</th>
                            <th>Fecha Salida</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventario as $item) { ?>
                            <tr>
                                <td><?php echo $item['id_inventario']; ?></td>
                                <td><?php echo $item['marca'] . ' ' . $item['modelo']; ?></td>
                                <td><?php echo $item['cantidad']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($item['fecha_ingreso'])); ?></td>
                                <td><?php echo !empty($item['fecha_salida']) ? date('Y-m-d', strtotime($item['fecha_salida'])) : ''; ?></td>
                                <td>
                                    <?php echo ($item['estado'] == 'activo') ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Eliminado</span>'; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between">
                                        <a href="editar_inventario.php?id_inventario=<?php echo $item['id_inventario']; ?>" class="btn btn-info me-2">Editar</a>
                                        <?php if ($item['estado'] == 'activo'): ?>
                                            <a href="inventario.php?accion=cambiar_estado&id_inventario=<?php echo $item['id_inventario']; ?>&estado=eliminado"
                                                class="btn btn-danger">Eliminar</a>
                                        <?php else: ?>
                                            <a href="inventario.php?accion=cambiar_estado&id_inventario=<?php echo $item['id_inventario']; ?>&estado=activo"
                                                class="btn btn-success">Restaurar</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <!-- Controles de paginación -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Anterior</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Siguiente</a></li>
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

                <div class="text-center mt-4">
                    <a href="crear_inventario.php" class="btn btn-success">Agregar Inventario</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>
