<?php
session_start();
require('../configuraciones/bd.php');
require('../configuraciones/verificar_acceso.php');
verificarAcceso(['administrador']);

$conexionBD = BD::crearInstancia();

$id_usuario = $_GET['id_usuario'] ?? '';
$nombre = $apellido = $email = $password = $rol = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['editar'])) {
        $id_usuario = $_POST['id_usuario'];
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $email = $_POST['email'];
        $passwordForm = $_POST['password'];
        $rol = $_POST['rol'];

        // Si se quiere actualizar la contraseña
        if (!empty($passwordForm)) {
            $passwordHashed = password_hash($passwordForm, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios 
                    SET nombre=:nombre, apellido=:apellido, email=:email, password=:password, rol=:rol 
                    WHERE id_usuario=:id_usuario";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':password', $passwordHashed, PDO::PARAM_STR);
        } else {
            // Si no se quiere cambiar la contraseña
            $sql = "UPDATE usuarios 
                    SET nombre=:nombre, apellido=:apellido, email=:email, rol=:rol 
                    WHERE id_usuario=:id_usuario";
            $consulta = $conexionBD->prepare($sql);
        }

        // Parámetros comunes
        $consulta->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->bindParam(':apellido', $apellido, PDO::PARAM_STR);
        $consulta->bindParam(':email', $email, PDO::PARAM_STR);
        $consulta->bindParam(':rol', $rol, PDO::PARAM_STR);

        $consulta->execute();

        header("Location: lista_usuarios.php");
        exit();
    }


    if (isset($_POST['borrar'])) {
        $id_usuario = $_POST['id_usuario'];
        $sql = "DELETE FROM usuarios WHERE id_usuario = :id_usuario";
        $consulta = $conexionBD->prepare($sql);
        $consulta->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->execute();

        header("Location: lista_usuarios.php");
        exit();
    }
}

// Cargar datos si hay ID
if ($id_usuario) {
    $sql = "SELECT * FROM usuarios WHERE id_usuario = :id_usuario";
    $consulta = $conexionBD->prepare($sql);
    $consulta->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $consulta->execute();
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $nombre = $usuario['nombre'];
        $apellido = $usuario['apellido'];
        $email = $usuario['email'];
        $password = $usuario['password'];
        $rol = $usuario['rol'];
    }
}

// SOLO AHORA cargamos las vistas:
include('../templates/header_admin.php');
include('../templates/vista_admin.php');
?>

<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center">Editar Usuario</h1>
                <form id="formEditarCliente" action="" method="post">
                    <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            required
                            value="<?php echo htmlspecialchars($nombre); ?>">
                    </div>
                    <!-- Campo Apellido -->
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text"
                            class="form-control"
                            id="apellido"
                            name="apellido"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            required
                            value="<?php echo htmlspecialchars($apellido); ?>">
                    </div>
                    <!-- Campo Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            required
                            value="<?php echo htmlspecialchars($email); ?>">
                        <small id="emailFeedback" class="form-text"></small>
                    </div>

                    <!-- Campo Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña (dejar en blanco para no cambiar)</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <!-- Campo RUT -->
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="administrador" <?php echo ($rol == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="supervisor" <?php echo ($rol == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="tecnico" <?php echo ($rol == 'tecnico') ? 'selected' : ''; ?>>Técnico</option>
                        </select>
                    </div>
                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" name="editar" value="editar" class="btn btn-success">Actualizar Usuario</button>
                        <button type="submit" name="borrar" value="borrar" class="btn btn-danger">Borrar Usuario</button>
                        <a href="lista_usuarios.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<!-- jQuery (si no está cargado aún) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>
    // Validación de email
    const validateEmail = (email) => {
        return email.match(
            /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        );
    };

    const validate = () => {
        const $feedback = $('#emailFeedback');
        const email = $('#email').val();
        $feedback.text('');

        if (validateEmail(email)) {
            $feedback.text('"' + email + '" Email válido ✅');
            $feedback.css('color', 'green');
            return true;
        } else {
            $feedback.text('"' + email + '" Email inválido ❌');
            $feedback.css('color', 'red');
            return false;
        }
    };

    $('#email').on('input', validate);

    // Validación al enviar el formulario
    $('#formEditarCliente').on('submit', function(e) {
        if (!validate()) {
            e.preventDefault();
            alert("Por favor, ingresa un email válido antes de guardar.");
        }
    });
</script>

<?php include('../templates/footer.php'); ?>