<?php
require_once('../configuraciones/bd.php');
$conexionBD = BD::crearInstancia();

$id_ot = isset($_POST['id_ot']) ? $_POST['id_ot'] : '';
$id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : '';
$id_responsable = isset($_POST['id_responsable']) ? $_POST['id_responsable'] : '';
$id_estado = isset($_POST['id_estado']) ? $_POST['id_estado'] : '';
$id_servicio = isset($_POST['id_servicio']) ? $_POST['id_servicio'] : '';
$fecha_creacion = date('Y-m-d H:i:s');
$costo_total = isset($_POST['costo_total']) ? $_POST['costo_total'] : '';
$descripcion_actividad = isset($_POST['descripcion_actividad']) ? $_POST['descripcion_actividad'] : '';
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($accion != '') {
    try {
        $conexionBD->beginTransaction();

        switch ($accion) {
            case 'agregar':
                $descripcion_actividad = $_POST['descripcion_actividad'] ?? '';
                try {
                    // Verificar si ya hay una transacción activa antes de comenzar una nueva
                    if (!$conexionBD->inTransaction()) {
                        $conexionBD->beginTransaction();
                    }

                    // Insertar en OT sin costo_total
                    $sql_ot = "INSERT INTO OT (id_cliente, id_responsable, id_estado, fecha_creacion) 
                               VALUES (:id_cliente, :id_responsable, :id_estado, :fecha_creacion)";
                    $consulta_ot = $conexionBD->prepare($sql_ot);
                    $consulta_ot->bindParam(':id_cliente', $id_cliente);
                    $consulta_ot->bindParam(':id_responsable', $id_responsable);
                    $consulta_ot->bindParam(':id_estado', $id_estado);
                    $consulta_ot->bindParam(':fecha_creacion', $fecha_creacion);
                    $consulta_ot->execute();
                    $id_ot = $conexionBD->lastInsertId();

                    // Registrar en historial que se creó una nueva OT
                    $id_responsable = $_SESSION['id_usuario'] ?? $id_responsable ?? null; // respaldo por seguridad
                    $sql_historial = "INSERT INTO historial_ot (id_ot, id_responsable, campo_modificado, valor_anterior, valor_nuevo, fecha_modificacion) 
                  VALUES (:id_ot, :id_responsable, 'Creación', 'N/A', 'Nueva OT creada', NOW())";
                    $stmt_historial = $conexionBD->prepare($sql_historial);
                    $stmt_historial->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $stmt_historial->bindParam(':id_responsable', $id_responsable, PDO::PARAM_INT);
                    $stmt_historial->execute();


                    // Insertar el servicio en la tabla Servicios_OT
                    if (!empty($_POST['id_servicio'])) {
                        $id_servicio = $_POST['id_servicio'];
                        $sql_servicio = "INSERT INTO Servicios_OT (id_ot, id_servicio) VALUES (:id_ot, :id_servicio)";
                        $consulta_servicio = $conexionBD->prepare($sql_servicio);
                        $consulta_servicio->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                        $consulta_servicio->bindParam(':id_servicio', $id_servicio, PDO::PARAM_INT);
                        $consulta_servicio->execute();
                    }
                    // Insertar descripción como fila separada en Detalle_OT (sin producto)
                    $sql_detalle = "INSERT INTO Detalle_OT (id_ot, descripcion_actividad) VALUES (:id_ot, :descripcion_actividad)";
                    $consulta_detalle = $conexionBD->prepare($sql_detalle);
                    $consulta_detalle->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_detalle->bindParam(':descripcion_actividad', $descripcion_actividad, PDO::PARAM_STR);
                    $consulta_detalle->execute();
                    // Insertar descripción de la OT si no hay productos
                    if (empty($_POST['productos'])) {
                        $sql_detalle = "INSERT INTO Detalle_OT (id_ot, descripcion_actividad) VALUES (:id_ot, :descripcion_actividad)";
                        $consulta_detalle = $conexionBD->prepare($sql_detalle);
                        $consulta_detalle->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                        $consulta_detalle->bindParam(':descripcion_actividad', $descripcion_actividad, PDO::PARAM_STR);
                        $consulta_detalle->execute();
                    }

                    // Insertar productos y descontar inventario
                    if (!empty($_POST['productos'])) {
                        foreach ($_POST['productos'] as $key => $id_producto) {
                            $cantidad_solicitada = $_POST['cantidades'][$key];

                            // Registrar productos en Detalle_OT
                            $sql_producto = "INSERT INTO Detalle_OT (id_ot, id_producto, cantidad) 
                 VALUES (:id_ot, :id_producto, :cantidad)";
                            $consulta_producto = $conexionBD->prepare($sql_producto);
                            $consulta_producto->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                            $consulta_producto->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                            $consulta_producto->bindParam(':cantidad', $cantidad_solicitada, PDO::PARAM_INT);
                            $consulta_producto->execute();


                            // Descontar del inventario
                            $sql_actualizar_inventario = "UPDATE Inventario SET cantidad = cantidad - :cantidad_solicitada 
                                                          WHERE id_producto = :id_producto";
                            $consulta_actualizar_inventario = $conexionBD->prepare($sql_actualizar_inventario);
                            $consulta_actualizar_inventario->bindParam(':cantidad_solicitada', $cantidad_solicitada, PDO::PARAM_INT);
                            $consulta_actualizar_inventario->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                            $consulta_actualizar_inventario->execute();
                        }
                    }

                    // Calcular el nuevo costo total después de agregar productos y servicios
                    $sql_calcular_costo = "SELECT 
                                                (SELECT COALESCE(SUM(d.cantidad * p.costo_unitario), 0) 
                                                 FROM Detalle_OT d
                                                 LEFT JOIN Productos p ON d.id_producto = p.id_producto
                                                 WHERE d.id_ot = :id_ot) 
                                                +
                                                (SELECT COALESCE(SUM(s.costo_servicio), 0) 
                                                 FROM Servicios_OT sot
                                                 LEFT JOIN Servicios s ON sot.id_servicio = s.id_servicio
                                                 WHERE sot.id_ot = :id_ot)
                                                AS costo_total";

                    $consulta_costo = $conexionBD->prepare($sql_calcular_costo);
                    $consulta_costo->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_costo->execute();
                    $costo_total = $consulta_costo->fetch(PDO::FETCH_ASSOC)['costo_total'];

                    // Actualizar la OT con el nuevo costo total
                    $sql_actualizar_costo = "UPDATE OT SET costo_total = :costo_total WHERE id_ot = :id_ot";
                    $consulta_actualizar_costo = $conexionBD->prepare($sql_actualizar_costo);
                    $consulta_actualizar_costo->bindParam(':costo_total', $costo_total);
                    $consulta_actualizar_costo->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_actualizar_costo->execute();

                    // Confirmar la transacción
                    $conexionBD->commit();
                    header("Location: lista_ordenes.php");
                    exit();
                } catch (Exception $e) {
                    // Revertir la transacción en caso de error
                    if ($conexionBD->inTransaction()) {
                        $conexionBD->rollBack();
                    }
                    die("Error: " . $e->getMessage());
                }
                break;

            case 'editar':
                try {
                    // Capturar el valor enviado del formulario para la descripción
                    $descripcion_actividad = isset($_POST['descripcion_actividad']) ? $_POST['descripcion_actividad'] : '';

                    // Iniciar arreglo de cambios
                    $campos_modificados = [];

                    // Verificar si hay una transacción activa antes de comenzarla
                    if (!$conexionBD->inTransaction()) {
                        $conexionBD->beginTransaction();
                    }

                    // Obtener los datos actuales de la OT antes de la actualización
                    $sql_actual = "SELECT id_cliente, id_responsable, id_estado, costo_total FROM OT WHERE id_ot = :id_ot";
                    $consulta_actual = $conexionBD->prepare($sql_actual);
                    $consulta_actual->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_actual->execute();
                    $datos_actuales = $consulta_actual->fetch(PDO::FETCH_ASSOC);

                    if (!$datos_actuales) {
                        throw new Exception("No se encontró la OT con ID: $id_ot");
                    }

                    // Obtener los servicios actuales asociados a la OT (para múltiples registros)
                    $sql_servicios_actuales = "SELECT id_servicio_ot, id_servicio FROM Servicios_OT WHERE id_ot = :id_ot";
                    $consulta_servicios_actuales = $conexionBD->prepare($sql_servicios_actuales);
                    $consulta_servicios_actuales->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_servicios_actuales->execute();
                    $servicios_actuales = $consulta_servicios_actuales->fetchAll(PDO::FETCH_ASSOC);

                    // Obtener la descripción actual de Detalle_OT (donde id_producto IS NULL)
                    $sql_detalle_actual = "SELECT descripcion_actividad FROM Detalle_OT WHERE id_ot = :id_ot AND id_producto IS NULL";
                    $consulta_detalle_actual = $conexionBD->prepare($sql_detalle_actual);
                    $consulta_detalle_actual->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_detalle_actual->execute();
                    $descripcion_anterior = $consulta_detalle_actual->fetchColumn();

                    // Obtener los productos actuales (excluyendo la fila de descripción)
                    $sql_productos_actuales = "SELECT d.id_detalle, d.id_producto, d.cantidad, p.marca
                                                   FROM Detalle_OT d 
                                                   LEFT JOIN Productos p ON d.id_producto = p.id_producto 
                                                   WHERE d.id_ot = :id_ot AND d.id_producto IS NOT NULL";
                    $consulta_productos_actuales = $conexionBD->prepare($sql_productos_actuales);
                    $consulta_productos_actuales->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_productos_actuales->execute();
                    $productos_anteriores = $consulta_productos_actuales->fetchAll(PDO::FETCH_ASSOC);

                    // Actualizar la OT (cliente, responsable, estado, costo total)
                    $sql_ot = "UPDATE OT SET 
                                        id_cliente = :id_cliente, 
                                        id_responsable = :id_responsable, 
                                        id_estado = :id_estado, 
                                        costo_total = :costo_total
                                   WHERE id_ot = :id_ot";
                    $consulta_ot = $conexionBD->prepare($sql_ot);
                    $consulta_ot->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_ot->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
                    $consulta_ot->bindParam(':id_responsable', $id_responsable, PDO::PARAM_INT);
                    $consulta_ot->bindParam(':id_estado', $id_estado, PDO::PARAM_INT);
                    $consulta_ot->bindParam(':costo_total', $costo_total, PDO::PARAM_STR);
                    $consulta_ot->execute();

                    // Actualizar la Descripción en Detalle_OT usando el valor capturado en $descripcion_actividad
                    $sql_check_detalle = "SELECT COUNT(*) FROM Detalle_OT WHERE id_ot = :id_ot AND id_producto IS NULL";
                    $consulta_check_detalle = $conexionBD->prepare($sql_check_detalle);
                    $consulta_check_detalle->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_check_detalle->execute();
                    $existe_detalle = $consulta_check_detalle->fetchColumn();

                    if ($existe_detalle > 0) {
                        $sql_detalle = "UPDATE Detalle_OT SET descripcion_actividad = :descripcion_actividad WHERE id_ot = :id_ot AND id_producto IS NULL";
                    } else {
                        $sql_detalle = "INSERT INTO Detalle_OT (id_ot, descripcion_actividad) VALUES (:id_ot, :descripcion_actividad)";
                    }
                    $consulta_detalle = $conexionBD->prepare($sql_detalle);
                    $consulta_detalle->bindParam(':descripcion_actividad', $descripcion_actividad, PDO::PARAM_STR);
                    $consulta_detalle->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $consulta_detalle->execute();

                    /* Actualizar o insertar los Tipos de Trabajo (servicios) asociados a la OT */
                    // Se reciben en forma de arrays: $_POST['id_servicio'] y $_POST['id_servicio_ot']
                    $id_servicios_post = $_POST['id_servicio'] ?? [];
                    $id_servicio_ot_post = $_POST['id_servicio_ot'] ?? [];

                    for ($i = 0; $i < count($id_servicios_post); $i++) {
                        $servicio_post = $id_servicios_post[$i];
                        if (isset($id_servicio_ot_post[$i]) && !empty($id_servicio_ot_post[$i])) {
                            // Actualizar registro existente
                            $sql_update_service = "UPDATE Servicios_OT SET id_servicio = :id_servicio WHERE id_servicio_ot = :id_servicio_ot";
                            $consulta_update_service = $conexionBD->prepare($sql_update_service);
                            $consulta_update_service->execute([
                                ':id_servicio' => $servicio_post,
                                ':id_servicio_ot' => $id_servicio_ot_post[$i]
                            ]);
                        } else {
                            // Insertar nuevo registro
                            $sql_insert_service = "INSERT INTO Servicios_OT (id_ot, id_servicio) VALUES (:id_ot, :id_servicio)";
                            $consulta_insert_service = $conexionBD->prepare($sql_insert_service);
                            $consulta_insert_service->execute([
                                ':id_ot' => $id_ot,
                                ':id_servicio' => $servicio_post
                            ]);
                        }
                    }

                    // Eliminar aquellos registros de Servicios_OT que ya no se enviaron
                    $posted_service_ot_ids = array_filter($id_servicio_ot_post, function ($val) {
                        return !empty($val);
                    });
                    foreach ($servicios_actuales as $servicio_actual) {
                        if (!in_array($servicio_actual['id_servicio_ot'], $posted_service_ot_ids)) {
                            $sql_delete_service = "DELETE FROM Servicios_OT WHERE id_servicio_ot = :id_servicio_ot";
                            $consulta_delete_service = $conexionBD->prepare($sql_delete_service);
                            $consulta_delete_service->execute([':id_servicio_ot' => $servicio_actual['id_servicio_ot']]);
                            // Registrar en historial: obtener el nombre del servicio en vez del ID
                            $sql_get_service = "SELECT nombre_servicio FROM Servicios WHERE id_servicio = :id_servicio";
                            $stmt = $conexionBD->prepare($sql_get_service);
                            $stmt->bindParam(':id_servicio', $servicio_actual['id_servicio'], PDO::PARAM_INT);
                            $stmt->execute();
                            $servicio_nombre = $stmt->fetchColumn();
                            $sql_historial = "INSERT INTO historial_ot (id_ot, id_responsable, campo_modificado, valor_anterior, valor_nuevo, fecha_modificacion) 
                                                  VALUES (:id_ot, :id_responsable, 'Tipo de Trabajo', :valor_anterior, 'Eliminado', NOW())";
                            $consulta_historial = $conexionBD->prepare($sql_historial);
                            $consulta_historial->execute([
                                ':id_ot' => $id_ot,
                                ':id_responsable' => $id_responsable,
                                ':valor_anterior' => $servicio_nombre ? $servicio_nombre : "Servicio ID: " . $servicio_actual['id_servicio']
                            ]);
                        }
                    }

                    // Registrar en historial global el cambio de servicios si hubo modificación
                    $old_services = array_map(function ($s) {
                        return $s['id_servicio'];
                    }, $servicios_actuales);
                    $new_services = $id_servicios_post;
                    if (json_encode($old_services) !== json_encode($new_services)) {
                        function mapServicesToNames($services, $conexionBD)
                        {
                            $names = [];
                            foreach ($services as $id_servicio) {
                                $sql = "SELECT nombre_servicio FROM Servicios WHERE id_servicio = :id_servicio";
                                $stmt = $conexionBD->prepare($sql);
                                $stmt->bindParam(':id_servicio', $id_servicio, PDO::PARAM_INT);
                                $stmt->execute();
                                $name = $stmt->fetchColumn();
                                if ($name) {
                                    $names[] = $name;
                                }
                            }
                            return $names;
                        }
                        $old_service_names = mapServicesToNames($old_services, $conexionBD);
                        $new_service_names = mapServicesToNames($new_services, $conexionBD);
                        $old_services_str = implode(", ", $old_service_names);
                        $new_services_str = implode(", ", $new_service_names);

                        $campos_modificados[] = [
                            'campo' => 'Tipo de Trabajo',
                            'anterior' => $old_services_str,
                            'nuevo' => $new_services_str
                        ];
                    }

                    /* Procesar cambios en productos asociados */
                    $posted_productos  = $_POST['id_producto'] ?? [];
                    $posted_detalles   = $_POST['id_detalle']  ?? [];
                    $posted_cantidades = $_POST['cantidad']    ?? [];

                    // Mapear los productos anteriores usando el id_detalle como clave
                    $productos_por_detalle = [];
                    foreach ($productos_anteriores as $p) {
                        $productos_por_detalle[$p['id_detalle']] = $p;
                    }

                    // Arreglo para almacenar los id_detalle enviados (para detectar eliminaciones)
                    $posted_detalles_existentes = [];

                    // Recorrer los registros enviados para determinar cambios o nuevos
                    for ($i = 0; $i < count($posted_productos); $i++) {
                        $id_producto_post = $posted_productos[$i];
                        $id_detalle_post  = trim($posted_detalles[$i] ?? '');
                        $cantidad_post    = $posted_cantidades[$i] ?? 0;

                        if ($id_detalle_post !== '') {
                            $posted_detalles_existentes[] = $id_detalle_post;

                            if (isset($productos_por_detalle[$id_detalle_post])) {
                                $producto_anterior = $productos_por_detalle[$id_detalle_post];

                                // Obtener nombres de productos antes y después de la modificación
                                $sql_get_producto = "SELECT marca FROM Productos WHERE id_producto = :id_producto";
                                $stmt = $conexionBD->prepare($sql_get_producto);

                                // Producto nuevo
                                $stmt->bindParam(':id_producto', $id_producto_post, PDO::PARAM_INT);
                                $stmt->execute();
                                $nombre_producto_nuevo = $stmt->fetchColumn();

                                // Producto anterior
                                $stmt->bindParam(':id_producto', $producto_anterior['id_producto'], PDO::PARAM_INT);
                                $stmt->execute();
                                $nombre_producto_old = $stmt->fetchColumn();

                                // Si el producto fue reemplazado por otro diferente
                                if ($producto_anterior['id_producto'] != $id_producto_post) {
                                    $campos_modificados[] = [
                                        'campo' => 'Producto Reemplazado',
                                        'anterior' => "Producto: " . $nombre_producto_old . ", Cantidad: " . $producto_anterior['cantidad'],
                                        'nuevo' => "Producto: " . $nombre_producto_nuevo . ", Cantidad: " . $cantidad_post
                                    ];
                                }

                                // Si solo cambió la cantidad del producto
                                if ($producto_anterior['cantidad'] != $cantidad_post) {
                                    $campos_modificados[] = [
                                        'campo' => 'Cantidad Producto',
                                        'anterior' => "Producto: " . $nombre_producto_old . ", Cantidad: " . $producto_anterior['cantidad'],
                                        'nuevo' => "Producto: " . $nombre_producto_old . ", Cantidad: " . $cantidad_post
                                    ];
                                }
                            }
                        } else {
                            $sql_get_producto = "SELECT marca FROM Productos WHERE id_producto = :id_producto";
                            $stmt = $conexionBD->prepare($sql_get_producto);
                            $stmt->bindParam(':id_producto', $id_producto_post, PDO::PARAM_INT);
                            $stmt->execute();
                            $nombre_producto = $stmt->fetchColumn();
                            $campos_modificados[] = [
                                'campo' => 'Producto Nuevo',
                                'anterior' => 'N/A',
                                'nuevo' => "Producto: " . $nombre_producto . ", Cantidad: " . $cantidad_post
                            ];
                        }
                    }

                    // Detectar eliminaciones
                    foreach ($productos_por_detalle as $id_detalle => $producto) {
                        if (!in_array($id_detalle, $posted_detalles_existentes)) {
                            $sql_get_producto = "SELECT marca FROM Productos WHERE id_producto = :id_producto";
                            $stmt = $conexionBD->prepare($sql_get_producto);
                            $stmt->bindParam(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                            $stmt->execute();
                            $nombre_producto = $stmt->fetchColumn();
                            $campos_modificados[] = [
                                'campo' => 'Producto Eliminado',
                                'anterior' => "Producto: " . $nombre_producto . ", Cantidad: " . $producto['cantidad'],
                                'nuevo' => 'N/A'
                            ];
                        }
                    }

                    // Actualizar o insertar productos en la base de datos
                    for ($i = 0; $i < count($posted_productos); $i++) {
                        $id_producto = $posted_productos[$i];
                        $id_detalle = trim($posted_detalles[$i] ?? '');
                        $cantidad = $posted_cantidades[$i] ?? 0;
                        if (!empty($id_detalle)) {
                            $sql_producto = "UPDATE Detalle_OT SET id_producto = :id_producto, cantidad = :cantidad 
                                                 WHERE id_detalle = :id_detalle";
                            $consulta_producto = $conexionBD->prepare($sql_producto);
                            $consulta_producto->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                            $consulta_producto->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                            $consulta_producto->bindParam(':id_detalle', $id_detalle, PDO::PARAM_INT);
                            $consulta_producto->execute();
                        } else {
                            $sql_insert_producto = "INSERT INTO Detalle_OT (id_ot, id_producto, cantidad) 
                                                        VALUES (:id_ot, :id_producto, :cantidad)";
                            $consulta_insert_producto = $conexionBD->prepare($sql_insert_producto);
                            $consulta_insert_producto->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                            $consulta_insert_producto->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                            $consulta_insert_producto->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                            $consulta_insert_producto->execute();
                        }
                    }

                    // Registrar otros cambios en historial
                    if ($datos_actuales['id_cliente'] != $id_cliente) {
                        $campos_modificados[] = ['campo' => 'Cliente', 'anterior' => $datos_actuales['id_cliente'], 'nuevo' => $id_cliente];
                    }
                    if ($datos_actuales['id_responsable'] != $id_responsable) {
                        $campos_modificados[] = ['campo' => 'Responsable', 'anterior' => $datos_actuales['id_responsable'], 'nuevo' => $id_responsable];
                    }
                    if ($datos_actuales['id_estado'] != $id_estado) {
                        $campos_modificados[] = ['campo' => 'Estado', 'anterior' => $datos_actuales['id_estado'], 'nuevo' => $id_estado];
                    }
                    if ($datos_actuales['costo_total'] != $costo_total) {
                        $campos_modificados[] = ['campo' => 'Costo Total', 'anterior' => $datos_actuales['costo_total'], 'nuevo' => $costo_total];
                    }
                    if ($descripcion_anterior != $descripcion_actividad) {
                        $campos_modificados[] = ['campo' => 'Descripción', 'anterior' => $descripcion_anterior, 'nuevo' => $descripcion_actividad];
                    }

                    // Registrar en historial_ot cada cambio acumulado
                    foreach ($campos_modificados as $cambio) {
                        $sql_historial = "INSERT INTO historial_ot (id_ot, id_responsable, campo_modificado, valor_anterior, valor_nuevo, fecha_modificacion) 
                                              VALUES (:id_ot, :id_responsable, :campo_modificado, :valor_anterior, :valor_nuevo, NOW())";
                        $consulta_historial = $conexionBD->prepare($sql_historial);
                        $consulta_historial->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                        $consulta_historial->bindParam(':id_responsable', $id_responsable, PDO::PARAM_INT);
                        $consulta_historial->bindParam(':campo_modificado', $cambio['campo'], PDO::PARAM_STR);
                        $consulta_historial->bindParam(':valor_anterior', $cambio['anterior'], PDO::PARAM_STR);
                        $consulta_historial->bindParam(':valor_nuevo', $cambio['nuevo'], PDO::PARAM_STR);
                        $consulta_historial->execute();
                    }

                    // Confirmar la transacción
                    $conexionBD->commit();
                    header("Location: lista_ordenes.php");
                    exit();
                } catch (Exception $e) {
                    if ($conexionBD->inTransaction()) {
                        $conexionBD->rollBack();
                    }
                    echo "Error: " . $e->getMessage();
                }
                break;

            case 'eliminar':
                try {
                    // Iniciar la transacción
                    if (!$conexionBD->inTransaction()) {
                        $conexionBD->beginTransaction();
                    }

                    // Obtener datos de la OT antes de eliminarla (para registrar en el historial)
                    $sql_order = "SELECT id_ot, id_responsable FROM OT WHERE id_ot = :id_ot";
                    $stmt_order = $conexionBD->prepare($sql_order);
                    $stmt_order->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $stmt_order->execute();
                    $orderData = $stmt_order->fetch(PDO::FETCH_ASSOC);

                    // Si existe la orden, registrar en historial la eliminación
                    if ($orderData) {
                        // Puedes personalizar el mensaje de historial. En este ejemplo se usa "Eliminación" y se marca el valor anterior con información básica.
                        $valor_anterior = "OT #" . $orderData['id_ot'] . " Eliminada";
                        // Se asume que $id_responsable (el usuario que realiza la eliminación) está disponible; 
                        // si no, puedes asignarle un valor por defecto o extraerlo de la sesión.
                        $sql_historial = "INSERT INTO historial_ot (id_ot, id_responsable, campo_modificado, valor_anterior, valor_nuevo, fecha_modificacion) 
                                              VALUES (:id_ot, :id_responsable, 'Eliminación', :valor_anterior, 'Eliminada', NOW())";
                        $stmt_historial = $conexionBD->prepare($sql_historial);
                        $stmt_historial->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                        $stmt_historial->bindParam(':id_responsable', $orderData['id_responsable'], PDO::PARAM_INT);
                        $stmt_historial->bindParam(':valor_anterior', $valor_anterior, PDO::PARAM_STR);
                        $stmt_historial->execute();
                    }

                    // Eliminar los detalles asociados a la OT
                    $sql_detalle = "DELETE FROM Detalle_OT WHERE id_ot = :id_ot";
                    $stmt_detalle = $conexionBD->prepare($sql_detalle);
                    $stmt_detalle->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $stmt_detalle->execute();

                    // Eliminar la OT
                    $sql_ot = "DELETE FROM OT WHERE id_ot = :id_ot";
                    $stmt_ot = $conexionBD->prepare($sql_ot);
                    $stmt_ot->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
                    $stmt_ot->execute();

                    $conexionBD->commit();
                    header("Location: lista_ordenes.php");
                    exit();
                } catch (Exception $e) {
                    if ($conexionBD->inTransaction()) {
                        $conexionBD->rollBack();
                    }
                    echo "Error al eliminar la orden: " . $e->getMessage();
                }
                break;
        }
    } catch (Exception $e) {
        $conexionBD->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
// Obtener lista de todas las órdenes de trabajo con información relevante
$sql = "SELECT OT.*, Clientes.nombre_cliente, Usuarios.nombre AS responsable, Estado_OT.nombre_estado, 
               Detalle_OT.descripcion_actividad 
        FROM OT 
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente 
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario 
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        LEFT JOIN Detalle_OT ON OT.id_ot = Detalle_OT.id_ot";
$lista_ordenes = $conexionBD->query($sql)->fetchAll(PDO::FETCH_ASSOC);
