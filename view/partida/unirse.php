<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="contenedor-unirse">
    <h2 class="titulo-animado-pixel" style="font-size: 4rem; margin-bottom: 40px; letter-spacing: 5px;">UNIRSE</h2>
    
    <div class="lista-partidas-container">
        
        <div class="cabecera-lista-partidas">
            <div class="col-nombre">NOME DA SALA</div>
            <div class="col-jugadores">XOGADORES</div>
            <div class="col-bomba">BOMBA</div>
            <div class="col-vidas">VIDAS</div>
            <div class="col-accion">ACCIÓN</div>
        </div>

        <div class="lista-partidas-scroll" id="lista-partidas-tiempo-real">
            <?php if (empty($partidas)){ ?>
                <p class="sin-datos">NON HAI PARTIDAS DISPOÑIBLES</p>
                
            <?php }else{ ?>
                <?php foreach ($partidas as $p){ 
                    $llena = ($p->num_jugadores >= $p->max_jugadores);
                    $claseBoton = $llena ? 'btn-unirse-bloqueado' : 'btn-unirse-accion';
                    $textoBoton = $llena ? 'CHEA' : 'UNIRSE';
                    
                    // Comprobamos si está llena para darle una clase extra a la tarjeta
                    $claseTarjeta = $llena ? 'tarjeta-partida llena' : 'tarjeta-partida';

                    // Comprobamos si es admin (Rol 1)
                    $esAdmin = (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1);
                    // Si es admin, le damos una clase extra a los botones para encogerlos
                    $claseTamano = $esAdmin ? 'btn-admin-size' : '';

                ?>
                    
                    <div class="<?= $claseTarjeta ?>">
                        <div class="col-nombre">
                            <span class="nombre-sala"><?= htmlspecialchars($p->nombre) ?></span>
                        </div>

                        <div class="col-jugadores">
                            <span class="dato-partida"><?= $p->num_jugadores ?> / <?= $p->max_jugadores ?></span>
                        </div>

                        <div class="col-bomba">
                            <span class="dato-partida"><?= $p->tiempo_bomba ?>s</span>
                        </div>

                        <div class="col-vidas">
                            <span class="dato-partida"><?= $p->vidas ?> ❤</span>
                        </div>

                        <div class="col-accion">
                            <a href="?c=partida&a=Sala&id=<?= $p->id_partida ?>" class="<?= $claseBoton ?>">
                                <?= $textoBoton ?>
                            </a>

                            <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1){ ?>
                                <a href="?c=partida&a=Espectar&id=<?= $p->id_partida ?>" class="btn-espectar-accion">
                                    VER
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>