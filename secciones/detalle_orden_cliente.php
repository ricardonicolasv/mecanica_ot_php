<?php
session_start();
require_once('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['cliente']);

date_default_timezone_set('America/Santiago');
$conexionBD = BD::crearInstancia();

$id_cliente = $_SESSION['id_cliente'] ?? null;
$id_ot = $_GET['id'] ?? null;

if (!$id_cliente || !$id_ot) {
    die("Acceso no autorizado.");
}

// Verificar que la OT pertenezca al cliente
$sql = "SELECT OT.*, 
               Estado_OT.nombre_estado, 
               Usuarios.nombre AS responsable, 
               Clientes.nombre_cliente, 
               Clientes.rut,
               Clientes.email AS cliente_email,
               Clientes.nro_contacto AS cliente_contacto,
               CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00') AS fecha_creacion_local,
               (SELECT descripcion_actividad FROM Detalle_OT WHERE id_ot = OT.id_ot AND id_producto IS NULL LIMIT 1) AS descripcion_actividad
        FROM OT
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
        WHERE OT.id_ot = :id_ot AND OT.id_cliente = :id_cliente";

$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("No se encontró la orden o no tienes permiso para verla.");
}

// Productos asociados
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

// Servicios asociados
$sql_servicios = "SELECT s.nombre_servicio, s.costo_servicio
                  FROM Servicios_OT s_ot
                  INNER JOIN Servicios s ON s_ot.id_servicio = s.id_servicio
                  WHERE s_ot.id_ot = :id_ot";
$consulta_servicios = $conexionBD->prepare($sql_servicios);
$consulta_servicios->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_servicios->execute();
$servicios = $consulta_servicios->fetchAll(PDO::FETCH_ASSOC);

// Archivos adjuntos
$sql_archivos = "SELECT * FROM ArchivosAdjuntos_OT WHERE id_ot = :id_ot";
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

include('../templates/header_cliente.php');
?>

<div class="container mt-4">
    <h2 class="text-center">Detalle de Orden de Trabajo #<?= htmlspecialchars($orden['id_ot']) ?></h2>

    <div class="card mt-3">
        <div class="card-body">
            <p><strong>Fecha de Creación:</strong> <?= $orden['fecha_creacion_local'] ?></p>
            <p><strong>Estado:</strong> <?= $orden['nombre_estado'] ?></p>
            <p><strong>Responsable:</strong> <?= $orden['responsable'] ?></p>
            <p><strong>Email Cliente:</strong> <?= $orden['cliente_email'] ?></p>
            <p><strong>Contacto:</strong> <?= $orden['cliente_contacto'] ?></p>
            <p><strong>Descripción:</strong> <?= !empty($orden['descripcion_actividad']) ? htmlspecialchars($orden['descripcion_actividad']) : 'No especificada' ?></p>

            <?php if (!empty($servicios)): ?>
                <h5 class="mt-4">Servicios Asociados</h5>
                <ul>
                    <?php foreach ($servicios as $servicio): ?>
                        <li><?= htmlspecialchars($servicio['nombre_servicio']) ?> - $<?= number_format($servicio['costo_servicio'], 0, ',', '.') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

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
            <?php endif; ?>
            <p><strong>Costo Total:</strong> <?= '$' . number_format($orden['costo_total'], 0, ',', '.') ?></p>

            <?php if (!empty($archivos)): ?>
                <h5 class="mt-4">Archivos Adjuntos</h5>
                <ul>
                    <?php foreach ($archivos as $archivo): ?>
                        <li>
                            <a href="<?= $archivo['ruta_archivo'] ?>" target="_blank"><?= basename($archivo['ruta_archivo']) ?></a>
                            (<?= htmlspecialchars($archivo['tipo_archivo']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div>
                <a href="lista_ordenes_cliente.php" class="btn btn-secondary mt-3">Volver</a>
                <a href="reporte.php?id=<?= htmlspecialchars($orden['id_ot']) ?>" class="btn btn-primary mt-3">Reporte</a>
            </div>
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
                                        $descripciones[] = "Costo Total de <strong>$anterior</strong> a <strong>$nuevo</strong>";
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