<?php
// reporte.php
session_start();
date_default_timezone_set('America/Santiago');
require('../librerias/fpdf/fpdf.php');
require_once('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['supervisor', 'administrador', 'tecnico', 'cliente']);

// Extensión de FPDF para filas adaptables
class PDF extends FPDF
{
    var $widths;
    var $aligns;

    // Establece los anchos de las columnas
    function SetWidths($w)
    {
        $this->widths = $w;
    }

    // Establece las alineaciones de las columnas
    function SetAligns($a)
    {
        $this->aligns = $a;
    }

    // Dibuja una fila con celdas que se adaptan al contenido
    function Row($data)
    {
        // Calcula la altura de la fila (valor base: 5 por línea)
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        // Salta a la siguiente página si es necesario
        $this->CheckPageBreak($h);
        // Dibuja cada celda de la fila
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            // Dibuja el borde de la celda
            $this->Rect($x, $y, $w, $h);
            // Imprime el texto en la celda usando MultiCell para que se ajuste
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            // Regresa la posición al inicio de la celda siguiente
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    // Verifica si es necesario un salto de página
    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    // Calcula el número de líneas que se requerirán para un MultiCell de ancho w
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

// Función para convertir de UTF-8 a ISO-8859-1
function decodificar($texto)
{
    return utf8_decode($texto);
}

$conexionBD = BD::crearInstancia();

// Obtener el id de la OT desde GET
$id_ot = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id_ot) {
    die(decodificar("Error: No se ha proporcionado una Orden de Trabajo válida."));
}

// CONSULTA DE LA ORDEN
$sql = "SELECT OT.*, 
               Clientes.nombre_cliente, Clientes.rut, Clientes.email AS cliente_email, Clientes.nro_contacto AS cliente_contacto,
               Usuarios.nombre AS responsable, 
               Estado_OT.nombre_estado, 
               COALESCE(Detalle_OT.descripcion_actividad, 'Sin descripción') AS descripcion_actividad,
               CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00') AS fecha_creacion_local,
               OT.costo_total
        FROM OT
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        LEFT JOIN Detalle_OT ON OT.id_ot = Detalle_OT.id_ot
        WHERE OT.id_ot = :id_ot
        GROUP BY OT.id_ot";
$consulta = $conexionBD->prepare($sql);
$consulta->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta->execute();
$orden = $consulta->fetch(PDO::FETCH_ASSOC);
if (!$orden) {
    die(decodificar("No se encontró la Orden de Trabajo."));
}

// OBTENER PRODUCTOS ASOCIADOS
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

// OBTENER SERVICIOS (TIPOS DE TRABAJO)
$sql_servicios = "SELECT s.nombre_servicio, s.costo_servicio
                  FROM Servicios_OT s_ot
                  INNER JOIN Servicios s ON s_ot.id_servicio = s.id_servicio
                  WHERE s_ot.id_ot = :id_ot";
$consulta_servicios = $conexionBD->prepare($sql_servicios);
$consulta_servicios->bindParam(':id_ot', $id_ot, PDO::PARAM_INT);
$consulta_servicios->execute();
$servicios = $consulta_servicios->fetchAll(PDO::FETCH_ASSOC);

// OBTENER HISTORIAL DE CAMBIOS
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

// INICIO DE LA GENERACIÓN DEL PDF CON LA CLASE PDF extendida
$pdf = new PDF();
$pdf->AddPage();

// Título del Reporte
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, decodificar("Detalle de Orden de Trabajo"), 0, 1, 'C');
$pdf->Ln(5);

// Agregar fecha de creación del reporte
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, decodificar("Fecha Creación Reporte: " . date("d/m/Y H:i:s")), 0, 1, 'C');
$pdf->Ln(5);

