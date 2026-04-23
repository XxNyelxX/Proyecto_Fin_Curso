const inputContrasena = document.getElementById('campo_contrasena');
const radioPublica = document.getElementById('vis_publica');
const radioPrivada = document.getElementById('vis_privada');

// Si escribes o borras en el campo de contraseña
inputContrasena.addEventListener('input', function() {
    if (this.value.trim() !== '') {
        radioPrivada.checked = true;
    } else {
        radioPublica.checked = true;
    }
});

// Si pulsas en el botón de Pública, vacía la contraseña
radioPublica.addEventListener('change', function() {
    if (this.checked) {
        inputContrasena.value = '';
    }
});