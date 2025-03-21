<?php
session_start();
include('../configuraciones/bd.php'); // Conexión a la base de datos

$conexion = BD::crearInstancia();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validación en la tabla de Usuarios
    $stmt = $conexion->prepare("SELECT id_usuario, nombre, apellido, email, password, rol FROM Usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {

        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        
        // Redirigir según el rol
        switch ($user['rol']) {
            case 'administrador':
                header("Location: index.php");
                exit();
            case 'supervisor':
                header("Location: ../secciones/vista_supervisor.php");
                exit();
            case 'tecnico':
                header("Location: ../secciones/vista_tecnico.php");
                exit();
        }
    }

    // Validación en la tabla de Clientes
    $stmt = $conexion->prepare("SELECT id_cliente, nombre_cliente, apellido_cliente, email, password FROM Clientes WHERE email = ?");
    $stmt->execute([$email]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client && password_verify($password, $client['password'])) {
        $_SESSION['id_cliente'] = $client['id_cliente'];
        $_SESSION['nombre'] = $client['nombre_cliente'];
        $_SESSION['apellido'] = $client['apellido_cliente'];
        $_SESSION['email'] = $client['email'];
        
        header("Location: ../secciones/vista_cliente.php");
        exit();
    }
    
    // Si la autenticación falla
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
                <form action="login.php" method="post">
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
