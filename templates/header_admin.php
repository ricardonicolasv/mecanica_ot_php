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

    <!-- jQuery (debe ir ANTES de Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Select2 CSS y JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-light bg-light">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="../secciones/index.php">
                                <img src="../secciones/img/logo.png" alt="Logo" style="height: 60px;">
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
                                    <?php if (isset($_SESSION['nombre'])): ?>
                                        <li class="nav-item d-flex align-items-center me-2">
                                            <span class="nav-link disabled">👤 <?= htmlspecialchars($_SESSION['nombre']) ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="nav-item">
                                        <a class="nav-link" href="../secciones/logout.php">Cerrar Sesión</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>