<?php
session_start();
require('../librerias/fpdf/fpdf.php');
require_once('../configuraciones/bd.php');
date_default_timezone_set('America/Santiago');

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode("Reporte de Órdenes de Trabajo"), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, utf8_decode("Generado el: ") . date("d/m/Y H:i:s"), 0, 1, 'C');
        $this->Ln(3);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

$conexionBD = BD::crearInstancia();

// Filtros
$filtro_cliente    = $_GET['filtro_cliente'] ?? '';
$filtro_rut        = $_GET['filtro_rut'] ?? '';
$filtro_responsable = $_GET['filtro_responsable'] ?? '';
$filtro_estado     = $_GET['filtro_estado'] ?? '';
$filtro_fecha_inicio = $_GET['filtro_fecha_inicio'] ?? '';
$filtro_fecha_fin    = $_GET['filtro_fecha_fin'] ?? '';

// Ordenamiento
$orden_por = $_GET['orden_por'] ?? 'id_ot';
$orden = strtoupper($_GET['orden'] ?? 'DESC');
$orden = ($orden === 'ASC') ? 'ASC' : 'DESC';

$columnas_permitidas = [
    'id_ot' => 'OT.id_ot',
    'cliente' => 'Clientes.nombre_cliente',
    'rut' => 'Clientes.rut',
    'responsable' => 'Usuarios.nombre',
    'estado' => 'Estado_OT.nombre_estado',
    'fecha' => 'OT.fecha_creacion',
    'costo' => 'OT.costo_total'
];
$columna_orden_sql = $columnas_permitidas[$orden_por] ?? 'OT.id_ot';

$sql = "SELECT OT.id_ot, Clientes.nombre_cliente, Clientes.apellido_cliente, Clientes.rut,
               Usuarios.nombre AS responsable, Usuarios.apellido AS responsable_apellido, Estado_OT.nombre_estado,
               DATE_FORMAT(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00'), '%Y-%m-%d %H:%i:%s') AS fecha_creacion,
               OT.costo_total
        FROM OT
        INNER JOIN Clientes ON OT.id_cliente = Clientes.id_cliente
        INNER JOIN Usuarios ON OT.id_responsable = Usuarios.id_usuario
        INNER JOIN Estado_OT ON OT.id_estado = Estado_OT.id_estado
        WHERE OT.estado != 'Eliminada'";

$params = [];

if (!empty($filtro_cliente)) {
    $sql .= " AND (Clientes.nombre_cliente LIKE :cliente OR Clientes.apellido_cliente LIKE :cliente)";
    $params[':cliente'] = "%$filtro_cliente%";
}
if (!empty($filtro_rut)) {
    $sql .= " AND REPLACE(REPLACE(REPLACE(Clientes.rut, '.', ''), '-', ''), ' ', '') LIKE :rut";
    $params[':rut'] = "%" . preg_replace('/[^0-9kK]/', '', $filtro_rut) . "%";
}
if (!empty($filtro_responsable)) {
    $sql .= " AND (Usuarios.nombre LIKE :responsable OR Usuarios.apellido LIKE :responsable)";
    $params[':responsable'] = "%$filtro_responsable%";
}
if (!empty($filtro_estado)) {
    $sql .= " AND Estado_OT.id_estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
    $sql .= " AND DATE(CONVERT_TZ(OT.fecha_creacion, '+00:00', '-04:00')) BETWEEN :fecha_inicio AND :fecha_fin";
    $params[':fecha_inicio'] = $filtro_fecha_inicio;
    $params[':fecha_fin'] = $filtro_fecha_fin;
}

$sql .= " ORDER BY $columna_orden_sql $orden";

$consulta = $conexionBD->prepare($sql);
foreach ($params as $clave => $valor) {
    $consulta->bindValue($clave, $valor);
}
$consulta->execute();
$ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Cabecera tabla (ancho total ajustado a 190 mm)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(10, 8, 'ID', 1);
$pdf->Cell(35, 8, utf8_decode('Cliente'), 1);
$pdf->Cell(25, 8, 'RUT', 1);
$pdf->Cell(32, 8, 'Responsable', 1);
$pdf->Cell(38, 8, 'Estado', 1);
$pdf->Cell(25, 8, 'Fecha', 1);
$pdf->Cell(25, 8, 'Costo', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$total_general = 0;

foreach ($ordenes as $orden) {
    $pdf->Cell(10, 8, $orden['id_ot'], 1);
    $pdf->Cell(35, 8, utf8_decode(mb_strimwidth($orden['nombre_cliente'] . ' ' . $orden['apellido_cliente'], 0, 32, "...")), 1);
    $pdf->Cell(25, 8, $orden['rut'], 1);
    $pdf->Cell(32, 8, utf8_decode(mb_strimwidth($orden['responsable'] . ' ' . $orden['responsable_apellido'], 0, 28, "...")), 1);
    $pdf->Cell(38, 8, utf8_decode(mb_strimwidth($orden['nombre_estado'], 0, 30, "...")), 1);
    $pdf->Cell(25, 8, substr($orden['fecha_creacion'], 0, 10), 1); // Solo fecha
    $pdf->Cell(25, 8, '$' . number_format($orden['costo_total'], 0, ',', '.'), 1);
    $pdf->Ln();
    $total_general += $orden['costo_total'];
}


// Total General
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(165, 8, 'Total General', 1);
$pdf->Cell(25, 8, '$' . number_format($total_general, 0, ',', '.'), 1);

$pdf->Output();
