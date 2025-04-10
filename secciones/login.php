<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../configuraciones/bd.php'); // Conexión a la base de datos

$conexion = BD::crearInstancia();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Buscar en Usuarios
    $stmt = $conexion->prepare("SELECT id_usuario, nombre, apellido, email, password, rol FROM Usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Actualizar hash si aún no estaba hasheado (solo si decides permitirlo)
        if ($password === $user['password']) {
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $conexion->prepare("UPDATE Usuarios SET password = ? WHERE id_usuario = ?");
            $stmtUpdate->execute([$nuevoHash, $user['id_usuario']]);
        }

        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];

        header("Location: index.php");
        exit();
    }

    // Buscar en Clientes
    $stmt = $conexion->prepare("SELECT * FROM Clientes WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client && password_verify($password, $client['password'])) {
        // Actualizar hash si aún no estaba hasheado (opcional)
        if ($password === $client['password']) {
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $conexion->prepare("UPDATE Clientes SET password = ? WHERE id_cliente = ?");
            $stmtUpdate->execute([$nuevoHash, $client['id_cliente']]);
        }

        // Guardar datos de cliente en sesión
        $_SESSION['id_cliente'] = $client['id_cliente'];
        $_SESSION['nombre'] = $client['nombre_cliente'];
        $_SESSION['apellido'] = $client['apellido_cliente'];
        $_SESSION['email'] = $client['email'];
        $_SESSION['rut'] = $client['rut'];
        $_SESSION['direccion'] = $client['direccion'];
        $_SESSION['nro_contacto'] = $client['nro_contacto'];
        $_SESSION['rol'] = $client['rol']; // debe ser 'cliente'

        header("Location: vista_cliente.php");
        exit();
    }

    // Si ninguno coincide
    $_SESSION['error'] = "Correo o contraseña incorrectos";
    header("Location: login.php");
    exit();
}
?>

<?php include('../templates/header.php'); ?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center">Bienvenido</h1>
                <?php if (isset($_SESSION['error'])) : ?>
                    <div class="alert alert-danger"> <?= $_SESSION['error'] ?> </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Ingresar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include('../templates/footer.php'); ?>
