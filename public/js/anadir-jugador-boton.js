document.addEventListener("DOMContentLoaded", () => {

    const btnAnadirClon = document.getElementById('btnAnadirClon');
    const contadorJugadores = document.querySelector('.contador-jugadores');

    if (btnAnadirClon) {
        btnAnadirClon.addEventListener('click', (e) => {
            e.preventDefault(); // Evita que el href="#" te salte arriba de la página

            // Si está bloqueado, no hacemos nada
            if (btnAnadirClon.classList.contains('bloqueado')) return;

            const idPartida = btnAnadirClon.getAttribute('data-id');

            // Llamada asíncrona al controlador
            fetch(`?c=partida&a=AnadirClon&id=${idPartida}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // 1. Actualizamos el número en la bomba
                        contadorJugadores.innerText = `${data.jugadores_actuales}/${data.max_jugadores}`;
                        
                        // 2. Si llegamos al límite, bloqueamos el botón visualmente
                        if (data.jugadores_actuales >= data.max_jugadores) {
                            btnAnadirClon.classList.add('bloqueado');
                        }

                        // Nota: El monigote nuevo no aparecerá en el círculo hasta que recargues,
                        // en el futuro de esto se encargarán los WebSockets pintándolo en tiempo real.
                    } else {
                        console.error("Error:", data.mensaje);
                    }
                })
                .catch(error => console.error("Error en la petición:", error));
        });
    }
});