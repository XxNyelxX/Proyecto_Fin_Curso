<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="contenedor-crear-partida">
    
    <h2 class="titulo-animado-pixel" style="font-size: 4rem; margin-bottom: 40px; letter-spacing: 5px;">CREAR PARTIDA</h2>
    
    <form action="?c=partida&a=GuardarPartida" method="POST" class="form-crear-partida">
        
        <div class="bloque-formulario">
            <label class="label-config label-blanco">NOME DA SALA:</label>
            <input type="text" name="nombre" autocomplete="off" 
                   value="Sala de <?php echo htmlspecialchars($_SESSION['username']); ?>"
                   class="input-sala <?php echo isset($errores['nombre']) ? 'input-error' : ''; ?>">
        </div>

        <div class="bloque-formulario">
            <label class="label-config label-blanco">VISIBILIDADE:</label>
            <div class="radio-grupo">
                <input type="radio" id="vis_publica" name="visibilidad" value="publica" checked style="display: none;">
                <label for="vis_publica" class="btn-selector btn-publica">PÚBLICA</label>

                <input type="radio" id="vis_privada" name="visibilidad" value="privada" style="display: none;">
                <label for="vis_privada" class="btn-selector btn-privada">PRIVADA</label>
            </div>
            
            <input type="text" id="campo_contrasena" name="contrasena" autocomplete="off" 
                   placeholder="Contrasinal (opcional)" 
                   maxlength="15"
                   class="input-sala" style="margin-top: 15px;">
        </div>

        <div>
            <div class="slider-header">
                <label class="label-config label-rosa" style="margin-bottom: 0;">TEMPO DA BOMBA:</label>
                <span id="val-tiempo" class="slider-valor">5s</span>
            </div>
            <input type="range" name="tiempo_bomba" min="1" max="10" value="5" class="slider-pixel"
                   oninput="document.getElementById('val-tiempo').innerText = this.value + 's'">
        </div>

        <div>
            <div class="slider-header">
                <label class="label-config label-verde" style="margin-bottom: 0;">TURNOS MÁX. SÍLABA:</label>
                <span id="val-turnos" class="slider-valor">2</span>
            </div>
            <input type="range" name="turnos_silaba" min="1" max="16" value="2" class="slider-pixel"
                   oninput="document.getElementById('val-turnos').innerText = this.value">
        </div>

        <div class="bloque-formulario">
            <label class="label-config label-blanco">VIDAS:</label>
            <div class="radio-grupo">
                
                <input type="radio" id="v1" name="vidas" value="1" style="display: none;">
                <label for="v1" class="btn-selector btn-vidas">
                    <div class="iconos-vidas">
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                    </div>
                </label>

                <input type="radio" id="v2" name="vidas" value="2" checked style="display: none;">
                <label for="v2" class="btn-selector btn-vidas">
                    <div class="iconos-vidas">
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                    </div>
                </label>

                <input type="radio" id="v3" name="vidas" value="3" style="display: none;">
                <label for="v3" class="btn-selector btn-vidas">
                    <div class="iconos-vidas">
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 9 8" fill="currentColor" shape-rendering="crispEdges">
                            <path d="M1 1 V0 H3 V1 H6 V0 H8 V1 H9 V4 H8 V5 H7 V6 H6 V7 H5 V8 H4 V7 H3 V6 H2 V5 H1 V4 H0 V1 Z"/>
                        </svg>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <div class="slider-header">
                <label class="label-config label-azul" style="margin-bottom: 0;">MÁXIMO XOGADORES:</label>
                <span id="val-players" class="slider-valor">4</span>
            </div>
            <input type="range" name="max_jugadores" min="2" max="16" value="4" class="slider-pixel"
                oninput="document.getElementById('val-players').innerText = this.value">
        </div>

        <button type="submit" class="btn-gigante btn-crear" style="width: 100%; margin-top: 10px;">
            ARRINCAR PARTIDA
        </button>
        
    </form>

    <?php if (!empty($errores)) { ?>
        <div class="mensaje-error">
            <?php foreach ($errores as $error) { ?>
                <p>> <?php echo $error; ?></p>
            <?php } ?>
        </div>
    <?php } ?>

</div>
<script src="../public/js/contrasinal.js"></script>