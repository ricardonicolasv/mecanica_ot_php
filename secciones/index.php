<?php
session_start();
include('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['tecnico', 'supervisor', 'administrador', 'cliente']);

// Incluir header según rol
if ($_SESSION['rol'] === 'cliente') {
    include('../templates/header_cliente.php');
} else {
    include('../templates/header_admin.php');
    include('../templates/vista_admin.php');
}
?>

<main>

    <section class="intro">
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <h2 class="text-center">Vista Administrador</h2>
        <?php endif; ?>
        <?php if ($_SESSION['rol'] === 'tecnico'): ?>
            <h2 class="text-center">Vista Tecnico</h2>
        <?php endif; ?>
        <?php if ($_SESSION['rol'] === 'supervisor'): ?>
            <h2 class="text-center">Vista Supervisor</h2>
        <?php endif; ?>
        <?php if ($_SESSION['rol'] === 'cliente'): ?>
            <h2 class="text-center">Vista Clientes</h2>
        <?php endif; ?>
        <p>Ofrecemos soluciones especializadas en mecánica industrial, mantenimiento, reparación y fabricación de piezas de alta precisión.</p>
    </section>
    <section class="galeria">
        <div class="imagen">
            <img src="imagenes/taller.jpg" alt="Taller de mecánica industrial">
            <p>Taller especializado</p>
        </div>
        <div class="imagen">
            <img src="imagenes/maquinaria.jpg" alt="Maquinaria de precisión">
            <p>Maquinaria de alta precisión</p>
        </div>
        <div class="imagen">
            <img src="imagenes/operario.jpg" alt="Operario trabajando">
            <p>Profesionales calificados</p>
        </div>
    </section>
</main>
<?php include('../templates/footer.php'); ?>