<?php
date_default_timezone_set('America/Santiago');
session_start();
include('../configuraciones/bd.php');
include('../templates/header_admin.php');
include('../templates/vista_admin.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador']);

$conexionBD = BD::crearInstancia();

$id_ot = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_ot) {
    die("Error: No se ha proporcionado una Orden de Trabajo válida.");
}

// Consulta principal de la OT (sin incluir directamente los servicios)
$sql = "SELECT OT.*, 
               Clientes.nombre_cliente,
               Clientes.apellido_cliente,
               Clientes.rut,
               Clientes.email AS cliente_email, 
               Clientes.nro_contacto AS cliente_contacto,
               Usuarios.nombre AS responsable, 
               Estado_OT.nombre_estado, 
               -- Usamos una subconsulta para obtener la descripción
               (SELECT descripcion_actividad FROM Detalle_OT WHERE id_ot = OT.id_ot AND id_producto IS NULL LIMIT 1) AS descripcion_actividad,
               CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00') AS fecha_creacion_local,
               OT.costo_total
        FROM OT
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        WHERE OT.id_ot = :id_ot";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("No se encontró la Orden de Trabajo.");
}

// Obtener productos asociados a la OT
$sql_productos = "SELECT Productos.marca, Productos.modelo, 
                         Detalle_OT.cantidad, 
                         Productos.costo_unitario, 
                         (Detalle_OT.cantidad * Productos.costo_unitario) AS costo_total_producto
                  FROM Detalle_OT
                  INNER JOIN Productos ON Detalle_OT.id_producto = Productos.id_producto
                  WHERE Detalle_OT.id_ot = :id_ot";
$consulta_productos = $conexionBD->prepare($sql_productos);
$consulta_productos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_productos->execute();
$productos = $consulta_productos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los servicios (Tipos de Trabajo) asociados a la OT
$sql_servicios = "SELECT s.nombre_servicio, s.costo_servicio
                  FROM Servicios_OT s_ot
                  INNER JOIN Servicios s ON s_ot.id_servicio = s.id_servicio
                  WHERE s_ot.id_ot = :id_ot";
$consulta_servicios = $conexionBD->prepare($sql_servicios);
$consulta_servicios->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_servicios->execute();
$servicios = $consulta_servicios->fetchAll(PDO::FETCH_ASSOC);

// Obtener archivos adjuntos de la OT
$sql_archivos = "SELECT id_archivo, ruta_archivo, tipo_archivo, nombre_original FROM ArchivosAdjuntos_OT WHERE id_ot = :id_ot";
$consulta_archivos = $conexionBD->prepare($sql_archivos);
$consulta_archivos->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_archivos->execute();
$archivos = $consulta_archivos->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de cambios de la OT
$sql_historial = "SELECT historial_ot.*, 
                         u.nombre AS usuario,
                         COALESCE(c.nombre_cliente, historial_ot.valor_anterior) AS nombre_cliente_anterior,
                         COALESCE(c2.nombre_cliente, historial_ot.valor_nuevo) AS nombre_cliente_nuevo,
                         COALESCE(r.nombre, historial_ot.valor_anterior) AS nombre_responsable_anterior,
                         COALESCE(r2.nombre, historial_ot.valor_nuevo) AS nombre_responsable_nuevo,
                         COALESCE(e.nombre_estado, historial_ot.valor_anterior) AS nombre_estado_anterior,
                         COALESCE(e2.nombre_estado, historial_ot.valor_nuevo) AS nombre_estado_nuevo,
                         COALESCE(s.nombre_servicio, historial_ot.valor_anterior) AS nombre_servicio_anterior,
                         COALESCE(s2.nombre_servicio, historial_ot.valor_nuevo) AS nombre_servicio_nuevo
                  FROM historial_ot
                  INNER JOIN Usuarios u ON historial_ot.id_responsable = u.id_usuario
                  LEFT JOIN Clientes c ON historial_ot.valor_anterior = c.id_cliente AND historial_ot.campo_modificado = 'Cliente'
                  LEFT JOIN Clientes c2 ON historial_ot.valor_nuevo = c2.id_cliente AND historial_ot.campo_modificado = 'Cliente'
                  LEFT JOIN Usuarios r ON historial_ot.valor_anterior = r.id_usuario AND historial_ot.campo_modificado = 'Responsable'
                  LEFT JOIN Usuarios r2 ON historial_ot.valor_nuevo = r2.id_usuario AND historial_ot.campo_modificado = 'Responsable'
                  LEFT JOIN Estado_OT e ON historial_ot.valor_anterior = e.id_estado AND historial_ot.campo_modificado = 'Estado'
                  LEFT JOIN Estado_OT e2 ON historial_ot.valor_nuevo = e2.id_estado AND historial_ot.campo_modificado = 'Estado'
                  LEFT JOIN Servicios s ON historial_ot.valor_anterior = s.id_servicio AND historial_ot.campo_modificado = 'Tipo de Trabajo'
                  LEFT JOIN Servicios s2 ON historial_ot.valor_nuevo = s2.id_servicio AND historial_ot.campo_modificado = 'Tipo de Trabajo'
                  WHERE historial_ot.id_ot = :id_ot 
                  ORDER BY historial_ot.fecha_modificacion DESC";
