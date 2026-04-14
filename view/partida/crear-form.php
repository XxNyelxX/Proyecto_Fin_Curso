<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="contenedor-crear-partida">
    
    <h2 class="titulo-animado-pixel" style="font-size: 4rem; margin-bottom: 40px; letter-spacing: 5px;">CREAR PARTIDA</h2>
    
    <form action="?c=partida&a=GuardarPartida" method="POST" class="form-crear-partida">
        
        <div class="bloque-formulario">
            <label class="label-config label-blanco">NOME DA SALA:</label>
            <input type="text" name="nombre" autocomplete="off" 
                   value="Partida de <?php echo htmlspecialchars($_SESSION['username']); ?>"
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
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                    </div>
                </label>

                <input type="radio" id="v2" name="vidas" value="2" checked style="display: none;">
                <label for="v2" class="btn-selector btn-vidas">
                    <div class="iconos-vidas">
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                    </div>
                </label>

                <input type="radio" id="v3" name="vidas" value="3" style="display: none;">
                <label for="v3" class="btn-selector btn-vidas">
                    <div class="iconos-vidas">
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                        <svg class="corazon-icon" viewBox="0 0 13 11" fill="currentColor">
                          <path d="M6.5 11C6.5 11 0 7 0 3.5C0 1.5 2 0 4.5 0C6 0 7 1 8 2C9 1 10 0 11.5 0C14 0 16 1.5 16 3.5C16 7 9.5 11 9.5 11L6.5 11Z"/>
                        </svg>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <div class="slider-header">
                <label class="label-config label-azul" style="margin-bottom: 0;">MÁXIMO XOGADORES:</label>
                <span id="val-players" class="slider-valor">8</span>
            </div>
            <input type="range" name="max_jugadores" min="2" max="16" value="8" class="slider-pixel"
                   oninput="document.getElementById('val-players').innerText = this.value">
        </div>

        <button type="submit" class="btn-gigante btn-crear" style="width: 100%; margin-top: 10px;">
            ARRINCAR PARTIDA
        </button>
        
    </form>

    <?php if (!empty($errores)) { ?>
        <div class="mensaje-error" style="position: static; transform: none; margin-top: 20px;">
            <?php foreach ($errores as $error) { ?>
                <p>> <?php echo $error; ?></p>
            <?php } ?>
        </div>
    <?php } ?>

</div>

<script src="../public/js/boton-partida.js"></script>

<!-- <script>
    // Script temporal para iluminar el botón seleccionado de Pública/Privada
    const visLabels = document.querySelectorAll('label[for^="vis_"]');
    const visRadios = document.querySelectorAll('input[name="visibilidad"]');
    visRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            visLabels.forEach(l => { l.style.backgroundColor = 'transparent'; l.style.color = l.style.borderColor; });
            const selectedLabel = document.querySelector(`label[for="${radio.id}"]`);
            selectedLabel.style.backgroundColor = selectedLabel.style.borderColor;
            selectedLabel.style.color = '#0b0d12';
        });
    });

    // Script temporal para iluminar las Vidas
    const vidasLabels = document.querySelectorAll('label[for^="vidas_"]');
    const vidasRadios = document.querySelectorAll('input[name="vidas"]');
    vidasRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            vidasLabels.forEach(l => { l.style.borderColor = '#8892b0'; l.style.color = '#8892b0'; l.style.backgroundColor = 'transparent'; });
            const selectedLabel = document.querySelector(`label[for="${radio.id}"]`);
            selectedLabel.style.borderColor = '#ffeb3b';
            selectedLabel.style.color = '#0b0d12';
            selectedLabel.style.backgroundColor = '#ffeb3b';
        });
    });
    
    // Disparar eventos iniciales para que se coloreen al cargar
    document.querySelector('input[name="visibilidad"]:checked').dispatchEvent(new Event('change'));
    document.querySelector('input[name="vidas"]:checked').dispatchEvent(new Event('change'));
</script> -->