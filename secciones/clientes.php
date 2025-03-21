<?php
require_once '../configuraciones/bd.php'; // Cargar configuración de base de datos
$conexionBD = BD::crearInstancia();

$id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : '';
$nombre_cliente = isset($_POST['nombre_cliente']) ? $_POST['nombre_cliente'] : '';
$apellido_cliente = isset($_POST['apellido_cliente']) ? $_POST['apellido_cliente'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rut = isset($_POST['rut']) ? $_POST['rut'] : '';
$direccion = isset($_POST['direccion']) ? $_POST['direccion'] : '';
$nro_contacto = isset($_POST['nro_contacto']) ? $_POST['nro_contacto'] : '';
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($accion != '') {
    switch ($accion) {
        case 'agregar':
            $sql = "INSERT INTO Clientes (nombre_cliente, apellido_cliente, email, password, rut, direccion, nro_contacto) 
                    VALUES (:nombre_cliente, :apellido_cliente, :email, :password, :rut, :direccion, :nro_contacto)";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':nombre_cliente', $nombre_cliente);
            $consulta->bindParam(':apellido_cliente', $apellido_cliente);
            $consulta->bindParam(':email', $email);
            $consulta->bindParam(':password', password_hash($password, PASSWORD_DEFAULT)); // Encriptación de contraseña
            $consulta->bindParam(':rut', $rut);
            $consulta->bindParam(':direccion', $direccion);
            $consulta->bindParam(':nro_contacto', $nro_contacto);
            $consulta->execute();
            header("Location: lista_clientes.php");
            exit();
            break;
        case 'agregar_cliente':
            $sql = "INSERT INTO Clientes (nombre_cliente, apellido_cliente, email, password, rut, direccion, nro_contacto) 
                        VALUES (:nombre_cliente, :apellido_cliente, :email, :password, :rut, :direccion, :nro_contacto)";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':nombre_cliente', $nombre_cliente);
            $consulta->bindParam(':apellido_cliente', $apellido_cliente);
            $consulta->bindParam(':email', $email);
            $consulta->bindParam(':password', password_hash($password, PASSWORD_DEFAULT)); // Encriptación de contraseña
            $consulta->bindParam(':rut', $rut);
            $consulta->bindParam(':direccion', $direccion);
            $consulta->bindParam(':nro_contacto', $nro_contacto);
            $consulta->execute();
            header("Location: vista_cliente.php");
            exit();
            break;

        case 'editar':
            $sql = "UPDATE Clientes SET 
                        nombre_cliente=:nombre_cliente, apellido_cliente=:apellido_cliente, email=:email, 
                        password=:password, rut=:rut, direccion=:direccion, nro_contacto=:nro_contacto 
                    WHERE id_cliente=:id_cliente";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':id_cliente', $id_cliente);
            $consulta->bindParam(':nombre_cliente', $nombre_cliente);
            $consulta->bindParam(':apellido_cliente', $apellido_cliente);
            $consulta->bindParam(':email', $email);
            $consulta->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
            $consulta->bindParam(':rut', $rut);
            $consulta->bindParam(':direccion', $direccion);
            $consulta->bindParam(':nro_contacto', $nro_contacto);
            $consulta->execute();
            header("Location: lista_clientes.php");
            exit();
            break;

        case 'eliminar':
            $sql = "DELETE FROM Clientes WHERE id_cliente=:id_cliente";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':id_cliente', $id_cliente);
            $consulta->execute();
            header("Location: lista_clientes.php");
            exit();
            break;

        case 'seleccionar':
            $sql = "SELECT * FROM Clientes WHERE id_cliente=:id_cliente";
            $consulta = $conexionBD->prepare($sql);
            $consulta->bindParam(':id_cliente', $id_cliente);
            $consulta->execute();
            $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

            $nombre_cliente = $cliente['nombre_cliente'];
            $apellido_cliente = $cliente['apellido_cliente'];
            $email = $cliente['email'];
            $password = $cliente['password'];
            $rut = $cliente['rut'];
            $direccion = $cliente['direccion'];
            $nro_contacto = $cliente['nro_contacto'];
            break;
    }
}

$sql = "SELECT * FROM Clientes";
$lista_clientes = $conexionBD->query($sql)->fetchAll(PDO::FETCH_ASSOC);
