// Variable global para recordar a qué partida intentamos entrar
let partidaSeleccionadaId = null;

function abrirModalContrasinal(id_partida) {
    partidaSeleccionadaId = id_partida;
    
    const modal = document.getElementById('modalContrasinal');
    const inputPass = document.getElementById('inputModalPass');
    const errorMsg = document.getElementById('errorModalPass');

    // Limpia cualquier cosa que hubiera escrita antes
    inputPass.value = '';
    inputPass.classList.remove('input-error');
    errorMsg.style.display = 'none';

    // Muestra el modal añadiendo la clase de la animación
    modal.classList.add('activo');

    // Hace autofocus en el input para que el usuario pueda escribir directamente
    // Le da 100ms de margen para que la animación termine antes de hacer focus
    setTimeout(() => inputPass.focus(), 100);
}

function ocultarModalContrasinal() {
    const modal = document.getElementById('modalContrasinal');
    modal.classList.remove('activo');
    partidaSeleccionadaId = null; // Resetea la memoria
}

function cerrarModalDesdeFuera(event) {
    // Si el clic se hace fuera se cierra
    if (event.target.id === 'modalContrasinal') {
        ocultarModalContrasinal();
    }
}

function procesarContrasinalModal() {
    const inputPass = document.getElementById('inputModalPass');
    const errorMsg = document.getElementById('errorModalPass');
    const contrasena = inputPass.value.trim();

    if (contrasena === '') {
        mostrarErrorModal("Escribe un contrasinal primeiro.");
        return;
    }
    if (contrasena.length < 3) {
        mostrarErrorModal("O contrasinal debe ter polo menos 3 caracteres.");
        return;
    }
    if (contrasena.length > 15) {
        mostrarErrorModal("O contrasinal non pode superar os 15 caracteres.");
        return;
    }

    // Se envía la petición a PHP si todo es correcto
    fetch(`?c=partida&a=VerificarContrasena&id=${partidaSeleccionadaId}&pass=${encodeURIComponent(contrasena)}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.status === 'ok') {
                window.location.href = `?c=partida&a=Acceder&id=${partidaSeleccionadaId}`;
            } else {
                mostrarErrorModal(datos.mensaje || "Contrasinal incorrecto.");
            }
        })
        .catch(error => {
            console.error("Error validando contrasinal:", error);
            mostrarErrorModal("Erro de conexión co servidor.");
        });
}

function mostrarErrorModal(mensaje) {
    const inputPass = document.getElementById('inputModalPass');
    const errorMsg = document.getElementById('errorModalPass');
    
    inputPass.classList.add('input-error');
    errorMsg.innerText = `> ${mensaje}`;
    errorMsg.style.display = 'block';
}

//Permitir que el usuario pulse la tecla "Enter" para entrar sin usar el ratón
document.addEventListener('DOMContentLoaded', () => {
    const inputModalPass = document.getElementById('inputModalPass');
    if (inputModalPass) {
        inputModalPass.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Evita que envíe un formulario fantasma
                procesarContrasinalModal();
            }
        });
    }
});