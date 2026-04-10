document.addEventListener('DOMContentLoaded', function() {
    // Buscamos el input oculto
    const inputSubida = document.getElementById('subida-oculta');
    
    // Solo ejecutamos el código si estamos en la pantalla de Editar Perfil
    if (inputSubida) {
        inputSubida.addEventListener('change', function(e) {
            let label = document.querySelector('label[for="subida-oculta"]');
            
            // Si el usuario ha seleccionado un archivo
            if (e.target.files.length > 0) {
                
                // Cambiamos el texto
                label.textContent = "FOTO LISTA";
            } else {
                // Si el usuario abre la ventana y cancela, vuelve al estado original
                label.textContent = "SUBIR FOTO";
                label.style.backgroundColor = "";
                label.style.borderColor = "";
                label.style.color = "";
            }
        });
    }
});