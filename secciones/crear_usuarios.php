<?php 
require_once('../configuraciones/bd.php');
include('../secciones/usuarios.php'); 
session_start();
include('../templates/header_admin.php'); 
include('../templates/vista_admin.php'); 
include('../configuraciones/verificar_acceso.php');
verificarAcceso(['administrador']);
?>
    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-6">
                    <h1 class="text-center mb-4">Crear Usuario</h1>
                    <form action="" method="post">
                        <!-- Campo Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" 
                                   class="form-control <?php if(isset($error) && $error=="El nombre no puede contener números.") echo 'is-invalid'; ?>" 
                                   id="nombre" 
                                   name="nombre" 
                                   required 
                                   value="<?= htmlspecialchars($nombre) ?>">
                            <?php if(isset($error) && $error=="El nombre no puede contener números."): ?>
                                <div class="invalid-feedback">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Campo Apellido -->
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" 
                                   class="form-control <?php if(isset($error) && $error=="El apellido no puede contener números.") echo 'is-invalid'; ?>" 
                                   id="apellido" 
                                   name="apellido" 
                                   required 
                                   value="<?= htmlspecialchars($apellido) ?>">
                            <?php if(isset($error) && $error=="El apellido no puede contener números."): ?>
                                <div class="invalid-feedback">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Campo Email (la validación de HTML5 mostrará el mensaje por defecto) -->
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
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <!-- Campo Rol -->
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="administrador" <?= ($rol=="administrador") ? "selected" : "" ?>>Administrador</option>
                                <option value="supervisor" <?= ($rol=="supervisor") ? "selected" : "" ?>>Supervisor</option>
                                <option value="tecnico" <?= ($rol=="tecnico") ? "selected" : "" ?>>Técnico</option>
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
</div>
<?php include('../templates/footer.php'); ?>
