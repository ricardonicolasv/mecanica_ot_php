<?php 
include_once '../configuraciones/bd.php';
$conexionBD = BD::crearInstancia();

$id_producto = isset($_POST['id_producto']) ? trim($_POST['id_producto']) : '';
$marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
$modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$nombre_categoria = isset($_POST['nombre_categoria']) ? trim($_POST['nombre_categoria']) : '';
$tipo_producto = isset($_POST['tipo_producto']) ? trim($_POST['tipo_producto']) : '';
$costo_unitario = isset($_POST['costo_unitario']) ? intval($_POST['costo_unitario']) : 0; // Convertir a entero

$accion = $_POST['accion'] ?? '';

if ($accion != '') {

    // Validación común del costo unitario
    if (in_array($accion, ['agregar', 'editar'])) {
        if ($costo_unitario < 1) {
            echo "<script>
                    alert('El costo unitario debe ser un número positivo mayor o igual a 1.');
                    window.history.back();
                  </script>";
            exit();
        }
    }

    switch ($accion) {
        case 'agregar':
            try {
                $sqlCat = "SELECT id_categoria FROM CategoriaProducto WHERE nombre_categoria = :nombre_categoria AND tipo_producto = :tipo_producto";
                $consultaCat = $conexionBD->prepare($sqlCat);
                $consultaCat->bindParam(':nombre_categoria', $nombre_categoria);
                $consultaCat->bindParam(':tipo_producto', $tipo_producto);
                $consultaCat->execute();
                $categoria = $consultaCat->fetch(PDO::FETCH_ASSOC);

                if (!$categoria) {
                    $sqlInsertCat = "INSERT INTO CategoriaProducto (nombre_categoria, tipo_producto) VALUES (:nombre_categoria, :tipo_producto)";
                    $consultaInsertCat = $conexionBD->prepare($sqlInsertCat);
                    $consultaInsertCat->bindParam(':nombre_categoria', $nombre_categoria);
                    $consultaInsertCat->bindParam(':tipo_producto', $tipo_producto);
                    $consultaInsertCat->execute();
                    $id_categoria = $conexionBD->lastInsertId();
                } else {
                    $id_categoria = $categoria['id_categoria'];
                }

                $sql = "INSERT INTO productos (id_categoria, marca, modelo, descripcion, costo_unitario) 
                        VALUES (:id_categoria, :marca, :modelo, :descripcion, :costo_unitario)";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
                $consulta->bindParam(':marca', $marca);
                $consulta->bindParam(':modelo', $modelo);
                $consulta->bindParam(':descripcion', $descripcion);
                $consulta->bindParam(':costo_unitario', $costo_unitario, PDO::PARAM_INT);
                $consulta->execute();
                header("Location: lista_producto.php");
                exit();
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
            break;

        case 'editar':
            try {
                $sqlCat = "SELECT id_categoria FROM CategoriaProducto WHERE nombre_categoria = :nombre_categoria AND tipo_producto = :tipo_producto";
                $consultaCat = $conexionBD->prepare($sqlCat);
                $consultaCat->bindParam(':nombre_categoria', $nombre_categoria);
                $consultaCat->bindParam(':tipo_producto', $tipo_producto);
                $consultaCat->execute();
                $categoria = $consultaCat->fetch(PDO::FETCH_ASSOC);

                if (!$categoria) {
                    $sqlInsertCat = "INSERT INTO CategoriaProducto (nombre_categoria, tipo_producto) VALUES (:nombre_categoria, :tipo_producto)";
                    $consultaInsertCat = $conexionBD->prepare($sqlInsertCat);
                    $consultaInsertCat->bindParam(':nombre_categoria', $nombre_categoria);
                    $consultaInsertCat->bindParam(':tipo_producto', $tipo_producto);
                    $consultaInsertCat->execute();
                    $id_categoria = $conexionBD->lastInsertId();
                } else {
                    $id_categoria = $categoria['id_categoria'];
                }

                $sql = "UPDATE productos 
                        SET id_categoria = :id_categoria, 
                            marca = :marca, 
                            modelo = :modelo, 
                            descripcion = :descripcion, 
                            costo_unitario = :costo_unitario 
                        WHERE id_producto = :id_producto";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
                $consulta->bindParam(':marca', $marca);
                $consulta->bindParam(':modelo', $modelo);
                $consulta->bindParam(':descripcion', $descripcion);
                $consulta->bindParam(':costo_unitario', $costo_unitario, PDO::PARAM_INT);
                $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta->execute();
                header("Location: lista_producto.php");
                exit();
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
            break;

        case 'borrar':
            try {
                $sql = "DELETE FROM productos WHERE id_producto = :id_producto";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta->execute();
                header("Location: lista_producto.php");
                exit();
            } catch (PDOException $e) {
                echo "Error al eliminar el producto: " . $e->getMessage();
            }
            break;

        case 'Seleccionar':
            try {
                $sql = "SELECT * FROM productos WHERE id_producto = :id_producto";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_producto', $id_producto);
                $consulta->execute();
                $producto = $consulta->fetch(PDO::FETCH_ASSOC);
                if ($producto) {
                    $id_categoria = $producto['id_categoria'];
                    $marca = $producto['marca'];
                    $modelo = $producto['modelo'];
                    $descripcion = $producto['descripcion'];
                    $costo_unitario = $producto['costo_unitario'];
                }
            } catch (PDOException $e) {
                echo "Error al seleccionar el producto: " . $e->getMessage();
            }
            break;
    }
}

$consulta = $conexionBD->prepare('SELECT * FROM productos');
$consulta->execute();
