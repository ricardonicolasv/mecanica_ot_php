<?php
session_start();
include('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['supervisor', 'administrador']);
$conexionBD = BD::crearInstancia();

$id_inventario = isset($_GET['id_inventario']) ? intval($_GET['id_inventario']) : 0;

if ($id_inventario == 0) {
    header("Location: lista_inventario.php");
    exit();
}

$sql = "SELECT * FROM inventario WHERE id_inventario = :id_inventario";
$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
$consulta->execute();
$inventario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$inventario) {
    header("Location: lista_inventario.php");
    exit();
}

$productos = $conexionBD->query("SELECT id_producto, marca, modelo FROM productos")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] == 'editar') {
        $id_producto = $_POST['id_producto'];
        $cantidad_nueva = $_POST['cantidad'];
        $fecha_ingreso_nueva = $_POST['fecha_ingreso'];
        $fecha_salida_nueva = $_POST['fecha_salida'];

        try {
            $sql_anterior = "SELECT cantidad, DATE(fecha_ingreso) as fecha_ingreso, DATE(fecha_salida) as fecha_salida 
                             FROM inventario WHERE id_inventario = :id_inventario";
            $consulta_anterior = $conexionBD->prepare($sql_anterior);
            $consulta_anterior->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
            $consulta_anterior->execute();
            $datos_anteriores = $consulta_anterior->fetch(PDO::FETCH_ASSOC);

            $cantidad_anterior = $datos_anteriores['cantidad'];
            $fecha_ingreso_anterior = $datos_anteriores['fecha_ingreso'];
            $fecha_salida_anterior = $datos_anteriores['fecha_salida'];

            // Construcción dinámica de la descripción
            $cambios = [];
            if ($cantidad_anterior != $cantidad_nueva) {
                $cambios[] = "Cantidad de $cantidad_anterior a $cantidad_nueva";
            }
            if ($fecha_ingreso_anterior != $fecha_ingreso_nueva) {
                $cambios[] = "Fecha de ingreso de $fecha_ingreso_anterior a $fecha_ingreso_nueva";
            }
            if ($fecha_salida_anterior != $fecha_salida_nueva) {
                $cambios[] = "Fecha de salida de $fecha_salida_anterior a $fecha_salida_nueva";
            }

            if (!empty($cambios)) {
                $descripcion = "Edición de inventario: " . implode(", ", $cambios);

                $sql_historial = "INSERT INTO historial_inventario (id_inventario, id_producto, cantidad_anterior, cantidad_nueva, 
                                  fecha_ingreso_anterior, fecha_ingreso_nueva, fecha_salida_anterior, fecha_salida_nueva, 
                                  fecha_modificacion, descripcion) 
                                  VALUES (:id_inventario, :id_producto, :cantidad_anterior, :cantidad_nueva, 
                                  :fecha_ingreso_anterior, :fecha_ingreso_nueva, :fecha_salida_anterior, :fecha_salida_nueva, 
                                  NOW(), :descripcion)";
                $consulta_historial = $conexionBD->prepare($sql_historial);
                $consulta_historial->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta_historial->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta_historial->bindParam(':cantidad_anterior', $cantidad_anterior, PDO::PARAM_INT);
                $consulta_historial->bindParam(':cantidad_nueva', $cantidad_nueva, PDO::PARAM_INT);
                $consulta_historial->bindParam(':fecha_ingreso_anterior', $fecha_ingreso_anterior);
                $consulta_historial->bindParam(':fecha_ingreso_nueva', $fecha_ingreso_nueva);
                $consulta_historial->bindParam(':fecha_salida_anterior', $fecha_salida_anterior);
                $consulta_historial->bindParam(':fecha_salida_nueva', $fecha_salida_nueva);
                $consulta_historial->bindParam(':descripcion', $descripcion);
                $consulta_historial->execute();
            }

            $sql = "UPDATE inventario SET id_producto = :id_producto, cantidad = :cantidad, fecha_ingreso = :fecha_ingreso, fecha_salida = :fecha_salida WHERE id_inventario = :id_inventario";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $consulta->bindParam(':cantidad', $cantidad_nueva, PDO::PARAM_INT);
            $consulta->bindParam(':fecha_ingreso', $fecha_ingreso_nueva);
            $consulta->bindParam(':fecha_salida', $fecha_salida_nueva);
            $consulta->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
            $consulta->execute();

            header("Location: lista_inventario.php");
            exit();
        } catch (PDOException $e) {
            echo "Error al actualizar el inventario: " . $e->getMessage();
        }
    }

    // Manejar la acción "borrar" (cambiar estado a "eliminado")
    if (isset($_POST['accion']) && $_POST['accion'] == 'borrar') {
        try {
            $sql_borrar = "UPDATE inventario SET estado = 'eliminado' WHERE id_inventario = :id_inventario";
            $consulta_borrar = $conexionBD->prepare($sql_borrar);
            $consulta_borrar->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
            $consulta_borrar->execute();

            // Registrar el cambio en el historial
            $descripcion = "Producto eliminado del inventario";
            $sql_historial = "INSERT INTO historial_inventario (id_inventario, id_producto, cantidad_anterior, cantidad_nueva, 
                              fecha_modificacion, descripcion) 
                              VALUES (:id_inventario, :id_producto, :cantidad_anterior, 0, NOW(), :descripcion)";
            $consulta_historial = $conexionBD->prepare($sql_historial);
            $consulta_historial->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
            $consulta_historial->bindParam(':id_producto', $inventario['id_producto'], PDO::PARAM_INT);
            $consulta_historial->bindParam(':cantidad_anterior', $inventario['cantidad'], PDO::PARAM_INT);
            $consulta_historial->bindParam(':descripcion', $descripcion);
            $consulta_historial->execute();

            header("Location: lista_inventario.php");
            exit();
        } catch (PDOException $e) {
            echo "Error al eliminar el inventario: " . $e->getMessage();
        }
    }
}
?>
<?php include('../templates/header_admin.php'); ?>
<?php include('../templates/vista_admin.php'); ?>

