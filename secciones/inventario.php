<?php
include_once '../configuraciones/bd.php';
$conexionBD = BD::crearInstancia();

$id_inventario = isset($_POST['id_inventario']) ? trim($_POST['id_inventario']) : '';
$id_producto = isset($_POST['id_producto']) ? trim($_POST['id_producto']) : '';
$cantidad = isset($_POST['cantidad']) ? trim($_POST['cantidad']) : '';
$fecha_ingreso = isset($_POST['fecha_ingreso']) ? trim($_POST['fecha_ingreso']) : null;
$fecha_salida = isset($_POST['fecha_salida']) ? trim($_POST['fecha_salida']) : null;
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : ''; 

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

if ($accion != '') {
    switch ($accion) {

        case 'cambiar_estado':
            try {
                if (!isset($_GET['id_inventario']) || !isset($_GET['estado'])) {
                    throw new Exception("Datos insuficientes para cambiar el estado.");
                }

                $id_inventario = $_GET['id_inventario'];
                $nuevo_estado = ($_GET['estado'] == 'activo') ? 'activo' : 'eliminado';

                $sql = "UPDATE inventario SET estado = :estado WHERE id_inventario = :id_inventario";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
                $consulta->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta->execute();

                // Registrar en historial
                $descripcion = ($nuevo_estado == 'activo') ? "Producto restaurado en el inventario" : "Producto eliminado del inventario";
                $sql_historial = "INSERT INTO historial_inventario (id_inventario, id_producto, cantidad_anterior, cantidad_nueva, fecha_modificacion, descripcion) 
                                  VALUES (:id_inventario, (SELECT id_producto FROM inventario WHERE id_inventario = :id_inventario), 0, 0, NOW(), :descripcion)";
                $consulta_historial = $conexionBD->prepare($sql_historial);
                $consulta_historial->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta_historial->bindParam(':descripcion', $descripcion);
                $consulta_historial->execute();

                header("Location: lista_inventario.php");
                exit();
            } catch (Exception $e) {
                echo "Error al cambiar el estado: " . $e->getMessage();
            }
            break;

        case 'agregar':
            try {
                $conexionBD->beginTransaction();

                $sql = "INSERT INTO inventario (id_producto, cantidad, fecha_ingreso, fecha_salida, estado) 
                        VALUES (:id_producto, :cantidad, :fecha_ingreso, :fecha_salida, 'activo')";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                $consulta->bindParam(':fecha_ingreso', $fecha_ingreso);
                $consulta->bindParam(':fecha_salida', $fecha_salida);
                $consulta->execute();

                $id_inventario = $conexionBD->lastInsertId();

                // Registro en historial
                $descripcion = "Producto agregado al inventario";
                $sql_historial = "INSERT INTO historial_inventario 
                                    (id_inventario, id_producto, cantidad_anterior, cantidad_nueva, 
                                    fecha_ingreso_anterior, fecha_ingreso_nueva, 
                                    fecha_salida_anterior, fecha_salida_nueva, 
                                    fecha_modificacion, descripcion) 
                                  VALUES 
                                    (:id_inventario, :id_producto, 0, :cantidad_nueva, 
                                    NULL, :fecha_ingreso, 
                                    NULL, :fecha_salida, 
                                    NOW(), :descripcion)";
                $consulta_historial = $conexionBD->prepare($sql_historial);
                $consulta_historial->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta_historial->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta_historial->bindParam(':cantidad_nueva', $cantidad, PDO::PARAM_INT);
                $consulta_historial->bindParam(':fecha_ingreso', $fecha_ingreso);
                $consulta_historial->bindParam(':fecha_salida', $fecha_salida);
                $consulta_historial->bindParam(':descripcion', $descripcion);
                $consulta_historial->execute();

                $conexionBD->commit();
                header("Location: lista_inventario.php");
                exit();
            } catch (PDOException $e) {
                $conexionBD->rollBack();
                echo "Error: " . $e->getMessage();
            }
            break;

        case 'editar':
            try {
                // Obtener valores actuales antes de actualizar
                $sql_actual = "SELECT cantidad, fecha_ingreso, fecha_salida FROM inventario WHERE id_inventario = :id_inventario";
                $consulta_actual = $conexionBD->prepare($sql_actual);
                $consulta_actual->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta_actual->execute();
                $datos_actuales = $consulta_actual->fetch(PDO::FETCH_ASSOC);

                if (!$datos_actuales) {
                    throw new Exception("No se encontró el inventario con ID: $id_inventario");
                }

                $cantidad_anterior = $datos_actuales['cantidad'];
                $fecha_ingreso_anterior = $datos_actuales['fecha_ingreso'];
                $fecha_salida_anterior = $datos_actuales['fecha_salida'];

                // Actualizar inventario
                $sql = "UPDATE inventario 
                        SET id_producto = :id_producto, 
                            cantidad = :cantidad, 
                            fecha_ingreso = :fecha_ingreso, 
                            fecha_salida = :fecha_salida 
                        WHERE id_inventario = :id_inventario";
                $consulta = $conexionBD->prepare($sql);
                $consulta->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                $consulta->bindParam(':fecha_ingreso', $fecha_ingreso);
                $consulta->bindParam(':fecha_salida', $fecha_salida);
                $consulta->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta->execute();

                // Registrar en historial
                $descripcion = "Inventario actualizado";
                $sql_historial = "INSERT INTO historial_inventario 
                                    (id_inventario, id_producto, cantidad_anterior, cantidad_nueva, 
                                    fecha_ingreso_anterior, fecha_ingreso_nueva, 
                                    fecha_salida_anterior, fecha_salida_nueva, 
                                    fecha_modificacion, descripcion) 
                                  VALUES 
                                    (:id_inventario, :id_producto, :cantidad_anterior, :cantidad_nueva, 
                                    :fecha_ingreso_anterior, :fecha_ingreso_nueva, 
                                    :fecha_salida_anterior, :fecha_salida_nueva, 
                                    NOW(), :descripcion)";
                $consulta_historial = $conexionBD->prepare($sql_historial);
                $consulta_historial->bindParam(':id_inventario', $id_inventario, PDO::PARAM_INT);
                $consulta_historial->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                $consulta_historial->bindParam(':cantidad_anterior', $cantidad_anterior, PDO::PARAM_INT);
                $consulta_historial->bindParam(':cantidad_nueva', $cantidad, PDO::PARAM_INT);
                $consulta_historial->bindParam(':fecha_ingreso_anterior', $fecha_ingreso_anterior);
                $consulta_historial->bindParam(':fecha_ingreso_nueva', $fecha_ingreso);
                $consulta_historial->bindParam(':fecha_salida_anterior', $fecha_salida_anterior);
                $consulta_historial->bindParam(':fecha_salida_nueva', $fecha_salida);
                $consulta_historial->bindParam(':descripcion', $descripcion);
                $consulta_historial->execute();

                header("Location: lista_inventario.php");
                exit();
            } catch (PDOException $e) {
                echo "Error al actualizar el inventario: " . $e->getMessage();
            }
            break;
    }
}

$consulta = $conexionBD->prepare('SELECT * FROM inventario');
$consulta->execute();
