<?php
session_start();
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['administrador']);
include('../configuraciones/bd.php'); // si necesitas conexión aquí también
include('../templates/header_admin.php');
include('../templates/vista_admin.php');
?>
<main>
    <?php include('../secciones/usuarios.php'); ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10">
                <h1 class="text-center">Lista de Usuarios</h1>

                <!-- Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar usuario..."
                                value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="rol" class="form-control">
                                <option value="">Todos los roles</option>
                                <?php
                                $roles = $conexionBD->query("SELECT DISTINCT rol FROM usuarios");
                                foreach ($roles as $rol) {
                                    $selected = (isset($_GET['rol']) && $_GET['rol'] == $rol['rol']) ? 'selected' : '';
                                    echo "<option value='{$rol['rol']}' $selected>{$rol['rol']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="lista_usuarios.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>

                <?php
                // Variables de filtro
                $buscar = $_GET['buscar'] ?? '';
                $rol = $_GET['rol'] ?? '';

                // Paginación
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;

                // Consulta para contar total
                $sqlTotal = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";
                if (!empty($buscar)) $sqlTotal .= " AND (nombre LIKE :buscar OR apellido LIKE :buscar OR email LIKE :buscar)";
                if (!empty($rol)) $sqlTotal .= " AND rol = :rol";

                $stmtTotal = $conexionBD->prepare($sqlTotal);
                if (!empty($buscar)) {
                    $buscarParam = "%$buscar%";
                    $stmtTotal->bindParam(':buscar', $buscarParam, PDO::PARAM_STR);
                }
                if (!empty($rol)) {
                    $stmtTotal->bindParam(':rol', $rol, PDO::PARAM_STR);
                }
                $stmtTotal->execute();
                $totalRegistros = $stmtTotal->fetchColumn();
                $totalPages = ceil($totalRegistros / $limit);

                // Consulta con paginación
                $sql = "SELECT * FROM usuarios WHERE 1=1";
                if (!empty($buscar)) $sql .= " AND (nombre LIKE :buscar OR apellido LIKE :buscar OR email LIKE :buscar)";
                if (!empty($rol)) $sql .= " AND rol = :rol";
                $sql .= " ORDER BY id_usuario ASC LIMIT :offset, :limit";

                $consulta = $conexionBD->prepare($sql);
                if (!empty($buscar)) {
                    $consulta->bindParam(':buscar', $buscarParam, PDO::PARAM_STR);
                }
                if (!empty($rol)) {
                    $consulta->bindParam(':rol', $rol, PDO::PARAM_STR);
                }
                $consulta->bindParam(':offset', $offset, PDO::PARAM_INT);
                $consulta->bindParam(':limit', $limit, PDO::PARAM_INT);
                $consulta->execute();
                $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <!-- Tabla -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Email</th>
                            <th>Contraseña</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <th><?php echo $usuario['id_usuario']; ?></th>
                                    <td><?php echo $usuario['nombre']; ?></td>
                                    <td><?php echo $usuario['apellido']; ?></td>
                                    <td><?php echo $usuario['email']; ?></td>
                                    <td>
                                        <span title="Contraseña protegida 🔒">••••••••</span>
                                    </td>
                                    <td><?php echo $usuario['rol']; ?></td>
                                    <td>
                                        <a href="editar_usuarios.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn btn-info">Seleccionar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron usuarios.</td>
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
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
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
                    <a href="crear_usuarios.php" class="btn btn-success">Agregar Usuario</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>