<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-8">
                <h1 class="text-center">Editar Inventario</h1>

                <form method="POST">
                    <input type="hidden" name="id_inventario" value="<?php echo $inventario['id_inventario']; ?>">

                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto</label>
                        <select name="id_producto" class="form-control" required>
                            <?php foreach ($productos as $producto) : ?>
                                <option value="<?php echo $producto['id_producto']; ?>"
                                    <?php echo ($producto['id_producto'] == $inventario['id_producto']) ? 'selected' : ''; ?>>
                                    <?php echo $producto['marca'] . ' ' . $producto['modelo']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" name="cantidad" value="<?php echo $inventario['cantidad']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                        <input type="date" class="form-control" name="fecha_ingreso" value="<?php echo date('Y-m-d', strtotime($inventario['fecha_ingreso'])); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="fecha_salida" class="form-label">Fecha de Salida</label>
                        <input type="date" class="form-control" name="fecha_salida" value="<?php echo !empty($inventario['fecha_salida']) ? date('Y-m-d', strtotime($inventario['fecha_salida'])) : ''; ?>">
                    </div>

                    <form method="POST">
                        <input type="hidden" name="id_inventario" value="<?php echo $inventario['id_inventario']; ?>">
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="accion" value="editar" class="btn btn-primary">Guardar Cambios</button>
                            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                            <button type="submit" name="accion" value="borrar" class="btn btn-danger" onclick="return confirmarEliminacion();">Borrar de Inventario</button>
                            <?php endif; ?>
                            <a href="lista_inventario.php" class="btn btn-warning">Cancelar</a>
                        </div>
                    </form>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
    function confirmarEliminacion() {
        return confirm('¿Estás seguro de que deseas eliminar este producto del inventario? Esta acción no se puede deshacer.');
    }
</script>

<?php include('../templates/footer.php'); ?>