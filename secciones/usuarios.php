<?php
require_once '../configuraciones/bd.php'; // Evita cargar el archivo más de una vez
$conexionBD = BD::crearInstancia();

$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
$nombre     = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$apellido   = isset($_POST['apellido']) ? $_POST['apellido'] : '';
$email      = isset($_POST['email']) ? $_POST['email'] : '';
$password   = isset($_POST['password']) ? $_POST['password'] : '';
$rol        = isset($_POST['rol']) ? $_POST['rol'] : '';
$accion     = isset($_POST['accion']) ? $_POST['accion'] : '';

$error = ""; // Variable para almacenar mensajes de error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación: El nombre y apellido no deben contener números
    if (!empty($nombre) && preg_match('/[0-9]/', $nombre)) {
        $error = "El nombre no puede contener números.";
    } elseif (!empty($apellido) && preg_match('/[0-9]/', $apellido)) {
        $error = "El apellido no puede contener números.";
    }
    
    // Si no hay error, se procede a ejecutar la acción
    if ($error == "") {
        if ($accion != '') {
            switch ($accion) {
                case 'agregar':
                    $sql = "INSERT INTO usuarios(nombre, apellido, email, password, rol) VALUES (:nombre, :apellido, :email, :password, :rol)";
                    $consulta = $conexionBD->prepare($sql);
                    $consulta->bindParam(':nombre', $nombre);
                    $consulta->bindParam(':apellido', $apellido);
                    $consulta->bindParam(':email', $email);
                    $consulta->bindParam(':password', $password);
                    $consulta->bindParam(':rol', $rol);
                    $consulta->execute();
                    header("Location: lista_usuarios.php");
                    exit();
                    break;

                case 'editar':
                    $sql = "UPDATE usuarios SET nombre=:nombre, apellido=:apellido, email=:email, password=:password, rol=:rol WHERE id_usuario=:id_usuario";
                    $consulta = $conexionBD->prepare($sql);
                    $consulta->bindParam(':id_usuario', $id_usuario);
                    $consulta->bindParam(':nombre', $nombre);
                    $consulta->bindParam(':apellido', $apellido);
                    $consulta->bindParam(':email', $email);
                    $consulta->bindParam(':password', $password);
                    $consulta->bindParam(':rol', $rol);
                    $consulta->execute();
                    header("Location: lista_usuarios.php");
                    exit();
                    break;

                case 'eliminar':
                    $sql = "DELETE FROM usuarios WHERE id_usuario=:id_usuario";
                    $consulta = $conexionBD->prepare($sql);
                    $consulta->bindParam(':id_usuario', $id_usuario);
                    $consulta->execute();
                    header("Location: lista_usuarios.php");
                    exit();
                    break;

                case 'seleccionar':
                    $sql = "SELECT * FROM usuarios WHERE id_usuario=:id_usuario";
                    $consulta = $conexionBD->prepare($sql);
                    $consulta->bindParam(':id_usuario', $id_usuario);
                    $consulta->execute();
                    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

                    $nombre   = $usuario['nombre'];
                    $apellido = $usuario['apellido'];
                    $email    = $usuario['email'];
                    $password = $usuario['password'];
                    $rol      = $usuario['rol'];
                    break;
            }
        }
    }
}

$sql = "SELECT * FROM usuarios";
$lista_usuarios = $conexionBD->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
