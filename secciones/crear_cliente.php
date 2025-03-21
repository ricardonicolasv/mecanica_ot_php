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
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label for="nombre_cliente" class="form-label">Nombre</label>
                        <input type="text" 
                               class="form-control <?php if(isset($errors['nombre_cliente'])) echo 'is-invalid'; ?>" 
                               id="nombre_cliente" 
                               name="nombre_cliente" 
                               required 
                               value="<?= htmlspecialchars($nombre_cliente) ?>">
                        <?php if(isset($errors['nombre_cliente'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['nombre_cliente'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo Apellido -->
                    <div class="mb-3">
                        <label for="apellido_cliente" class="form-label">Apellido</label>
                        <input type="text" 
                               class="form-control <?php if(isset($errors['apellido_cliente'])) echo 'is-invalid'; ?>" 
                               id="apellido_cliente" 
                               name="apellido_cliente" 
                               required 
                               value="<?= htmlspecialchars($apellido_cliente) ?>">
                        <?php if(isset($errors['apellido_cliente'])): ?>
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
                    </div>
                    <!-- Campo Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" 
                               class="form-control <?php if(isset($errors['password'])) echo 'is-invalid'; ?>" 
                               id="password" 
                               name="password" 
                               required>
                        <?php if(isset($errors['password'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['password'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Campo RUT -->
                    <div class="mb-3">
                        <label for="rut" class="form-label">RUT</label>
                        <input type="text" 
                               class="form-control <?php if(isset($errors['rut'])) echo 'is-invalid'; ?>" 
                               id="rut" 
                               name="rut" 
                               required 
                               value="<?= htmlspecialchars($rut) ?>">
                        <?php if(isset($errors['rut'])): ?>
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
                               class="form-control <?php if(isset($errors['nro_contacto'])) echo 'is-invalid'; ?>" 
                               id="nro_contacto" 
                               name="nro_contacto" 
                               value="<?= htmlspecialchars($nro_contacto) ?>">
                        <?php if(isset($errors['nro_contacto'])): ?>
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
</div>
<?php include('../templates/footer.php'); ?>
