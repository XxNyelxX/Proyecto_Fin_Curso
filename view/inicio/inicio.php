<div class="top-right-controls">
    
    <button id="btnEstatico" class="btn-animaciones on">ANIMACIONES: ENCENDIDO</button>
        
    <div class="pixel-dropdown-container">
        <button class="btn-desplegable" id="btnUserMenu">
            <span class="username-text">
                <?php echo isset($_SESSION['username']) ? htmlspecialchars(strtoupper($_SESSION['username'])) : 'ACCESO'; ?>
            </span>
            <span class="flecha">&gt;</span>
        </button>
    
        <div class="pixel-dropdown-content" id="dropdownMenu">
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="?c=acceso&a=EditarPerfil" class="btn-opcion edit-color">MODIFICAR</a>
                <a href="?c=acceso&a=Salir" class="btn-opcion exit-color">SAÍR</a>
            <?php } else { ?>
                <a href="?c=acceso&a=Entrar" class="btn-opcion login-color">ENTRAR</a>
                <a href="?c=acceso&a=Registrarse" class="btn-opcion signup-color">REXISTRARSE</a>
            <?php } ?>
        </div>
    </div>
</div>

<div class="contenedor-central">
    <h1 class="titulo-animado-pixel" style="font-size: 5rem; margin-bottom: 40px; letter-spacing: 5px;">GBOMB</h1>
        
    <div class="botones-menu">
        <a href="?c=partida&a=Crear" class="btn-gigante btn-crear">CREAR PARTIDA</a>
        <a href="?c=partida&a=Unirse" class="btn-gigante btn-unirse">UNIRSE A PARTIDA</a>
        <a href="?c=partida&a=Historial" class="btn-gigante btn-historial">HISTORIAL</a>
        <a href="?c=clasificacion&a=Clasificacion" class="btn-gigante btn-clasificacion">CLASIFICACIÓN</a>
    </div>
</div>