// Datos Principales de la OT
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, decodificar("ID OT: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['id_ot']), 0, 1);

$pdf->Cell(50, 8, decodificar("Cliente: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['nombre_cliente']), 0, 1);

$pdf->Cell(50, 8, decodificar("RUT Cliente: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['rut']), 0, 1);

$pdf->Cell(50, 8, decodificar("Email: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['cliente_email']), 0, 1);

$pdf->Cell(50, 8, decodificar("Contacto: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['cliente_contacto']), 0, 1);

$pdf->Cell(50, 8, decodificar("Responsable: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['responsable']), 0, 1);

$pdf->Cell(50, 8, decodificar("Estado: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['nombre_estado']), 0, 1);

$pdf->Cell(50, 8, decodificar("Fecha Creacion OT: "), 0, 0);
$pdf->Cell(50, 8, decodificar($orden['fecha_creacion_local']), 0, 1);

$pdf->Cell(50, 8, decodificar("Descripcion: "), 0, 0);
$pdf->MultiCell(0, 8, decodificar($orden['descripcion_actividad']), 0, 1);
$pdf->Ln(5);

// TIPOS DE TRABAJO
if (!empty($servicios)) {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, decodificar("Tipos de Trabajo:"), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    foreach ($servicios as $servicio) {
        $texto = $servicio['nombre_servicio'] . " - $" . number_format($servicio['costo_servicio'], 0, ',', '.');
        $pdf->Cell(0, 8, decodificar($texto), 0, 1);
    }
    $pdf->Ln(5);
}

// PRODUCTOS ASOCIADOS (tabla adaptativa)
if (!empty($productos)) {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, decodificar("Productos Asociados:"), 0, 1);

    // Definimos anchos para las columnas de la tabla de productos
    $pdf->SetWidths(array(40, 40, 20, 40, 40));
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));

    // Cabecera de la tabla
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Row(array(
        decodificar("Marca"),
        decodificar("Modelo"),
        decodificar("Cant."),
        decodificar("Costo Unit."),
        decodificar("Costo Total")
    ));

    // Datos de la tabla
    $pdf->SetFont('Arial', '', 12);
    foreach ($productos as $producto) {
        $pdf->Row(array(
            decodificar($producto['marca']),
            decodificar($producto['modelo']),
            decodificar($producto['cantidad']),
            decodificar('$' . number_format($producto['costo_unitario'], 0, ',', '.')),
            decodificar('$' . number_format($producto['costo_total_producto'], 0, ',', '.'))
        ));
    }
    $pdf->Ln(5);
}

// COSTO FINAL
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 8, decodificar("Costo Final: "), 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, decodificar('$' . number_format($orden['costo_total'], 0, ',', '.')), 0, 1);
$pdf->Ln(5);

// HISTORIAL DE CAMBIOS
if (!empty($historial)) {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, decodificar("Historial de Cambios"), 0, 1);

    // Definir anchos para tres columnas: Fecha, Usuario y Descripción
    $pdf->SetWidths(array(35, 25, 110));
    $pdf->SetAligns(array('C', 'C', 'L'));

    // Cabecera de la tabla
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Row(array(
        decodificar("Fecha"),
        decodificar("Usuario"),
        decodificar("Descripción")
    ));

    // Agrupar los registros por fecha y usuario (consolidando todos los cambios en una modificación)
    $allowedFields = array(
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
    );
    $grouped = array();
    foreach ($historial as $registro) {
        if (!in_array($registro['campo_modificado'], $allowedFields)) continue;
        $key = $registro['fecha_modificacion'] . '|' . $registro['usuario'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = array(
                'fecha_modificacion' => $registro['fecha_modificacion'],
                'usuario' => $registro['usuario'],
                'descripciones' => array()
            );
        }
        switch ($registro['campo_modificado']) {
            case 'Cliente':
                $desc = "Cliente de " . ($registro['nombre_cliente_anterior'] ?? $registro['valor_anterior'])
                    . " a " . ($registro['nombre_cliente_nuevo'] ?? $registro['valor_nuevo']);
                break;
            case 'Responsable':
                $desc = "Responsable de " . ($registro['nombre_responsable_anterior'] ?? $registro['valor_anterior'])
                    . " a " . ($registro['nombre_responsable_nuevo'] ?? $registro['valor_nuevo']);
                break;
            case 'Estado':
                $desc = "Estado de " . ($registro['nombre_estado_anterior'] ?? $registro['valor_anterior'])
                    . " a " . ($registro['nombre_estado_nuevo'] ?? $registro['valor_nuevo']);
                break;
            case 'Tipo de Trabajo':
                $decodedAnterior = json_decode($registro['valor_anterior'], true);
                $decodedNuevo = json_decode($registro['valor_nuevo'], true);
                $changeAnterior = is_array($decodedAnterior) ? implode(', ', $decodedAnterior) : ($registro['nombre_servicio_anterior'] ?? $registro['valor_anterior']);
                $changeNuevo = is_array($decodedNuevo) ? implode(', ', $decodedNuevo) : ($registro['nombre_servicio_nuevo'] ?? $registro['valor_nuevo']);
                $desc = "Tipo de trabajo de " . $changeAnterior . " a " . $changeNuevo;
                break;
            case 'Producto Nuevo':
                $desc = "Producto nuevo: " . $registro['valor_nuevo'];
                break;
            case 'Producto Reemplazado':
                $desc = "Producto reemplazado: de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
                break;
            case 'Producto Eliminado':
                $desc = "Producto eliminado: " . $registro['valor_anterior'];
                break;
            case 'Cantidad Producto':
                $desc = "Cantidad de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
                break;
            case 'Costo Total':
                // Formatear el costo sin decimales y con el símbolo de pesos chilenos
                $desc = "Costo Total de $" . number_format($registro['valor_anterior'], 0, ',', '.')
                    . " a $" . number_format($registro['valor_nuevo'], 0, ',', '.');
                break;
            case 'Descripción':
                $desc = "Descripción de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
                break;
            default:
                $desc = $registro['campo_modificado'] . " de " . $registro['valor_anterior'] . " a " . $registro['valor_nuevo'];
        }
        $grouped[$key]['descripciones'][] = $desc;
    }

    $pdf->SetFont('Arial', '', 10);
    foreach ($grouped as $group) {
        // Generar una cadena con viñetas y saltos de línea
        $descFinal = "- " . implode("\n- ", $group['descripciones']);
        $pdf->Row(array(
            decodificar($group['fecha_modificacion']),
            decodificar($group['usuario']),
            decodificar($descFinal)
        ));
    }
}
$pdf->Output();
