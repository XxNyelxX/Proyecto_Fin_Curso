<div class="contenedor-sala-espera">


    <div class="mesa-juego">
        <div class="bomba-central">
            <img src="img/bomba-parada.png" alt="Bomba">
            <div class="contador-jugadores">
                <?php echo count($jugadores); ?>/<?php echo $partida['max_jugadores']; ?>
            </div>
        </div>

        <div class="circulo-jugadores">
            <?php foreach ($jugadores as $index => $jugador) { ?>
                <div class="jugador-slot slot-<?php echo $index; ?>">
                    <div class="vidas-jugador">❤ <?php echo $partida['vidas']; ?></div>
                    
                    <span class="nombre-jugador"><?php echo htmlspecialchars($jugador['username']); ?></span>
                    
                    <div class="avatar-wrapper <?php echo ($jugador['id_usuario'] == $partida['id_host']) ? 'es-host' : ''; ?>">
                        <img src="img/avatars/<?php echo $jugador['foto']; ?>" alt="Avatar">
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="acciones-sala">
        <?php 
            $listo = count($jugadores) >= 2;
            $esHost = ($_SESSION['user_id'] == $partida['id_host']);
            $claseBoton = $listo ? 'btn-gigante btn-arrancar listo' : 'btn-gigante btn-arrancar bloqueado';
        ?>

        <?php if ($esHost) {
            $lleno = count($jugadores) >= $partida['max_jugadores'];
            $claseClon = $lleno ? 'btn-anadir-clon bloqueado' : 'btn-anadir-clon';
        ?>
        
            <a href="#" data-id="<?php echo $partida['id_partida']; ?>" class="<?php echo $claseClon; ?>" id="btnAnadirClon">
            ENGADIR XOGADOR
            </a>

            <?php if (!$listo) { ?>
                <p class="esperando-mini">
                    Agardando xogadores<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
                </p>
            <?php } ?>
            
            <a href="<?php echo ($listo) ? '?c=partida&a=Empezar&id='.$partida['id_partida'] : '#'; ?>" 
                class="<?php echo $claseBoton; ?>">
                ARRINCAR PARTIDA
            </a>
        <?php } else { ?>
            <p class="esperando-mini" style="margin-bottom: 20px;">
                Agardando a que el host arrinque a partida<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
            </p>
        <?php } ?>
    </div>

    <a href="?c=partida&a=Abandonar&id=<?php echo $partida['id_partida']; ?>" class="btn-abandonar-sala">ABANDONAR</a>
</div>
<script src="../public/js/anadir-jugador-boton.js"></script>