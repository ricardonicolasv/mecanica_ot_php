<?php
include_once('../configuraciones/bd.php');
session_start();
include('../templates/header_admin.php');
include('../templates/vista_admin.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
$conexionBD = BD::crearInstancia();
// Obtener datos desde BD
$consultaClientes = $conexionBD->prepare("SELECT id_cliente, nombre_cliente, email, nro_contacto FROM Clientes");
$consultaClientes->execute();
$clientes = $consultaClientes->fetchAll(PDO::FETCH_ASSOC);
$consultaUsuarios = $conexionBD->prepare("SELECT id_usuario, nombre FROM Usuarios");
$consultaUsuarios->execute();
$usuarios = $consultaUsuarios->fetchAll(PDO::FETCH_ASSOC);
$consultaEstados = $conexionBD->prepare("SELECT id_estado, nombre_estado FROM Estado_OT");
$consultaEstados->execute();
$estados = $consultaEstados->fetchAll(PDO::FETCH_ASSOC);
$consultaServicios = $conexionBD->prepare("SELECT id_servicio, nombre_servicio, COALESCE(costo_servicio,0) AS costo_servicio FROM Servicios");
$consultaServicios->execute();
$servicios = $consultaServicios->fetchAll(PDO::FETCH_ASSOC);
// Consultar productos una vez para reutilizar
$consultaProductos = $conexionBD->prepare("SELECT p.id_producto, p.marca, p.modelo, p.costo_unitario,
    COALESCE(SUM(i.cantidad), 0) AS stock_disponible 
    FROM Productos p 
    LEFT JOIN Inventario i ON p.id_producto = i.id_producto 
    GROUP BY p.id_producto, p.marca, p.modelo, p.costo_unitario");
$consultaProductos->execute();
$listaProductos = $consultaProductos->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Crear Orden de Trabajo</h1>
                <?php if (isset($_SESSION['error_archivo'])): ?>
                    <div class="alert alert-danger" id="errorArchivo">
                        <?= htmlspecialchars($_SESSION['error_archivo']) ?>
                    </div>
                    <script>
                        document.getElementById("errorArchivo").scrollIntoView({
                            behavior: "smooth"
                        });
                    </script>
                    <?php unset($_SESSION['error_archivo']); ?>
                <?php endif; ?>
                <form action="ordenes_trabajo.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="agregar">
                    <!-- Cliente -->
                    <div class="mb-3">
                        <label for="id_cliente" class="form-label">Cliente</label>
                        <select class="form-select select2" id="id_cliente" name="id_cliente" required onchange="actualizarDatosCliente()">
                            <option value="" selected disabled>Seleccione un Cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>" data-email="<?= $cliente['email'] ?>" data-contacto="<?= $cliente['nro_contacto'] ?>">
                                    <?= $cliente['nombre_cliente'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Botón para crear cliente -->
                        <div class="mt-2" id="btn_crear_cliente" style="display: none;">
                            <a href="crear_clientes.php" class="btn btn-outline-primary btn-sm">➕ Crear nuevo cliente</a>
                        </div>
                    </div>
                    <!-- Info cliente -->
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
                        <select class="form-select select2" id="id_responsable" name="id_responsable" required>
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
                    <!-- Tipos de Trabajo -->
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
                                            <option value="<?= $servicio['id_servicio'] ?>" data-costo="<?= $servicio['costo_servicio'] ?>">
                                                <?= $servicio['nombre_servicio'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarServicio()">Agregar Tipo de Trabajo</button>
                    <!-- Productos -->
                    <h4 class="mt-4">Productos Asociados</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productos_lista"></tbody>
                    </table>
                    <button type="button" class="btn btn-success" onclick="agregarProducto()">Agregar Producto</button>
                    <!-- Costo Total -->
                    <div class="mb-3 mt-3">
                        <label for="costo_total" class="form-label">Costo Total</label>
                        <input type="text" class="form-control" id="costo_total" name="costo_total" readonly>
                    </div>
                    <!-- Descripción -->
                    <div class="mb-3">
                        <label for="descripcion_actividad" class="form-label">Descripción de la Orden</label>
                        <textarea class="form-control" id="descripcion_actividad" name="descripcion_actividad" rows="3" required></textarea>
                    </div>
                    <!-- Archivos -->
                    <div class="mb-3">
                        <label for="archivo_adjunto" class="form-label">Archivos Adjuntos</label>
                        <input class="form-control" type="file" name="archivos_adjuntos[]" id="archivo_adjunto" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    </div>
                    <!-- Botones -->
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">Crear Orden</button>
                        <a href="lista_ordenes.php" class="btn btn-warning">Cancelar</a>
                        <button type="reset" class="btn btn-secondary">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Inicializar Select2 en todos los .select2 excepto #id_cliente
        $('.select2').not('#id_cliente').select2({
            width: '100%',
            placeholder: 'Seleccione una opción',
            allowClear: true
        });
        // Activar foco automático al abrir cualquier Select2
        $(document).on('select2:open', () => {
            document.querySelector('.select2-container--open .select2-search__field')?.focus();
        });
        // Inicializar Select2 en el campo de cliente
        $('#id_cliente').select2({
                width: '100%',
                placeholder: 'Seleccione un Cliente',
                allowClear: true,
                language: {
                    noResults: function() {
                        return `
                <div class="text-center">
                    Cliente no encontrado.<br>
                    <a href="crear_clientes.php" class="btn btn-sm btn-outline-primary mt-2">
                        ➕ Crear nuevo cliente
                    </a>
                </div>
            `;
                    }
                },
                escapeMarkup: function(markup) {
                    return markup; // Permitir HTML en noResults
                }
            })
            .on('select2:select', function() {
                actualizarDatosCliente();
            })
            .on('select2:clear', function() {
                actualizarDatosCliente();
            });
        // Mostrar info del cliente seleccionado
        window.actualizarDatosCliente = function() {
            const cliente = document.querySelector("#id_cliente option:checked");
            document.getElementById("cliente_email").value = cliente?.dataset.email || '';
            document.getElementById("cliente_contacto").value = cliente?.dataset.contacto || '';
        };

        // Agregar producto
        window.agregarProducto = function() {
            const productosLista = document.getElementById("productos_lista");
            const row = document.createElement("tr");

            row.innerHTML = `
        <td>
            <select class="form-select select2" name="productos[]" onchange="actualizarStock(this); actualizarCostoTotal()">
                <option value="" selected disabled>Seleccione un Producto</option>
                <?php foreach ($listaProductos as $producto): ?>
                    <option value="<?= $producto['id_producto'] ?>" data-stock="<?= $producto['stock_disponible'] ?>" data-costo="<?= $producto['costo_unitario'] ?>">
                        <?= $producto['marca'] . ' ' . $producto['modelo'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" class="form-control" name="stock_disponible[]" readonly></td>
        <td><input type="number" class="form-control" name="cantidades[]" min="1" value="1" required oninput="actualizarCostoTotal()"></td>
        <td><button type="button" class="btn btn-danger" onclick="eliminarProducto(this)">Eliminar</button></td>
    `;
            productosLista.appendChild(row);

            // Activar Select2 en el nuevo select
            $(row).find('.select2').select2({
                width: '100%',
                placeholder: 'Seleccione un Producto',
                allowClear: true
            });

            // Enfocar automáticamente el campo de búsqueda del select agregado
            $(row).find('.select2').on('select2:open', () => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            });

            actualizarCostoTotal();
        };
        window.actualizarStock = function(select) {
            const stock = select.options[select.selectedIndex].dataset.stock || '';
            select.closest('tr').querySelector("input[name='stock_disponible[]']").value = stock;
        };

        window.eliminarProducto = function(btn) {
            btn.closest("tr").remove();
            actualizarCostoTotal();
        };
        window.agregarServicio = function() {
            const serviciosLista = document.getElementById("servicios_lista");
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>
                    <select class="form-select" name="id_servicio[]" required onchange="actualizarCostoTotal()">
                        <option value="" disabled selected>Seleccione un Tipo de Trabajo</option>
                        <?php foreach ($servicios as $servicio): ?>
                            <option value="<?= $servicio['id_servicio'] ?>" data-costo="<?= $servicio['costo_servicio'] ?>">
                                <?= $servicio['nombre_servicio'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="button" class="btn btn-danger" onclick="eliminarServicio(this)">Eliminar</button></td>
            `;
            serviciosLista.appendChild(row);
        };

        window.eliminarServicio = function(btn) {
            btn.closest("tr").remove();
            actualizarCostoTotal();
        };
        window.actualizarCostoTotal = function() {
            let total = 0;

            document.querySelectorAll("select[name='productos[]']").forEach((select, i) => {
                const costo = parseFloat(select.options[select.selectedIndex]?.dataset.costo || 0);
                const cantidad = parseFloat(document.querySelectorAll("input[name='cantidades[]']")[i]?.value || 0);
                total += costo * cantidad;
            });

            document.querySelectorAll("select[name='id_servicio[]']").forEach(select => {
                total += parseFloat(select.options[select.selectedIndex]?.dataset.costo || 0);
            });

            document.getElementById("costo_total").value = "$" + Math.round(total).toLocaleString("es-CL");
        };

        // Inicialización automática
        actualizarDatosCliente();
        actualizarCostoTotal();
    });
</script>
<?php include('../templates/footer.php'); ?>