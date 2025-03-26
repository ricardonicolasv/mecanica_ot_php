<?php
session_start();
include('../configuraciones/bd.php');
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['cliente']);
$conexionBD = BD::crearInstancia();

$id_cliente = $_SESSION['id_cliente'] ?? (isset($_GET['id_cliente']) ? $_GET['id_cliente'] : null);

$nombre_cliente = $apellido_cliente = $email = $rut = $direccion = $nro_contacto = '';

// Cargar datos del cliente
if ($id_cliente) {
    $sql = "SELECT * FROM Clientes WHERE id_cliente = :id_cliente";
    $consulta = $conexionBD->prepare($sql);
    $consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $consulta->execute();
    $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $nombre_cliente = $cliente['nombre_cliente'];
        $apellido_cliente = $cliente['apellido_cliente'];
        $email = $cliente['email'];
        $rut = $cliente['rut'];
        $direccion = $cliente['direccion'];
        $nro_contacto = $cliente['nro_contacto'];
    }
}

// Actualizar Cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id_cliente = $_POST['id_cliente'];
    $nombre_cliente = $_POST['nombre_cliente'];
    $apellido_cliente = $_POST['apellido_cliente'];
    $email = $_POST['email'];
    $rut = $_POST['rut'];
    $direccion = $_POST['direccion'];
    $nro_contacto = $_POST['nro_contacto'];
    $password = $_POST['password'];

    // Construcción de la consulta SQL
    if (!empty($password)) {
        // Si se ingresa una nueva contraseña, actualizarla con hash
        $sql = "UPDATE Clientes SET 
                    nombre_cliente=:nombre_cliente, apellido_cliente=:apellido_cliente, 
                    email=:email, password=:password, rut=:rut, direccion=:direccion, nro_contacto=:nro_contacto 
                WHERE id_cliente=:id_cliente";
    } else {
        // Si no se ingresa una contraseña, no actualizarla
        $sql = "UPDATE Clientes SET 
                    nombre_cliente=:nombre_cliente, apellido_cliente=:apellido_cliente, 
                    email=:email, rut=:rut, direccion=:direccion, nro_contacto=:nro_contacto 
                WHERE id_cliente=:id_cliente";
    }

    $consulta = $conexionBD->prepare($sql);
    $consulta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $consulta->bindParam(':nombre_cliente', $nombre_cliente, PDO::PARAM_STR);
    $consulta->bindParam(':apellido_cliente', $apellido_cliente, PDO::PARAM_STR);
    $consulta->bindParam(':email', $email, PDO::PARAM_STR);
    $consulta->bindParam(':rut', $rut, PDO::PARAM_STR);
    $consulta->bindParam(':direccion', $direccion, PDO::PARAM_STR);
    $consulta->bindParam(':nro_contacto', $nro_contacto, PDO::PARAM_STR);

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $consulta->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    }

    $consulta->execute();
    // Actualizar la sesión con los nuevos datos
    $_SESSION['nombre'] = $nombre_cliente;
    $_SESSION['apellido'] = $apellido_cliente;
    $_SESSION['email'] = $email;
    $_SESSION['rut'] = $rut;
    $_SESSION['direccion'] = $direccion;
    $_SESSION['nro_contacto'] = $nro_contacto;

    header("Location: vista_cliente.php");
    exit();
}
include('../templates/header_cliente.php');
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center">Editar Cliente</h1>
                <form action="" method="post">
                    <input type="hidden" name="id_cliente" value="<?php echo $id_cliente; ?>">

                    <div class="mb-3">
                        <label for="nombre_cliente" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" value="<?php echo htmlspecialchars($nombre_cliente); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellido_cliente" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido_cliente" name="apellido_cliente" value="<?php echo htmlspecialchars($apellido_cliente); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva Contraseña (Opcional)</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para no cambiar">
                    </div>
                    <div class="mb-3">
                        <label for="rut" class="form-label">RUT</label>
                        <input type="text" class="form-control" id="rut" name="rut" value="<?php echo htmlspecialchars($rut); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($direccion); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="nro_contacto" class="form-label">Número de Contacto</label>
                        <input type="text" class="form-control" id="nro_contacto" name="nro_contacto" value="<?php echo htmlspecialchars($nro_contacto); ?>">
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" name="editar" value="editar_cliente" class="btn btn-success">Actualizar Cliente</button>
                        <a href="vista_cliente.php" class="btn btn-warning">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>