<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center">Agregar Inventario</h2>

                <form action="inventario.php" method="POST">
                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto</label>
                        <select name="id_producto" id="id_producto" class="form-control" required>
                            <option value="">Seleccione un producto</option>
                            <?php
                            include_once '../configuraciones/bd.php';
                            $conexionBD = BD::crearInstancia();
                            $productos = $conexionBD->query("SELECT id_producto, marca, modelo FROM productos");
                            foreach ($productos as $producto) {
                                echo "<option value='{$producto['id_producto']}'>{$producto['marca']} - {$producto['modelo']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                        <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_salida" class="form-label">Fecha de Salida (Opcional)</label>
                        <input type="date" name="fecha_salida" id="fecha_salida" class="form-control">
                    </div>

                    <input type="hidden" name="accion" value="agregar">

                    <div class="text-center">
                        <button type="submit" class="btn btn-success">Guardar</button>
                        <a href="lista_inventario.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>