$consulta_historial = $conexionBD->prepare($sql_historial);
$consulta_historial->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_historial->execute();
$historial = $consulta_historial->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <div class="container">
        <h1 class="text-center">Detalle de Orden de Trabajo</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">ID OT: <?= htmlspecialchars($orden['id_ot']) ?></h5>
                <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['nombre_cliente'] . ' ' . $orden['apellido_cliente']) ?></p>
                <p><strong>Rut Cliente:</strong> <?= htmlspecialchars($orden['rut']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($orden['cliente_email']) ?></p>
                <p><strong>Contacto:</strong> <?= htmlspecialchars($orden['cliente_contacto']) ?></p>
                <p><strong>Responsable:</strong> <?= htmlspecialchars($orden['responsable']) ?></p>
                <p><strong>Estado:</strong> <?= htmlspecialchars($orden['nombre_estado']) ?></p>
                <p><strong>Tipos de Trabajo:</strong>
                    <?php if (!empty($servicios)): ?>
                <ul>
                    <?php foreach ($servicios as $servicio): ?>
                        <li><?= htmlspecialchars($servicio['nombre_servicio']) ?> - $<?= number_format($servicio['costo_servicio'], 0, ',', '.') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                No asignado
            <?php endif; ?>
            </p>
            <p><strong>Fecha Creación OT:</strong> <?= htmlspecialchars($orden['fecha_creacion_local']) ?></p>
            <p><strong>Descripción:</strong> <?= !empty($orden['descripcion_actividad']) ? htmlspecialchars($orden['descripcion_actividad']) : 'No especificada' ?></p>

            <!-- Productos Asociados -->
            <?php if (!empty($productos)): ?>
                <h5 class="mt-4">Productos Asociados</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Cantidad</th>
                            <th>Costo Unitario</th>
                            <th>Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['marca']) ?></td>
                                <td><?= htmlspecialchars($producto['modelo']) ?></td>
                                <td><?= $producto['cantidad'] ?></td>
                                <td><?= '$' . number_format($producto['costo_unitario'], 0, ',', '.') ?></td>
                                <td><?= '$' . number_format($producto['costo_total_producto'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No hay productos asociados.</p>
            <?php endif; ?>

            <p><strong>Costo Final:</strong> <?= '$' . number_format($orden['costo_total'], 0, ',', '.') ?></p>

            <?php if (!empty($archivos)): ?>
                <h5 class="mt-4">Archivos Adjuntos</h5>
                <ul>
                    <?php foreach ($archivos as $archivo): ?>
                        <li>
                            <a href="../<?= htmlspecialchars($archivo['ruta_archivo']) ?>" target="_blank">
                                <?= htmlspecialchars($archivo['nombre_original']) ?>
                            </a>

                            (<?= htmlspecialchars($archivo['tipo_archivo']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="mt-4">
                <a href="lista_ordenes.php" class="btn btn-secondary">Volver</a>
                <a href="editar_orden.php?id=<?= htmlspecialchars($orden['id_ot']) ?>" class="btn btn-info">Editar</a>
            </div>
            <div class="text-end mb-3">
                <a href="reporte.php?id=<?= htmlspecialchars($orden['id_ot']) ?>" class="btn btn-success" target="_blank">
                    Generar Reporte PDF
                </a>
            </div>

            </div>
        </div>
        <main class="container">
            <!-- Historial de Cambios -->
            <?php if (!empty($historial)): ?>
                <h3 class="mt-4">Historial de Cambios</h3>
                <?php
                // Definir los campos permitidos
                $allowedFields = [
                    'Cliente',
                    'Responsable',
                    'Estado',
                    'Tipo de Trabajo',
                    'Producto Nuevo',
                    'Producto Reemplazado',
                    'Producto Eliminado',
                    'Cantidad Producto',
                    'Costo Total',
                    'Descripción'
                ];
                $grouped = [];

                // Agrupar los registros por fecha y usuario
                foreach ($historial as $registro) {
                    if (!in_array($registro['campo_modificado'], $allowedFields)) continue;
                    $key = $registro['fecha_modificacion'] . '|' . $registro['usuario'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'fecha_modificacion' => $registro['fecha_modificacion'],
                            'usuario' => $registro['usuario'],
                            'campos' => [],
                            'valor_anterior' => [],
                            'valor_nuevo' => [],
                        ];
                    }

                    switch ($registro['campo_modificado']) {
                        case 'Cliente':
                            $changeAnterior = $registro['nombre_cliente_anterior'] ?? $registro['valor_anterior'];
                            $changeNuevo = $registro['nombre_cliente_nuevo'] ?? $registro['valor_nuevo'];
                            break;
                        case 'Responsable':
                            $changeAnterior = $registro['nombre_responsable_anterior'] ?? $registro['valor_anterior'];
                            $changeNuevo = $registro['nombre_responsable_nuevo'] ?? $registro['valor_nuevo'];
                            break;
                        case 'Estado':
                            $changeAnterior = $registro['nombre_estado_anterior'] ?? $registro['valor_anterior'];
                            $changeNuevo = $registro['nombre_estado_nuevo'] ?? $registro['valor_nuevo'];
                            break;
                        case 'Tipo de Trabajo':
                            $decodedAnterior = json_decode($registro['valor_anterior'], true);
                            $decodedNuevo = json_decode($registro['valor_nuevo'], true);
                            $changeAnterior = is_array($decodedAnterior) ? implode(', ', $decodedAnterior) : ($registro['nombre_servicio_anterior'] ?? $registro['valor_anterior']);
                            $changeNuevo = is_array($decodedNuevo) ? implode(', ', $decodedNuevo) : ($registro['nombre_servicio_nuevo'] ?? $registro['valor_nuevo']);
                            break;
                        default:
                            $changeAnterior = $registro['valor_anterior'];
                            $changeNuevo = $registro['valor_nuevo'];
                    }
                    $grouped[$key]['campos'][] = $registro['campo_modificado'];
                    $grouped[$key]['valor_anterior'][] = $changeAnterior;
                    $grouped[$key]['valor_nuevo'][] = $changeNuevo;
                }
                ?>

                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grouped as $group): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($group['fecha_modificacion'])) ?></td>
                                <td><?= htmlspecialchars($group['usuario']) ?></td>
                                <td>
                                    <?php
                                    $descripciones = [];
                                    foreach ($group['campos'] as $i => $campo) {
                                        $anterior = $group['valor_anterior'][$i];
                                        $nuevo = $group['valor_nuevo'][$i];
                                        switch ($campo) {
                                            case 'Cliente':
                                                $descripciones[] = "Cliente de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Responsable':
                                                $descripciones[] = "Responsable de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Estado':
                                                $descripciones[] = "Estado de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Tipo de Trabajo':
                                                $descripciones[] = "Tipo de trabajo de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Producto Nuevo':
                                                $descripciones[] = "Producto nuevo: <strong>$nuevo</strong>";
                                                break;
                                            case 'Producto Reemplazado':
                                                $descripciones[] = "Producto reemplazado: de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Cantidad Producto':
                                                $descripciones[] = "Cantidad de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            case 'Producto Eliminado':
                                                $descripciones[] = "Producto eliminado: <strong>$anterior</strong>";
                                                break;
                                            case 'Costo Total':
                                                $anteriorNumerico = (float) filter_var($anterior, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                                $nuevoNumerico = (float) filter_var($nuevo, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                                $anteriorFormateado = '$' . number_format($anteriorNumerico, 0, ',', '.');
                                                $nuevoFormateado = '$' . number_format($nuevoNumerico, 0, ',', '.');
                                                $descripciones[] = "Costo Total de <strong>$anteriorFormateado</strong> a <strong>$nuevoFormateado</strong>";
                                                break;
                                            case 'Descripción':
                                                $descripciones[] = "Descripción de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                                break;
                                            default:
                                                $descripciones[] = "$campo de <strong>$anterior</strong> a <strong>$nuevo</strong>";
                                        }
                                    }
                                    ?>
                                    <ul class="mb-0">
                                        <?php foreach ($descripciones as $desc): ?>
                                            <li><?= $desc ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">No hay cambios registrados para esta orden.</p>
            <?php endif; ?>
            </ul>
            </nav>
        </main>
        <?php include('../templates/footer.php'); ?>