<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>
<main>
    <?php include('../secciones/clientes.php'); ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Lista de Clientes</h1>

                <!-- Formulario de Búsqueda y Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar cliente..."
                                value="<?php echo $_GET['buscar'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="lista_clientes.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>

                <table class="table">
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener filtros
                        $buscar = $_GET['buscar'] ?? '';

                        // Construir consulta SQL con filtros
                        $sql = "SELECT * FROM clientes WHERE 1=1";
                        
                        if (!empty($buscar)) {
                            $sql .= " AND (nombre_cliente LIKE :buscar OR apellido_cliente LIKE :buscar OR email LIKE :buscar OR rut LIKE :buscar)";
                        }

                        $consulta = $conexionBD->prepare($sql);

                        if (!empty($buscar)) {
                            $buscar = "%$buscar%";
                            $consulta->bindParam(':buscar', $buscar, PDO::PARAM_STR);
                        }

                        $consulta->execute();
                        $clientes = $consulta->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($clientes as $cliente) { ?>
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
                                        <button type="submit" class="btn btn-info">Seleccionar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="text-center mt-4">
                    <a href="crear_clientes.php" class="btn btn-success">Agregar Cliente</a>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>
