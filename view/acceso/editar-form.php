<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<div class="pixel-login-container">
    <h2 class="titulo-animado-pixel">MODIFICAR PERFIL</h2>
    
    <form action="?c=acceso&a=ActualizarPerfil" method="POST" enctype="multipart/form-data">
        
        <div style="text-align: center; margin-bottom: 20px;">
            <?php if (!empty($usuario->foto)) { ?>
                <img src="../public/img/avatars/<?php echo htmlspecialchars($usuario->foto); ?>" alt="Foto de perfil" 
                    style="width: 150px; height: 150px; object-fit: cover; box-shadow: 4px 4px 0px #000; border: 2px solid #fff;">
            <?php } else { ?>
                <img src="img/perfiles/default.png" alt="Foto de perfil" 
                    style="width: 150px; height: 150px; object-fit: cover; box-shadow: 4px 4px 0px #000; border: 2px solid #fff;">
            <?php } ?>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; display: block; margin-bottom: 10px;">Cambiar Avatar:</label>
            
            <label for="subida-oculta" class="btn-archivo <?php echo isset($errores['nueva_foto']) ? 'input-error' : ''; ?>">
                SUBIR FOTO
            </label>
            
            <input id="subida-oculta" type="file" name="nueva_foto" accept="image/*" style="display: none;">
        </div>
        
        <div>
            <label style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; display: block; margin-bottom: 5px;">Cambiar nome de usuario:</label>
            <input type="text" name="nuevo_username" placeholder="Novo nombre" autocomplete="off"
                class="<?php echo isset($errores['nuevo_username']) ? 'input-error' : ''; ?>">
        </div>


        <div style="margin-top: 10px;">
            <h3 style="color: #00e5ff; font-family: 'VT323', monospace; font-size: 1.5rem; margin-top: 0; margin-bottom: 15px;">CAMBIAR CONTRASINAL</h3>

            <div style="margin-bottom: 15px;">
                <label style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; display: block; margin-bottom: 5px;">Contrasinal actual:</label>
                <input type="password" name="contraseña_actual" placeholder="**********" autocomplete="off" 
                    class="<?php echo isset($errores['contraseña_actual']) ? 'input-error' : ''; ?>">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; display: block; margin-bottom: 5px;">Novo contrasinal:</label>
                <input type="password" name="nueva_contraseña" placeholder="Novo contrasinal" autocomplete="off" 
                    class="<?php echo isset($errores['nueva_contraseña']) ? 'input-error' : ''; ?>">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="color: #fff; font-family: 'VT323', monospace; font-size: 1.2rem; display: block; margin-bottom: 5px;">Repetir novo contrasinal:</label>
                <input type="password" name="confirmar_contraseña" placeholder="Repetir contrasinal" autocomplete="off" 
                    class="<?php echo isset($errores['confirmar_contraseña']) ? 'input-error' : ''; ?>">
            </div>
        </div>
        
        <button type="submit" style="background-color: #ffeb3b; color: #0b0d12; margin-top: 15px;">GARDAR CAMBIOS</button>
        
    </form>

    <?php if (!empty($errores)) { ?>
        <div class="mensaje-error">
            <?php foreach ($errores as $error) { ?>
                <p>> <?php echo $error; ?></p>
            <?php } ?>
        </div>
    <?php } ?>
    
    <?php if (isset($mensaje_exito)) { ?>
        <div class="mensaje-exito">
            <p>> <?php echo $mensaje_exito; ?></p>
        </div>
    <?php } ?>

</div>

<script src="../public/js/boton-foto.js"></script>