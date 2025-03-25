<?php 
session_start();
include('../secciones/clientes.php');
include('../templates/header_admin.php'); 
include('../templates/vista_admin.php'); 
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10">
                <h1 class="text-center">Lista de Clientes</h1>

                <!-- Formulario de Búsqueda -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, apellido o email..."
                                value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="buscar_rut" class="form-control" placeholder="Buscar por RUT..."
                                value="<?php echo htmlspecialchars($_GET['buscar_rut'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="lista_clientes.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>


                <?php
                // Filtros
                $buscar = $_GET['buscar'] ?? '';
                $buscar_rut = $_GET['buscar_rut'] ?? '';


                // Paginación
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                // Consulta para contar total
                $sqlTotal = "SELECT COUNT(*) as total FROM clientes WHERE 1=1";
                if (!empty($buscar)) {
                    $sqlTotal .= " AND (nombre_cliente LIKE :buscar OR apellido_cliente LIKE :buscar OR email LIKE :buscar)";
                }
                if (!empty($buscar_rut)) {
                    $sqlTotal .= " AND rut LIKE :buscar_rut";
                }


                $stmtTotal = $conexionBD->prepare($sqlTotal);
                if (!empty($buscar)) {
                    $buscarParam = "%$buscar%";
                    $stmtTotal->bindParam(':buscar', $buscarParam, PDO::PARAM_STR);
                }
                if (!empty($buscar_rut)) {
                    $buscarRutParam = "%$buscar_rut%";
                    $stmtTotal->bindParam(':buscar_rut', $buscarRutParam, PDO::PARAM_STR);
                }


                $stmtTotal->execute();
                $totalRegistros = $stmtTotal->fetchColumn();
                $totalPages = ceil($totalRegistros / $limit);

                // Consulta con LIMIT y OFFSET
                $sql = "SELECT * FROM clientes WHERE 1=1";
                if (!empty($buscar)) {
                    $sql .= " AND (nombre_cliente LIKE :buscar OR apellido_cliente LIKE :buscar OR email LIKE :buscar)";
                }
                if (!empty($buscar_rut)) {
                    $sql .= " AND rut LIKE :buscar_rut";
                }

                $sql .= " ORDER BY id_cliente DESC LIMIT :offset, :limit";

                $consulta = $conexionBD->prepare($sql);
                if (!empty($buscar)) {
                    $consulta->bindParam(':buscar', $buscarParam, PDO::PARAM_STR);
                }
                if (!empty($buscar_rut)) {
                    $buscarRutParam = "%$buscar_rut%";
                    $consulta->bindParam(':buscar_rut', $buscarRutParam, PDO::PARAM_STR);
                }
                $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);
                $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
                $consulta->execute();
                $clientes = $consulta->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <!-- Tabla -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Rut</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Celular</th>
                            <th>Contraseña</th>
                            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) > 0): ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <th><?php echo $cliente['id_cliente']; ?></th>
                                    <td><?php echo $cliente['nombre_cliente']; ?></td>
                                    <td><?php echo $cliente['apellido_cliente']; ?></td>
                                    <td><?php echo $cliente['rut']; ?></td>
                                    <td><?php echo $cliente['email']; ?></td>
                                    <td><?php echo $cliente['direccion']; ?></td>
                                    <td><?php echo $cliente['nro_contacto']; ?></td>
                                    <td><?php echo str_repeat('•', 10); ?></td>
                                    <td>
                                        <form action="editar_cliente.php" method="get">
                                            <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                                            <?php if (in_array($_SESSION['rol'], ['supervisor', 'administrador'])): ?>
                                            <button type="submit" class="btn btn-info">Seleccionar</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No se encontraron clientes.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <nav>
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
                        <?php foreach ($_GET as $key => $value) {
                            if ($key !== 'page') {
                                echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
                            }
                        } ?>
                        <div class="input-group">
                            <span class="input-group-text">Ir a página</span>
                            <input type="number" name="page" min="1" max="<?= $totalPages ?>" value="<?= $page ?>" class="form-control">
                            <button type="submit" class="btn btn-primary">Ir</button>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <a href="crear_clientes.php" class="btn btn-success">Agregar Cliente</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>