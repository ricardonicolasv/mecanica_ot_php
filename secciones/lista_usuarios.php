<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>
<main>
    <?php include('../secciones/usuarios.php'); ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Lista de Usuarios</h1>

                <!-- Formulario de Búsqueda y Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar usuario..."
                                value="<?php echo $_GET['buscar'] ?? ''; ?>">
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

                <table class="table">
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
                        <?php
                        // Obtener filtros
                        $buscar = $_GET['buscar'] ?? '';
                        $rol = $_GET['rol'] ?? '';

                        // Construir consulta SQL con filtros
                        $sql = "SELECT * FROM usuarios WHERE 1=1";

                        if (!empty($buscar)) {
                            $sql .= " AND (nombre LIKE :buscar OR apellido LIKE :buscar OR email LIKE :buscar)";
                        }
                        if (!empty($rol)) {
                            $sql .= " AND rol = :rol";
                        }

                        $consulta = $conexionBD->prepare($sql);

                        if (!empty($buscar)) {
                            $buscar = "%$buscar%";
                            $consulta->bindParam(':buscar', $buscar, PDO::PARAM_STR);
                        }
                        if (!empty($rol)) {
                            $consulta->bindParam(':rol', $rol, PDO::PARAM_STR);
                        }

                        $consulta->execute();
                        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($usuarios as $usuario) { ?>
                            <tr>
                                <th><?php echo $usuario['id_usuario']; ?></th>
                                <td><?php echo $usuario['nombre']; ?></td>
                                <td><?php echo $usuario['apellido']; ?></td>
                                <td><?php echo $usuario['email']; ?></td>
                                <td><?php echo str_repeat('•', strlen($usuario['password'])); ?></td>
                                <td><?php echo $usuario['rol']; ?></td>
                                <td>
                                    <a href="editar_usuarios.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn btn-info">Seleccionar</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-center mt-4">
                    <a href="crear_usuarios.php" class="btn btn-success">Agregar Usuario</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>