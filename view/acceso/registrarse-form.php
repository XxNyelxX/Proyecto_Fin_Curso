<a href="index.php" class="btn-volver">&lt; VOLVER</a>

<?php if (!isset($_SESSION['user_id'])) { ?>
    <a href="?c=acceso&a=Entrar" class="btn-intercambio login-color">ENTRAR &gt;</a>
<?php } ?>

<div class="contenedor-central">
    <div class="pixel-login-container">
        <h2 class="titulo-animado-pixel">REXISTRARSE</h2>
        
        <form action="?c=acceso&a=ValidarRegistro" method="POST" novalidate>
            
            <div>
                <input type="text" name="username" placeholder="Usuario" autocomplete="off"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    class="<?php echo isset($errores['username']) ? 'input-error' : ''; ?>">
            </div>
            
            <div>
                <input type="email" name="email" placeholder="Email" autocomplete="off"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    class="<?php echo isset($errores['email']) ? 'input-error' : ''; ?>">
            </div>
            
            <div>
                <input type="password" name="contrasinal" placeholder="Contrasinal"
                    autocomplete="new-password"
                    class="<?php echo isset($errores['contrasinal']) ? 'input-error' : ''; ?>">
            </div>
            
            <div>
                <input type="password" name="confirmar_contrasinal" placeholder="Repetir Contrasinal"
                    autocomplete="new-password"
                    class="<?php echo isset($errores['confirmar_contrasinal']) ? 'input-error' : ''; ?>">
            </div>
            
            <button type="submit">CREAR CONTA</button>
            
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
                <br>
                <a href="?c=acceso&a=Entrar">PREME AQUÍ PARA ENTRAR</a>
            </div>
        <?php } ?>

    </div>
</div>