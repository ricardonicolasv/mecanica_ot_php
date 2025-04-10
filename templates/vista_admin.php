<div class="row">
    <div class="col-12 d-flex justify-content-center">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="ordenTrabajoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Orden de Trabajo
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ordenTrabajoDropdown">
                                <li><a class="dropdown-item" href="crear_orden.php">OT Nueva</a></li>
                                <li><a class="dropdown-item" href="lista_ordenes.php">Lista de OT</a></li>
                                <li><a class="dropdown-item" href="historial_ordenes.php">Historial Ordenes</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="usuariosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Usuarios
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="usuariosDropdown">
                                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                    <li><a class="dropdown-item" href="crear_usuarios.php">Crear Usuario</a></li>
                                    <li><a class="dropdown-item" href="lista_usuarios.php">Lista de Usuarios</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="crear_clientes.php">Crear Cliente</a></li>
                                <li><a class="dropdown-item" href="lista_clientes.php">Lista de Clientes</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="inventarioDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Inventario
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="inventarioDropdown">
                                <li><a class="dropdown-item" href="crear_inventario.php">Agregar Producto</a></li>
                                <li><a class="dropdown-item" href="lista_inventario.php">Ver Inventario</a></li>
                                <li><a class="dropdown-item" href="historial_inventario.php">Ver Historial</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="productosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Productos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="productosDropdown">
                                <li><a class="dropdown-item" href="crear_producto.php">Agregar Producto</a></li>
                                <li><a class="dropdown-item" href="lista_producto.php">Lista de Productos</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Reportes
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="reportesDropdown">
                                <li><a class="dropdown-item" href="reportes_ordenes.php">Generar Reportes</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>