<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="contenedor-clasificacion">
    <h2 class="titulo-animado-pixel" style="font-size: 4rem; margin-bottom: 40px; letter-spacing: 5px;">CLASIFICACIÓN</h2>
    
    <div class="lista-scroll-container">
        
        <?php if (empty($jugadores)){ ?>
            <p class="sin-datos">SEN DATOS</p>
            
        <?php }else{ ?>
            <?php foreach ($jugadores as $jugador){ ?>
                
                <div class="tarjeta-jugador">
                    <div class="jugador-info">
                        <img src="img/perfiles/<?= htmlspecialchars($jugador->foto) ?>" alt="Avatar" class="avatar-clasificacion">
                        <span class="nombre-clasificacion"><?= htmlspecialchars($jugador->username) ?></span>
                    </div>
                    <!-- Con number_format le digo con el 0 que no quiero decimales, la coma es el simbolo que separa los decimales
                    y el punto separa los miles -->
                    <span class="puntos-clasificacion"><?= number_format($jugador->puntuacion_mensual, 0, ',', '.') ?> Puntos</span>
                </div>
                
            <?php } ?>
            
        <?php } ?>
        
    </div>
</div>