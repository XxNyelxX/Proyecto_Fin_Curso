document.addEventListener("DOMContentLoaded", () => {
    //Botón de acceso
    const btnUserMenu = document.getElementById('btnUserMenu');
    //Botones de iniciar sesión y registrarse
    const dropdownMenu = document.getElementById('dropdownMenu');

    // Verificamos que los elementos existan en la página actual para evitar errores
    if (btnUserMenu && dropdownMenu) {
        
        btnUserMenu.addEventListener('click', (e) => {
            // Evita que el clic baje al div, al body... y cuando vuelva a subir cierre el menú por culpa del burbujeo
            // Corta el click directamente
            e.stopPropagation();
            
            // Togleamos las clases para el efecto visual y la rotación
            btnUserMenu.classList.toggle('activo');
            dropdownMenu.classList.toggle('mostrar');
        });

        // Cerrar el menú si se hace clic en cualquier otra parte de la pantalla
        document.addEventListener('click', () => {
            if (btnUserMenu.classList.contains('activo')) {
                btnUserMenu.classList.remove('activo');
                dropdownMenu.classList.remove('mostrar');
            }
        });
    }
});
