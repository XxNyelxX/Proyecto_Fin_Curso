<script src="../public/js/recarga-sala.js"></script>

<div class="contenedor-sala-espera">


    <div class="mesa-juego">
        <div class="bomba-central">
            <img src="../public/img/logo/Logo_256.png" alt="Bomba">
            <div class="contador-jugadores">
                <?php echo count($jugadores); ?>/<?php echo $partida['max_jugadores']; ?>
            </div>
        </div>

        <div class="circulo-jugadores">
            <?php foreach ($jugadores as $index => $jugador) { ?>
                <div class="jugador-slot slot-<?php echo $index; ?>" data-id="<?php echo $jugador['id_usuario']; ?>">
                    <div class="vidas-jugador">❤ <?php echo $partida['vidas']; ?></div>
                    <span class="nombre-jugador"><?php echo htmlspecialchars($jugador['username']); ?></span>
                    
                    <div class="contenedor-avatar-panel">
                        <div class="avatar-wrapper <?php echo ($jugador['id_usuario'] == $partida['id_host']) ? 'es-host' : ''; ?>" onclick="togglePanel(this)">
                            <img src="img/avatars/<?php echo $jugador['foto']; ?>" alt="Avatar">
                        </div>

                        <?php if ($_SESSION['user_id'] == $partida['id_host'] && $jugador['id_usuario'] != $_SESSION['user_id']) { ?>
                            <div class="panel-expulsar">
                                <span class="texto-expulsar">Botar a <?php echo htmlspecialchars($jugador['username']); ?>?</span>
                                <button class="btn-expulsar-accion" data-id="<?php echo $jugador['id_usuario']; ?>">Botar</button>
                            </div>
                        <?php } ?>
                    </div>
                    
                </div>
            <?php } ?>
        </div>

    <div class="acciones-sala">
        <?php 
            $listo = count($jugadores) >= 2;
            $esHost = ($_SESSION['user_id'] == $partida['id_host']);
            $claseBoton = $listo ? 'btn-gigante btn-arrancar listo' : 'btn-gigante btn-arrancar bloqueado';
        ?>

        <!-- PANEL DEL HOST (Oculto si eres invitado) -->
        <div id="panel-host" style="display: <?php echo $esHost ? 'block' : 'none'; ?>;">
            <?php 
            $lleno = count($jugadores) >= $partida['max_jugadores'];
            $claseClon = $lleno ? 'btn-anadir-clon bloqueado' : 'btn-anadir-clon';
            ?>
            <a href="#" data-id="<?php echo $partida['id_partida']; ?>" class="<?php echo $claseClon; ?>" id="btnAnadirClon">
            ENGADIR XOGADOR
            </a>

            <p class="esperando-mini" style="display: <?php echo $listo ? 'none' : 'block'; ?>;">
                Agardando xogadores<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
            </p>
            
            <a href="<?php echo ($listo) ? '?c=partida&a=Empezar&id='.$partida['id_partida'] : '#'; ?>" 
                class="<?php echo $claseBoton; ?>">
                ARRINCAR PARTIDA
            </a>
        </div>

        <!-- PANEL DEL INVITADO (Oculto si eres host) -->
        <div id="panel-invitado" style="display: <?php echo $esHost ? 'none' : 'block'; ?>;">
            <p class="esperando-mini" style="margin-bottom: 20px;">
                Agardando a que o host arrinque a partida<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
            </p>
        </div>
    </div>

    <a href="?c=partida&a=Abandonar&id=<?php echo $partida['id_partida']; ?>" class="btn-abandonar-sala">ABANDONAR</a>
</div>

<script>
    const MI_ID = <?php echo $_SESSION['user_id']; ?>;
    const ID_PARTIDA = <?php echo $partida['id_partida']; ?>;
    const VIDAS_PARTIDA = <?php echo $partida['vidas']; ?>;
    
    <?php
    // Buscamos los datos visuales en la lista para pasárselos a JS
    $mi_nombre = "Xogador";
    $mi_foto = "default.png";
    $es_host = ($_SESSION['user_id'] == $partida['id_host']) ? 'true' : 'false';
    
    foreach($jugadores as $j) {
        if($j['id_usuario'] == $_SESSION['user_id']) {
            $mi_nombre = $j['username'];
            $mi_foto = $j['foto'];
            break;
        }
    }
    ?>
    
    const MI_NOMBRE = "<?php echo $mi_nombre; ?>";
    const MI_FOTO = "<?php echo $mi_foto; ?>";
    let SOY_HOST = <?php echo $es_host; ?>;
</script>

<script src="../public/js/sala-juego.js"></script>