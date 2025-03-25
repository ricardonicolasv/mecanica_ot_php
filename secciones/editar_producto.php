<?php
session_start();
include_once('../configuraciones/bd.php');
include('../templates/header_admin.php'); 
include('../templates/vista_admin.php'); 
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['supervisor', 'administrador', 'tecnico']);
$conexionBD = BD::crearInstancia();

// Capturar el id_producto desde GET
$id_producto = $_GET['id_producto'] ?? null;

if ($id_producto) {
    try {
        // Obtener datos del producto
        $sql = "SELECT * FROM productos WHERE id_producto = :id_producto";
        $consulta = $conexionBD->prepare($sql);
        $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $consulta->execute();
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            $id_categoria = $producto['id_categoria'];
            $marca = $producto['marca'];
            $modelo = $producto['modelo'];
            $descripcion = $producto['descripcion'];
            $costo_unitario = $producto['costo_unitario'];

            // Obtener la categoría y tipo de producto correspondiente a id_categoria
            $sqlCat = "SELECT nombre_categoria, tipo_producto FROM CategoriaProducto WHERE id_categoria = :id_categoria";
            $consultaCat = $conexionBD->prepare($sqlCat);
            $consultaCat->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $consultaCat->execute();
            $categoria = $consultaCat->fetch(PDO::FETCH_ASSOC);

            $nombre_categoria = $categoria['nombre_categoria'] ?? '';
            $tipo_producto = $categoria['tipo_producto'] ?? '';
        } else {
            echo "Producto no encontrado.";
            exit();
        }
    } catch (PDOException $e) {
        echo "Error al cargar el producto: " . $e->getMessage();
        exit();
    }
} else {
    echo "ID de producto no recibido.";
    exit();
}
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center mb-4">Editar Producto</h1>
                <form action="producto.php" method="post">
                    <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">

                    <div class="mb-3">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" name="marca" value="<?php echo htmlspecialchars($marca); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo htmlspecialchars($modelo); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($descripcion); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="costo_unitario" class="form-label">Costo Unitario (CLP)</label>
                        <input type="number" class="form-control" id="costo_unitario" name="costo_unitario"
                            value="<?php echo intval($costo_unitario); ?>" required>
                        <small class="text-muted">Ingrese el costo en pesos chilenos (sin decimales).</small>
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
                        <button type="submit" name="accion" value="editar" class="btn btn-primary">Guardar Cambios</button>
                        <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <button type="submit" name="accion" value="borrar" class="btn btn-danger">Borrar Producto</button>
                        <?php endif; ?>
                        <a href="lista_producto.php" class="btn btn-warning">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include('../templates/footer.php'); ?>