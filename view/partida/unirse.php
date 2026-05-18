<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="contenedor-unirse">
    <h2 class="titulo-animado-pixel" style="font-size: 4rem; margin-bottom: 40px; letter-spacing: 5px;">UNIRSE</h2>
    <div class="contenedor-buscador">
        <input type="text" id="inputBuscador" placeholder="BUSCAR SALA POR NOME..." autocomplete="off">
    </div>
    <div class="lista-partidas-container">
        
        <div class="cabecera-lista-partidas">
            <div class="col-nombre">NOME DA SALA</div>
            <div class="col-jugadores">XOGADORES</div>
            <div class="col-bomba">BOMBA</div>
            <div class="col-vidas">VIDAS</div>
            <div class="col-visibilidad">VISIBILIDADE</div>
            <div class="col-accion">ACCIÓN</div>
        </div>

        <div class="lista-partidas-scroll" id="lista-partidas-tiempo-real">
            <?php if (empty($partidas)){ ?>
                <p class="sin-datos">SEN DATOS</p>
                
            <?php }else{ ?>
            <p id="mensaje-sin-resultados" class="sin-datos" style="display: none;">SEN DATOS</p>
                <?php foreach ($partidas as $p){ 
                    $llena = ($p->num_jugadores >= $p->max_jugadores);
                    $claseBoton = $llena ? 'btn-unirse-bloqueado' : 'btn-unirse-accion';
                    $textoBoton = $llena ? 'CHEA' : 'UNIRSE';
                    
                    // Comprobamos si está llena para darle una clase extra a la tarjeta
                    $claseTarjeta = $llena ? 'tarjeta-partida llena' : 'tarjeta-partida';

                    // Comprobamos si es admin (Rol 1)
                    $esAdmin = (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1);

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

                        <div class="col-visibilidad">
                            <span class="dato-partida"><?= htmlspecialchars($p->visibilidad) ?></span>
                        </div>

                        <div class="col-accion">
                            <?php if ($p->visibilidad === 'privada' && !$llena){ ?>
                                <a style="cursor: pointer;" class="<?= $claseBoton ?>" onclick="abrirModalContrasinal(<?= $p->id_partida ?>)">
                                    <?= $textoBoton ?>
                                </a>
                            <?php }else{ ?>
                                <a href="?c=partida&a=Acceder&id=<?= $p->id_partida ?>" class="<?= $claseBoton ?>">
                                    <?= $textoBoton ?>
                                </a>
                            <?php } ?>

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

<div id="modalContrasinal" class="modal-pixel-overlay" onclick="cerrarModalDesdeFuera(event)">
    <div class="modal-pixel-content">
        <h3 class="titulo-animado-pixel" style="font-size: 2rem; margin-bottom: 20px;">SALA PRIVADA</h3>
        <p style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; margin-bottom: 15px;">Introduce o contrasinal para entrar:</p>
        
        <input type="password" id="inputModalPass" placeholder="**********" autocomplete="off" class="input-sala" maxlength="15">
        
        <div id="errorModalPass" class="mensaje-error" style="display: none; margin-top: 15px; margin-bottom: 0;"></div>
        
        <div class="radio-grupo" style="margin-top: 25px; gap: 15px;">
            <button type="button" class="btn-selector btn-publica" onclick="procesarContrasinalModal()" style="width: 50%;">ENTRAR</button>
            <button type="button" class="btn-selector btn-privada" onclick="ocultarModalContrasinal()" style="width: 50%;">CANCELAR</button>
        </div>
    </div>
</div>

<script src="../public/js/unirse.js"></script>
<script src="../public/js/buscador.js"></script>
<script src="../public/js/menu-contrasena.js"></script>