<?php
require_once('../configuraciones/bd.php');
include('../secciones/producto.php');
session_start();
include('../templates/header_admin.php'); 
include('../templates/vista_admin.php'); 
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center mb-4">Crear Producto</h1>
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" name="marca" required>
                    </div>
                    <div class="mb-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="costo_unitario" class="form-label">Costo Unitario</label>
                        <input type="number" step="1" class="form-control" id="costo_unitario" name="costo_unitario" required>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_categoria" class="form-label">Tipo Categoría</label>
                        <select class="form-select" id="nombre_categoria" name="nombre_categoria" required>
                            <option value="">Seleccione un tipo de categoría</option>
                            <?php
                            $categorias = $conexionBD->query("SELECT DISTINCT nombre_categoria FROM CategoriaProducto");
                            foreach ($categorias as $categoria) {
                                $selected = ($categoria['nombre_categoria'] == $nombre_categoria) ? 'selected' : '';
                                echo "<option value='{$categoria['nombre_categoria']}' $selected>{$categoria['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_producto" class="form-label">Tipo Producto</label>
                        <select class="form-select" id="tipo_producto" name="tipo_producto" required>
                            <option value="">Seleccione un tipo de producto</option>
                            <?php
                            $tipos = $conexionBD->query("SELECT DISTINCT tipo_producto FROM CategoriaProducto");
                            foreach ($tipos as $tipo) {
                                $selected = ($tipo['tipo_producto'] == $tipo_producto) ? 'selected' : '';
                                echo "<option value='{$tipo['tipo_producto']}' $selected>{$tipo['tipo_producto']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" name="accion" value="agregar" class="btn btn-primary">Crear Producto</button>
                        <a href="lista_producto.php" class="btn btn-warning">Cancelar</a>
                        <button type="reset" class="btn btn-secondary">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>