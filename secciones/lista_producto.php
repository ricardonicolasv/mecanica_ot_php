<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>
<main>
    <?php include '../secciones/producto.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10">
                <h1 class="text-center">Lista de Productos</h1>

                <!-- Formulario de Búsqueda y Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar producto..."
                                value="<?php echo $_GET['buscar'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="categoria" class="form-control">
                                <option value="">Todas las categorías</option>
                                <?php
                                $categorias = $conexionBD->query("SELECT DISTINCT nombre_categoria FROM CategoriaProducto");
                                foreach ($categorias as $cat) {
                                    $selected = ($_GET['categoria'] ?? '') == $cat['nombre_categoria'] ? 'selected' : '';
                                    echo "<option value='{$cat['nombre_categoria']}' $selected>{$cat['nombre_categoria']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="tipo_producto" class="form-control">
                                <option value="">Todos los tipos de producto</option>
                                <?php
                                $tipos = $conexionBD->query("SELECT DISTINCT tipo_producto FROM CategoriaProducto");
                                foreach ($tipos as $tipo) {
                                    $selected = ($_GET['tipo_producto'] ?? '') == $tipo['tipo_producto'] ? 'selected' : '';
                                    echo "<option value='{$tipo['tipo_producto']}' $selected>{$tipo['tipo_producto']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="lista_producto.php" class="btn btn-secondary ms-2">Limpiar</a>
                        </div>
                    </div>
                </form>

                <?php
                // Filtros
                $buscar = $_GET['buscar'] ?? '';
                $categoria = $_GET['categoria'] ?? '';
                $tipo_producto = $_GET['tipo_producto'] ?? '';

                // Paginación
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                // Consulta base
                $sqlBase = "FROM Productos p 
                            INNER JOIN CategoriaProducto cp ON p.id_categoria = cp.id_categoria 
                            WHERE 1=1";

                if (!empty($buscar)) {
                    $sqlBase .= " AND (p.marca LIKE :buscar OR p.modelo LIKE :buscar OR p.descripcion LIKE :buscar)";
                }
                if (!empty($categoria)) {
                    $sqlBase .= " AND cp.nombre_categoria = :categoria";
                }
                if (!empty($tipo_producto)) {
                    $sqlBase .= " AND cp.tipo_producto = :tipo_producto";
                }

                // Consulta para contar total
                $sqlTotal = "SELECT COUNT(*) as total " . $sqlBase;
                $stmtTotal = $conexionBD->prepare($sqlTotal);

                if (!empty($buscar)) {
                    $buscarParam = "%$buscar%";
                    $stmtTotal->bindParam(':buscar', $buscarParam);
                }
                if (!empty($categoria)) {
                    $stmtTotal->bindParam(':categoria', $categoria);
                }
                if (!empty($tipo_producto)) {
                    $stmtTotal->bindParam(':tipo_producto', $tipo_producto);
                }

                $stmtTotal->execute();
                $totalRecords = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
                $totalPages = ceil($totalRecords / $limit);

                // Consulta principal con LIMIT
                $sql = "SELECT p.*, cp.nombre_categoria, cp.tipo_producto 
                        " . $sqlBase . " 
                        ORDER BY p.id_producto DESC 
                        LIMIT $offset, $limit";

                $consulta = $conexionBD->prepare($sql);

                if (!empty($buscar)) {
                    $consulta->bindParam(':buscar', $buscarParam);
                }
                if (!empty($categoria)) {
                    $consulta->bindParam(':categoria', $categoria);
                }
                if (!empty($tipo_producto)) {
                    $consulta->bindParam(':tipo_producto', $tipo_producto);
                }

                $consulta->execute();
                $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Descripción</th>
                            <th>Costo Unitario</th>
                            <th>Categoría</th>
                            <th>Tipo Producto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= $producto['id_producto']; ?></td>
                                <td><?= $producto['marca']; ?></td>
                                <td><?= $producto['modelo']; ?></td>
                                <td><?= $producto['descripcion']; ?></td>
                                <td><?= '$' . number_format($producto['costo_unitario'], 0, ',', '.'); ?></td>
                                <td><?= $producto['nombre_categoria']; ?></td>
                                <td><?= $producto['tipo_producto']; ?></td>
                                <td>
                                    <a href="editar_producto.php?id_producto=<?= $producto['id_producto']; ?>" class="btn btn-info">Seleccionar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Controles de paginación -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente</a>
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
                            <input type="number" name="page" min="1" max="<?= $totalPages ?>" value="<?= $page ?>" class="form-control">
                            <button type="submit" class="btn btn-primary">Ir</button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <a href="crear_producto.php" class="btn btn-success">Agregar Producto</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>