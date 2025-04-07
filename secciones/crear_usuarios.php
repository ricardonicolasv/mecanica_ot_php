<?php
session_start();
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['administrador']);
require_once('../configuraciones/bd.php');
include('../secciones/usuarios.php');
include('../templates/header_admin.php');
include('../templates/vista_admin.php');

?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center mb-4">Crear Usuario</h1>
                <form id="formEditarCliente" action="" method="post">
                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text"
                            class="form-control <?php if (isset($error) && $error == "El nombre no puede contener números.") echo 'is-invalid'; ?>"
                            id="nombre"
                            name="nombre"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            required
                            value="<?= htmlspecialchars($nombre) ?>">
                        <?php if (isset($error) && $error == "El nombre no puede contener números."): ?>
                            <div class="invalid-feedback"><?= $error ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Apellido -->
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text"
                            class="form-control <?php if (isset($error) && $error == "El apellido no puede contener números.") echo 'is-invalid'; ?>"
                            id="apellido"
                            name="apellido"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            required
                            value="<?= htmlspecialchars($apellido) ?>">
                        <?php if (isset($error) && $error == "El apellido no puede contener números."): ?>
                            <div class="invalid-feedback"><?= $error ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                            title="Debe ser un correo electrónico válido (ej: usuario@dominio.com)"
                            required
                            value="<?= htmlspecialchars($email) ?>">
                        <small id="emailFeedback" class="form-text"></small>
                    </div>

                    <!-- Campo Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <!-- Campo Rol -->
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="administrador" <?= ($rol == "administrador") ? "selected" : "" ?>>Administrador</option>
                            <option value="supervisor" <?= ($rol == "supervisor") ? "selected" : "" ?>>Supervisor</option>
                            <option value="tecnico" <?= ($rol == "tecnico") ? "selected" : "" ?>>Técnico</option>
                        </select>
                    </div>
                    <!-- Botones -->
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" name="accion" value="agregar" class="btn btn-primary">Crear Usuario</button>
                        <button type="reset" class="btn btn-secondary">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<!-- jQuery (si no lo tienes aún) -->
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