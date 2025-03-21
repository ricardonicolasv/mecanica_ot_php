<?php
if (!class_exists('BD')) { // Verifica si la clase ya está definida
    class BD {
        private static $instancia = null;

        public static function crearInstancia() {
            if (!isset(self::$instancia)) {
                $opciones[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
                self::$instancia = new PDO('mysql:host=localhost;dbname=orden_trabajo', 'root', '', $opciones);
            }
            return self::$instancia;
        }
    }
}
?>
