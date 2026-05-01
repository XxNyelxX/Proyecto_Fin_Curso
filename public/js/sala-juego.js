// Conectamos al servidor WebSocket en el puerto 8080 (usamos la IP local para la máquina virtual)
const socket = new WebSocket('ws://127.0.0.1:8080');

// Cuando la conexión se abre correctamente
socket.addEventListener('open', function (event) {
    console.log("%c¡Conectado a la centralita");
    
    // Aquí es donde mandamos nuestro primer paquete estructurado en JSON
    const mensajePresentacion = {
        tipo: 'conexion_nueva',
        id_usuario: MI_ID,
        id_partida: ID_PARTIDA,
        username: MI_NOMBRE,
        foto: MI_FOTO,
        es_host: SOY_HOST
    };
    
    // Convertimos el objeto JSON a texto para que pueda viajar por el cable
    socket.send(JSON.stringify(mensajePresentacion));
});

// Cuando recibimos un mensaje del servidor
socket.addEventListener('message', function (event) {
    const datos = JSON.parse(event.data);

    if (datos.tipo === 'nuevo_jugador' || datos.tipo === 'desconexion' || datos.tipo === 'recargar_mesa') {
        sincronizarMesa(); // Miramos la BD y redibujamos
    } else if (datos.tipo === 'expulsion') {
        // Comprobamos si el expulsado soy YO
        if (MI_ID == datos.id_expulsado) {
            alert("Fuches expulsado da sala polo anfitrión.");
            // Lo mandamos a la pantalla de inicio por la fuerza
            window.location.href = "?c=inicio&a=Index"; 
        } else {
            // Han echado a otro, simplemente actualizo mis sillas
            sincronizarMesa();
        }
    } else if (datos.tipo === 'bienvenida') {
        console.log(datos.mensaje);
    }
});

function sincronizarMesa() {
    // Preguntamos al controlador de PHP cómo está la mesa realmente
    fetch(`?c=partida&a=DatosSalaJSON&id=${ID_PARTIDA}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            
            const contenedor = document.querySelector('.circulo-jugadores');
            contenedor.innerHTML = ''; // Limpiamos la mesa por completo

            // Sentamos a los jugadores uno por uno según la Base de Datos
            datos.jugadores.forEach((jugador, index) => {
                const esHost = (jugador.id_usuario == datos.id_host) ? 'es-host' : '';

                // Preparamos el panel de expulsión vacío por defecto
                let panelExpulsar = '';

                // Si YO soy el Host de la sala, y este jugador NO tiene mi ID
                if (MI_ID == datos.id_host && jugador.id_usuario != MI_ID) {
                    panelExpulsar = `
                        <div class="panel-expulsar">
                            <span class="texto-expulsar">Botar a ${jugador.username}?</span>
                            <button class="btn-expulsar-accion" data-id="${jugador.id_usuario}">Botar</button>
                        </div>
                    `;
                }
                
                contenedor.innerHTML += `
                    <div class="jugador-slot slot-${index}" data-id="${jugador.id_usuario}">
                        <div class="vidas-jugador">❤ ${datos.vidas}</div>
                        <span class="nombre-jugador">${jugador.username}</span>
                        
                        <div class="contenedor-avatar-panel">
                            <div class="avatar-wrapper ${esHost}" onclick="togglePanel(this)">
                                <img src="img/avatars/${jugador.foto}" alt="Avatar">
                            </div>
                            ${panelExpulsar}
                        </div>
                        
                    </div>
                `;
            });

            // Actualizamos el contador central con la cantidad real
            const contadorObj = document.querySelector('.contador-jugadores');
            contadorObj.innerText = `${datos.jugadores.length}/${datos.max_jugadores}`;
            
            // Si el host se fue y la corona pasó a otro, actualizamos el botón Arrancar
            const accionesSala = document.querySelector('.acciones-sala');
            if (MI_ID == datos.id_host && !document.querySelector('.btn-arrancar')) {
                // Si ahora yo soy el host y antes no lo era, recargo la página 
                // entera para que PHP me pinte mis botones de poder.
                window.location.reload();
            }
        })
        .catch(error => console.error("Error al sincronizar la mesa:", error));
}

// Función para abrir/cerrar la cortina de expulsar al hacer clic en la foto
function togglePanel(elementoAvatar) {
    const contenedor = elementoAvatar.closest('.contenedor-avatar-panel');
    const panel = contenedor.querySelector('.panel-expulsar');
    const slotPrincipal = elementoAvatar.closest('.jugador-slot');

    if (panel) {
        // Cerramos otros paneles que pudieran estar abiertos
        document.querySelectorAll('.panel-expulsar.abierto').forEach(p => {
            if (p !== panel) {
                p.classList.remove('abierto');
                p.closest('.jugador-slot').style.zIndex = "1";
            }
        });

        // 2. Alternamos el clicado
        panel.classList.toggle('abierto');

        // 3. Si se abre, ponemos a este jugador por encima de toda la mesa
        if (panel.classList.contains('abierto')) {
            slotPrincipal.style.zIndex = "100";
        } else {
            slotPrincipal.style.zIndex = "1";
        }
    }
}

// Escuchamos los clics en cualquier botón de "Botar"
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('btn-expulsar-accion')) {
        const idExpulsado = e.target.getAttribute('data-id');

        // Pedimos a PHP que lo borre de la Base de Datos
        fetch(`?c=partida&a=ExpulsarJugador&id_partida=${ID_PARTIDA}&id_usuario=${idExpulsado}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    // Que el servidor eche a ese usuario
                    socket.send(JSON.stringify({
                        tipo: 'expulsion',
                        id_expulsado: idExpulsado
                    }));
                    
                    // Nos recargamos la mesa a nosotros mismos
                    sincronizarMesa();
                } else {
                    console.error("Error al expulsar:", data.mensaje);
                }
            })
            .catch(error => console.error("Error en la petición:", error));
    }
});

// Detectamos cuando el Host hace clic en "ENGADIR XOGADOR"
document.addEventListener("DOMContentLoaded", () => {
    const btnAnadirClon = document.getElementById('btnAnadirClon');
    const contadorJugadores = document.querySelector('.contador-jugadores');

    if (btnAnadirClon) {
        btnAnadirClon.addEventListener('click', (e) => {
            e.preventDefault(); 

            // Si está bloqueado, no hacemos nada
            if (btnAnadirClon.classList.contains('bloqueado')) return;

            const idPartida = btnAnadirClon.getAttribute('data-id');

            // Llamada asíncrona al controlador
            fetch(`?c=partida&a=AnadirClon&id=${idPartida}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Actualizamos el número en la bomba
                        contadorJugadores.innerText = `${data.jugadores_actuales}/${data.max_jugadores}`;
                        
                        // Si llegamos al límite, bloqueamos el botón visualmente
                        if (data.jugadores_actuales >= data.max_jugadores) {
                            btnAnadirClon.classList.add('bloqueado');
                        }

                        // Dibujamos el clon
                        socket.send(JSON.stringify({ tipo: 'recargar_mesa' }));

                        // Nos mandamos recargar a nosotros mismos también
                        sincronizarMesa();
                        
                    } else {
                        console.error("Error:", data.mensaje);
                    }
                })
                .catch(error => console.error("Error en la petición:", error));
        });
    }
});

// Si la pestaña se cierra o se recarga por cualquier motivo, disparamos el abandono
window.addEventListener('beforeunload', function () {
    navigator.sendBeacon(`?c=partida&a=Abandonar&id=${ID_PARTIDA}`);
});

// Por si la terminal está apagada
socket.addEventListener('error', function (event) {
    console.error("No se pudo conectar.");
});