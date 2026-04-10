<a href="index.php" class="btn-volver">&lt; VOLVER</a>
<a href="?c=acceso&a=Registrarse" class="btn-intercambio signup-color">REXISTRARSE &gt;</a>
<div class="pixel-login-container">
    <h2 class="titulo-animado-pixel">INICIAR SESIÓN</h2>
    
    <form action="?c=acceso&a=ValidarEntrada" method="POST" novalidate>
        
        <div>
            <input type="text" name="email" placeholder="Email" required autocomplete="off"
                class="<?php echo isset($errores['email']) || isset($errores['login']) ? 'input-error' : ''; ?>"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <div>
            <input type="password" name="contrasinal" placeholder="Contrasinal" required
                class="<?php echo isset($errores['contrasinal']) || isset($errores['login']) ? 'input-error' : ''; ?>">
        </div>
        
        <button type="submit">ENTRAR</button>
        
    </form>

    <?php if (!empty($errores)) { ?>
            <ul class="mensaje-error">
                <?php foreach ($errores as $error) { ?>
                    <li><?php echo $error; ?></li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if (isset($mensaje_exito)) { ?>
            <div class="mensaje-exito">
                <?php echo $mensaje_exito; ?>
            </div>
        <?php } ?>

</div>