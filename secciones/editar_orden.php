<?php
require_once('../configuraciones/bd.php');
include('../templates/header_admin.php');
include('../templates/vista_admin.php');

$conexionBD = BD::crearInstancia();

$id_ot = isset($_GET['id']) ? $_GET['id'] : '';

if (!$id_ot) {
    die("Error: No se ha proporcionado una Orden de Trabajo válida.");
}

// Obtener datos de la OT antes de mostrar el formulario
$sql = "SELECT OT.*, 
       (SELECT descripcion_actividad 
        FROM Detalle_OT 
        WHERE id_ot = OT.id_ot AND id_producto IS NULL 
        LIMIT 1) AS descripcion_actividad
FROM OT
WHERE OT.id_ot = :id_ot";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

// Obtener productos asociados a la OT
$sql_productos = "SELECT Detalle_OT.id_detalle, Detalle_OT.id_producto, Detalle_OT.cantidad,
                         Productos.marca, Productos.modelo, COALESCE(Productos.costo_unitario, 0) AS costo_unitario
                  FROM Detalle_OT
                  INNER JOIN Productos ON Detalle_OT.id_producto = Productos.id_producto
                  WHERE Detalle_OT.id_ot = :id_ot";
$consulta_productos = $conexionBD->prepare($sql_productos);
$consulta_productos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_productos->execute();
$productos = $consulta_productos->fetchAll(PDO::FETCH_ASSOC);

if (!$orden) {
    die("Error: No se encontró la Orden de Trabajo.");
}

