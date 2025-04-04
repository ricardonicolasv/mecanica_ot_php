<?php
require_once('../configuraciones/bd.php');
include('../secciones/clientes.php');
include('../templates/header.php');
?>
<main>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-6">
                <h1 class="text-center mb-4">Crear Cliente</h1>
                <form id="formEditarCliente" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label for="nombre_cliente" class="form-label">Nombre</label>
                        <input type="text"
                            class="form-control <?php if (isset($errors['nombre_cliente'])) echo 'is-invalid'; ?>"
                            id="nombre_cliente"
                            name="nombre_cliente"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            required
                            value="<?= htmlspecialchars($nombre_cliente) ?>">
                        <?php if (isset($errors['nombre_cliente'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['nombre_cliente'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Apellido -->
                    <div class="mb-3">
                        <label for="apellido_cliente" class="form-label">Apellido</label>
                        <input type="text"
                            class="form-control <?php if (isset($errors['apellido_cliente'])) echo 'is-invalid'; ?>"
                            id="apellido_cliente"
                            name="apellido_cliente"
                            oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')"
                            pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+"
                            title="Solo se permiten letras"
                            required
                            value="<?= htmlspecialchars($apellido_cliente) ?>">
                        <?php if (isset($errors['apellido_cliente'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['apellido_cliente'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            required
                            value="<?= htmlspecialchars($email) ?>">
                        <!-- Feedback visual en vivo -->
                        <small id="emailFeedback" class="form-text"></small>
                    </div>
                    <!-- Campo Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password"
                            class="form-control <?php if (isset($errors['password'])) echo 'is-invalid'; ?>"
                            id="password"
                            name="password"
                            required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['password'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo RUT (actualizado con función JS) -->
                    <div class="mb-3">
                        <label for="rut" class="form-label">RUT</label>
                        <input type="text"
                            class="form-control <?php if (isset($errors['rut'])) echo 'is-invalid'; ?>"
                            id="rut"
                            name="rut"
                            maxlength="12"
                            oninput="formatRut(this)"
                            required
                            value="<?= htmlspecialchars($rut) ?>">
                        <?php if (isset($errors['rut'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['rut'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Dirección -->
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text"
                            class="form-control"
                            id="direccion"
                            name="direccion"
                            value="<?= htmlspecialchars($direccion) ?>">
                    </div>
                    <!-- Campo Número de Contacto -->
                    <div class="mb-3">
                        <label for="nro_contacto" class="form-label">Número de Contacto</label>
                        <input type="text"
                            class="form-control <?php if (isset($errors['nro_contacto'])) echo 'is-invalid'; ?>"
                            id="nro_contacto"
                            name="nro_contacto"
                            value="<?= htmlspecialchars($nro_contacto) ?>">
                        <small id="telefonoFeedback" class="form-text"></small>
                        <?php if (isset($errors['nro_contacto'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['nro_contacto'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" name="accion" value="agregar_cliente" class="btn btn-primary">Crear Cliente</button>
                        <button type="reset" class="btn btn-secondary">Limpiar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
    function formatRut(input) {
        let value = input.value.toUpperCase().replace(/[^0-9K]/g, '');
        if (value.length === 9) {
            value = value.replace(/^(\d{2})(\d{3})(\d{3})([\dkK])$/, '$1.$2.$3-$4');
        } else if (value.length === 8) {
            value = value.replace(/^(\d{1})(\d{3})(\d{3})([\dkK])$/, '$1.$2.$3-$4');
        }
        input.value = value;
    }
</script>
<!-- jQuery (si no está incluido ya) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
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

    $('#formEditarCliente').on('submit', function(e) {
        const emailValido = validate();
        const telefonoValido = validarTelefono();

        if (!emailValido || !telefonoValido) {
            e.preventDefault();
            alert("Por favor, revisa los campos de Email y Teléfono antes de guardar.");
        }
    });


    function validarNumeroChileno(numero) {
        // Elimina espacios, guiones y paréntesis
        const limpio = numero.replace(/[\s\-()]/g, '');

        // Celulares: +56912345678, 912345678, 0912345678
        const celularRegex = /^(?:\+?56)?(?:0)?9\d{8}$/;

        // Fijos: +5621234567, 21234567, 0221234567
        const fijoRegex = /^(?:\+?56)?(?:0)?2\d{7}$/;

        return celularRegex.test(limpio) || fijoRegex.test(limpio);
    }

    const validarTelefono = () => {
        const $telefono = $('#nro_contacto');
        const $feedback = $('#telefonoFeedback');
        const numero = $telefono.val();
        $feedback.text('');

        if (validarNumeroChileno(numero)) {
            $feedback.text('Número válido ✅');
            $feedback.css('color', 'green');
            return true;
        } else {
            $feedback.text('Número inválido ❌');
            $feedback.css('color', 'red');
            return false;
        }
    };

    $('#nro_contacto').on('input', validarTelefono);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-7+Q1j6g4v84U8FfR4ZT3F5NEi5b9N6H9oA6m7Z9smJ5vQKkWnDz0cbjwJq3ZWb8U" crossorigin="anonymous"></script>
<?php include('../templates/footer.php'); ?>