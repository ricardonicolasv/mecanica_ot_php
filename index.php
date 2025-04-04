<!doctype html>
<html lang="en">

<head>
    <title>Mecanica Industrial</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
</head>

<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="index.php">
                                <img src="../app/secciones/img/logo.png" alt="Logo" style="height: 60px;">
                            </a>
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                                aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarNav">
                                <!-- Elementos en la parte izquierda -->
                                <ul class="navbar-nav">
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Servicios</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Equipamiento</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Contacto</a>
                                    </li>
                                </ul>
                                <!-- Elementos en la parte derecha -->
                                <ul class="navbar-nav ms-auto">
                                    <li class="nav-item">
                                        <a class="nav-link" href="/app/secciones/login.php">Login</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="/app/secciones/crear_cliente.php">Registro</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="intro">
            <h2 class="text-center">Conoce Nuestros Servicios</h2>
            <p class="text-center">Ofrecemos soluciones especializadas en mecánica industrial, mantenimiento, reparación y fabricación de piezas de alta precisión.</p>
        </section>
        <section class="galeria text-center">
            <div class="imagen">
                <img src="../app/secciones/img/taller_mecanico.png" alt="Taller de mecánica industrial" style="height: 200px;">
                <p>Taller especializado</p>
            </div>
            <div class="imagen">
                <img src="../app/secciones/img/maquinaria.jpg" alt="Maquinaria de precisión" style="height: 200px;">
                <p>Maquinaria de alta precisión</p>
            </div>
            <div class="imagen">
                <img src="../app/secciones/img/operario.jpg" alt="Operario trabajando" style="height: 200px;">
                <p>Profesionales calificados</p>
            </div>
        </section>
    </main>

    <?php include('../app/templates/footer.php'); ?>