<?php
include_once('../configuraciones/bd.php');
include('../templates/header_admin.php');
include('../templates/vista_admin.php');

$conexionBD = BD::crearInstancia();

// Obtener lista de clientes
$consultaClientes = $conexionBD->prepare("SELECT id_cliente, nombre_cliente, email, nro_contacto FROM Clientes");
$consultaClientes->execute();
$clientes = $consultaClientes->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de usuarios responsables
$consultaUsuarios = $conexionBD->prepare("SELECT id_usuario, nombre FROM Usuarios");
$consultaUsuarios->execute();
$usuarios = $consultaUsuarios->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de estados de OT
$consultaEstados = $conexionBD->prepare("SELECT id_estado, nombre_estado FROM Estado_OT");
$consultaEstados->execute();
$estados = $consultaEstados->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de tipos de trabajo y servicios
$consultaServicios = $conexionBD->prepare("SELECT id_servicio, nombre_servicio, COALESCE(costo_servicio,0) AS costo_servicio FROM Servicios");
$consultaServicios->execute();
$servicios = $consultaServicios->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Crear Orden de Trabajo</h1>
                <form action="ordenes_trabajo.php" method="post">
                    <!-- Enviar acción 'agregar' para crear la orden -->
                    <input type="hidden" name="accion" value="agregar">

                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="id_cliente" class="form-label">Cliente</label>
                        <select class="form-select" id="id_cliente" name="id_cliente" required onchange="actualizarDatosCliente()">
                            <option value="" selected disabled>Seleccione un Cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>"
                                    data-email="<?= $cliente['email'] ?>"
                                    data-contacto="<?= $cliente['nro_contacto'] ?>">
                                    <?= $cliente['nombre_cliente'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Información del Cliente -->
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico:</label>
                        <input type="text" id="cliente_email" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de Contacto:</label>
                        <input type="text" id="cliente_contacto" class="form-control" readonly>
                    </div>

                    <!-- Responsable -->
                    <div class="mb-3">
                        <label for="id_responsable" class="form-label">Responsable</label>
                        <select class="form-select" id="id_responsable" name="id_responsable" required>
                            <option value="" selected disabled>Seleccione un Responsable</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id_usuario'] ?>"><?= $usuario['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estado -->
                    <div class="mb-3">
                        <label for="id_estado" class="form-label">Estado</label>
                        <select class="form-select" id="id_estado" name="id_estado" required>
                            <option value="" selected disabled>Seleccione un Estado</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['id_estado'] ?>"><?= $estado['nombre_estado'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sección de Tipos de Trabajo -->
                    <h4>Tipos de Trabajo</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tipo de Trabajo</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="servicios_lista">
                            <tr>
                                <td>
                                    <select class="form-select" name="id_servicio[]" required onchange="actualizarCostoTotal()">
                                        <option value="" disabled selected>Seleccione un Tipo de Trabajo</option>
                                        <?php foreach ($servicios as $servicio): ?>
                                            <option value="<?= htmlspecialchars($servicio['id_servicio']) ?>" data-costo="<?= htmlspecialchars($servicio['costo_servicio']) ?>">
                                                <?= htmlspecialchars($servicio['nombre_servicio']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarServicio()">Agregar Tipo de Trabajo</button>

                    <!-- Sección de Productos Asociados -->
                    <h4 class="mt-4">Productos Asociados</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock Disponible</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productos_lista">
                            <!-- Se agregarán dinámicamente -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarProducto()">Agregar Producto</button>

                    <!-- Costo Total (calculado en base a productos y servicios) -->
                    <div class="mb-3 mt-3">
                        <label for="costo_total" class="form-label">Costo Total</label>
                        <input type="text" class="form-control" id="costo_total" name="costo_total" readonly>
                    </div>

                    <!-- Descripción de la OT -->
                    <div class="mb-3">
                        <label for="descripcion_actividad" class="form-label">Descripción de la Orden</label>
                        <textarea class="form-control" id="descripcion_actividad" name="descripcion_actividad" rows="3" required></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">Crear Orden</button>
                        <button type="reset" class="btn btn-secondary">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- JavaScript -->
<script>
    function actualizarDatosCliente() {
        let select = document.getElementById("id_cliente");
        let emailInput = document.getElementById("cliente_email");
        let contactoInput = document.getElementById("cliente_contacto");

        let selectedOption = select.options[select.selectedIndex];
        if (selectedOption) {
            emailInput.value = selectedOption.getAttribute("data-email") || "";
            contactoInput.value = selectedOption.getAttribute("data-contacto") || "";
        }
    }

    // Funciones para agregar/quitar Productos
    function agregarProducto() {
        let productosLista = document.getElementById("productos_lista");
        let row = document.createElement("tr");
        row.innerHTML = `
            <td>
                <select class="form-select" name="productos[]" onchange="actualizarStock(this)">
                    <option value="" selected disabled>Seleccione un Producto</option>
                    <?php
                    $consultaProductos = $conexionBD->prepare("SELECT p.id_producto, p.marca, p.modelo, p.costo_unitario,
                        COALESCE(SUM(i.cantidad), 0) AS stock_disponible 
                        FROM Productos p 
                        LEFT JOIN Inventario i ON p.id_producto = i.id_producto 
                        GROUP BY p.id_producto, p.marca, p.modelo, p.costo_unitario");
                    $consultaProductos->execute();
                    $listaProductos = $consultaProductos->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($listaProductos as $producto) {
                        echo '<option value="' . $producto['id_producto'] . '" data-stock="' . $producto['stock_disponible'] . '" data-costo="' . $producto['costo_unitario'] . '">'
                            . $producto['marca'] . ' ' . $producto['modelo'] . '</option>';
                    }
                    ?>
                </select>
            </td>
            <td><input type="text" class="form-control" name="stock_disponible[]" readonly></td>
            <td><input type="number" class="form-control" name="cantidades[]" min="1" value="1" required></td>
            <td><button type="button" class="btn btn-danger" onclick="eliminarProducto(this)">Eliminar</button></td>
        `;
        productosLista.appendChild(row);
        actualizarCostoTotal();
    }

    function actualizarStock(select) {
        let stockInput = select.parentElement.nextElementSibling.children[0];
        let stock = select.options[select.selectedIndex].getAttribute("data-stock");
        stockInput.value = stock;
    }

    function eliminarProducto(button) {
        let row = button.closest("tr");
        row.remove();
        actualizarCostoTotal();
    }

    // Funciones para agregar/quitar Servicios (Tipos de Trabajo)
    function agregarServicio() {
        let serviciosLista = document.getElementById("servicios_lista");
        let row = document.createElement("tr");
        row.innerHTML = `
            <td>
                <select class="form-select" name="id_servicio[]" required onchange="actualizarCostoTotal()">
                    <option value="" disabled selected>Seleccione un Tipo de Trabajo</option>
                    <?php foreach ($servicios as $servicio): ?>
                        <option value="<?= htmlspecialchars($servicio['id_servicio']) ?>" data-costo="<?= htmlspecialchars($servicio['costo_servicio']) ?>">
                            <?= htmlspecialchars($servicio['nombre_servicio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button></td>
        `;
        serviciosLista.appendChild(row);
        actualizarCostoTotal();
    }

    function eliminarServicio(button) {
        let row = button.closest("tr");
        row.remove();
        actualizarCostoTotal();
    }

    // Función para actualizar el costo total (productos + servicios)
    function actualizarCostoTotal() {
        let totalProductos = 0;
        document.querySelectorAll("tbody#productos_lista tr").forEach(row => {
            let cantidad = parseFloat(row.querySelector("input[name='cantidades[]']").value) || 0;
            let selectProducto = row.querySelector("select[name='productos[]']");
            let costoUnitario = parseFloat(selectProducto.selectedOptions[0]?.getAttribute("data-costo")) || 0;
            totalProductos += cantidad * costoUnitario;
        });
        
        let totalServicios = 0;
        document.querySelectorAll("tbody#servicios_lista tr").forEach(row => {
            let selectServicio = row.querySelector("select[name='id_servicio[]']");
            let costoServicio = parseFloat(selectServicio.selectedOptions[0]?.getAttribute("data-costo")) || 0;
            totalServicios += costoServicio;
        });
        
        let costoTotal = totalProductos + totalServicios;
        document.getElementById("costo_total").value = costoTotal.toFixed(2);
    }

    document.addEventListener("DOMContentLoaded", function() {
        actualizarDatosCliente();
        document.getElementById("id_estado").addEventListener("change", actualizarCostoTotal);
        // Agregar eventos para recalcular costo al cambiar productos y servicios
        document.querySelectorAll("select[name='productos[]'], input[name='cantidades[]']").forEach(el => {
            el.addEventListener("change", actualizarCostoTotal);
        });
        document.querySelectorAll("select[name='id_servicio[]']").forEach(el => {
            el.addEventListener("change", actualizarCostoTotal);
        });
        actualizarCostoTotal();
    });
</script>

<?php include('../templates/footer.php'); ?>