// Obtener listas para los selects
$clientes = $conexionBD->query("SELECT id_cliente, nombre_cliente, email, nro_contacto FROM Clientes")->fetchAll(PDO::FETCH_ASSOC);
$responsables = $conexionBD->query("SELECT id_usuario, nombre FROM Usuarios")->fetchAll(PDO::FETCH_ASSOC);
$estados = $conexionBD->query("SELECT id_estado, nombre_estado FROM Estado_OT")->fetchAll(PDO::FETCH_ASSOC);
$servicios = $conexionBD->query("
    SELECT id_servicio, nombre_servicio, COALESCE(costo_servicio, 0) AS costo_servicio 
    FROM Servicios")->fetchAll(PDO::FETCH_ASSOC);

// Obtener los servicios asociados a la OT (para múltiples registros)
$sql_servicios_ot = "SELECT s_ot.id_servicio_ot, s_ot.id_servicio, s.nombre_servicio, s.costo_servicio 
                     FROM Servicios_OT s_ot
                     INNER JOIN Servicios s ON s_ot.id_servicio = s.id_servicio
                     WHERE s_ot.id_ot = :id_ot";
$consulta_servicios_ot = $conexionBD->prepare($sql_servicios_ot);
$consulta_servicios_ot->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_servicios_ot->execute();
$servicios_ot = $consulta_servicios_ot->fetchAll(PDO::FETCH_ASSOC);

// Obtener costo total de los productos asociados a la OT
$sql_costo_productos = "SELECT SUM(Detalle_OT.cantidad * Productos.costo_unitario) AS total_productos 
                        FROM Detalle_OT 
                        LEFT JOIN Productos ON Detalle_OT.id_producto = Productos.id_producto 
                        WHERE Detalle_OT.id_ot = :id_ot";
$consulta_costo_productos = $conexionBD->prepare($sql_costo_productos);
$consulta_costo_productos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_costo_productos->execute();
$total_productos = $consulta_costo_productos->fetch(PDO::FETCH_ASSOC)['total_productos'] ?? 0;

// Obtener costo total de los servicios asociados a la OT
$total_servicios = 0;
foreach ($servicios_ot as $servicio_asociado) {
    $total_servicios += $servicio_asociado['costo_servicio'];
}

// Calcular el costo total de la OT (productos + servicios)
$costo_total_calculado = $total_productos + $total_servicios;
?>

<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Editar Orden de Trabajo</h1>
                <form action="ordenes_trabajo.php" method="post">
                    <input type="hidden" name="id_ot" value="<?= $id_ot; ?>">
                    <input type="hidden" name="accion" value="editar">

                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="id_cliente" class="form-label">Cliente</label>
                        <select class="form-select" id="id_cliente" name="id_cliente" required onchange="actualizarDatosCliente()">
                            <option value="" disabled>Seleccione un Cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>"
                                    data-email="<?= $cliente['email'] ?>"
                                    data-contacto="<?= $cliente['nro_contacto'] ?>"
                                    <?= ($cliente['id_cliente'] == $orden['id_cliente']) ? 'selected' : '' ?>>
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
                            <option value="" disabled>Seleccione un Responsable</option>
                            <?php foreach ($responsables as $responsable): ?>
                                <option value="<?= $responsable['id_usuario'] ?>"
                                    <?= ($responsable['id_usuario'] == $orden['id_responsable']) ? 'selected' : '' ?>>
                                    <?= $responsable['nombre'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estado -->
                    <div class="mb-3">
                        <label for="id_estado" class="form-label">Estado</label>
                        <select class="form-select" id="id_estado" name="id_estado" required>
                            <option value="" disabled>Seleccione un Estado</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado['id_estado'] ?>"
                                    <?= ($estado['id_estado'] == $orden['id_estado']) ? 'selected' : '' ?>>
                                    <?= $estado['nombre_estado'] ?>
                                </option>
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
                            <?php if (!empty($servicios_ot)): ?>
                                <?php foreach ($servicios_ot as $servicio_asociado): ?>
                                    <tr>
                                        <td>
                                            <!-- Si ya existe un registro, enviamos también su id para luego identificar si se actualiza o se elimina -->
                                            <input type="hidden" name="id_servicio_ot[]" value="<?= htmlspecialchars($servicio_asociado['id_servicio_ot']) ?>">
                                            <select class="form-select" name="id_servicio[]" required onchange="actualizarCostoTotal()">
                                                <option value="" disabled>Seleccione un Tipo de Trabajo</option>
                                                <?php foreach ($servicios as $servicio): ?>
                                                    <option value="<?= htmlspecialchars($servicio['id_servicio']) ?>"
                                                        data-costo="<?= htmlspecialchars($servicio['costo_servicio']) ?>"
                                                        <?= ($servicio['id_servicio'] == $servicio_asociado['id_servicio']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($servicio['nombre_servicio']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
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
                                    <td><button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarServicio()">Agregar Tipo de Trabajo</button>

                    <!-- Costo Total -->
                    <div class="mb-3 mt-3">
                        <label for="costo_total" class="form-label">Costo Total</label>
                        <input type="text" class="form-control" id="costo_total" name="costo_total"
                            value="<?= number_format((float)$costo_total_calculado, 2, ',', '.') ?>" readonly>
                    </div>

                    <!-- Descripción de la OT -->
                    <div class="mb-3">
                        <label for="descripcion_actividad" class="form-label">Descripción de la Orden</label>
                        <textarea class="form-control" id="descripcion_actividad" name="descripcion_actividad" rows="3" required><?= $orden['descripcion_actividad']; ?></textarea>
                    </div>

                    <!-- Sección de Productos Asociados -->
                    <h4>Productos Asociados</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productos_lista">
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="id_detalle[]" value="<?= htmlspecialchars($producto['id_detalle']) ?>">
                                        <select class="form-select" name="id_producto[]" onchange="actualizarCostoTotal()">
                                            <option value="" disabled>Seleccione un Producto</option>
                                            <?php
                                            $consultaTodosProductos = $conexionBD->query("SELECT id_producto, marca, modelo, COALESCE(costo_unitario, 0) AS costo_unitario FROM Productos");
                                            $todosLosProductos = $consultaTodosProductos->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($todosLosProductos as $prod): ?>
                                                <option value="<?= htmlspecialchars($prod['id_producto']) ?>"
                                                    data-costo="<?= htmlspecialchars($prod['costo_unitario']) ?>"
                                                    <?= ($prod['id_producto'] == $producto['id_producto']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prod['marca'] . ' ' . $prod['modelo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="cantidad[]" value="<?= htmlspecialchars($producto['cantidad']) ?>" min="1" onchange="actualizarCostoTotal()">
                                    </td>
                                    <td><button type="button" class="btn btn-danger" onclick="eliminarProducto(this)">Eliminar</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarProducto()">Agregar Producto</button>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-success">Actualizar OT</button>
                        <a href="lista_ordenes.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

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

    // Funciones para Productos
    function agregarProducto() {
        let productosLista = document.getElementById("productos_lista");
        let row = document.createElement("tr");
        row.innerHTML = `
            <td>
                <select class="form-select" name="id_producto[]" onchange="actualizarCostoTotal()">
                    <option value="" disabled selected>Seleccione un Producto</option>
                    <?php
                    $consultaTodosProductos = $conexionBD->query("SELECT id_producto, marca, modelo, COALESCE(costo_unitario, 0) AS costo_unitario FROM Productos");
                    $todosLosProductos = $consultaTodosProductos->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($todosLosProductos as $prod): ?>
                        <option value="<?= htmlspecialchars($prod['id_producto']) ?>"
                            data-costo="<?= htmlspecialchars($prod['costo_unitario']) ?>">
                            <?= htmlspecialchars($prod['marca'] . ' ' . $prod['modelo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="number" class="form-control" name="cantidad[]" min="1" value="1" onchange="actualizarCostoTotal()">
            </td>
            <td>
                <button type="button" class="btn btn-danger" onclick="eliminarProducto(this)">Eliminar</button>
            </td>
        `;
        productosLista.appendChild(row);
        actualizarCostoTotal();
    }

    function eliminarProducto(button) {
        let row = button.closest("tr");
        row.remove();
        actualizarCostoTotal();
    }

    // Funciones para Servicios (Tipos de Trabajo)
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
            let cantidad = parseFloat(row.querySelector("input[name='cantidad[]']").value) || 0;
            let selectProducto = row.querySelector("select[name='id_producto[]']");
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
        document.querySelectorAll("select[name='id_producto[]'], input[name='cantidad[]']").forEach(el => {
            el.addEventListener("change", actualizarCostoTotal);
        });
        document.querySelectorAll("select[name='id_servicio[]']").forEach(el => {
            el.addEventListener("change", actualizarCostoTotal);
        });
        actualizarCostoTotal();
    });
</script>

<?php include('../templates/footer.php'); ?>