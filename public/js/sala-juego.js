// Conectamos al servidor WebSocket en el puerto 8080 (usamos la IP local para la máquina virtual)
const socket = new WebSocket('ws://127.0.0.1:8080');

let palabraActual = "";
let turnoActivoSlot = null; // Guardará el número de silla (index) donde toca escribir
let turnoGeneralMesa = null; // Para saber de quién es el turno en la sala
let estadoClasePalabra = '';
let heAcertado = false;

// Cuando la conexión se abre correctamente
socket.addEventListener('open', function (event) {
    
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
            // Lo mandamos a la pantalla de inicio por la fuerza
            window.location.href = "?c=inicio&a=Index"; 
        } else {
            // Han echado a otro, simplemente actualizo mis sillas
            sincronizarMesa();
        }
    } else if (datos.tipo === 'bienvenida') {
        console.log(datos.mensaje);
    } else if (datos.tipo === 'tecleando') {
        palabraActual = datos.palabra; // Guardamos la palabra que nos llega
        
        // Pintamos las letras al instante en el HTML del jugador que escribe
        const divPalabra = document.getElementById(`palabra-slot-${datos.slot}`);
        if (divPalabra) {
            divPalabra.innerText = palabraActual;
        }
    }

});

function sincronizarMesa() {
    // Preguntamos al controlador de PHP cómo está la mesa realmente
    fetch(`?c=partida&a=DatosSalaJSON&id=${ID_PARTIDA}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            
            const contenedor = document.querySelector('.circulo-jugadores');
            contenedor.innerHTML = ''; // Limpiamos la mesa por completo

            let tieneTurno = null;

            // Sentamos a los jugadores uno por uno según la Base de Datos
            datos.jugadores.forEach((jugador, index) => {
                const esHost = (jugador.id_usuario == datos.id_host && datos.estado === 'esperando') ? 'es-host' : '';

                // Si la partida está iniciada y su silla coincide con el turno actual, le damos el verde
                const esTurno = (datos.estado === 'iniciada' && index == datos.turno_actual) ? 'turno-activo' : '';

                // Si el turno actual cae en mi
                if (esTurno && jugador.id_usuario == MI_ID) {
                    tieneTurno = index;
                }

                let textoEscrito = (esTurno === 'turno-activo') ? palabraActual : "";

                // Preparamos el panel de expulsión vacío por defecto
                let panelExpulsar = '';

                // Si YO soy el Host de la sala, y este jugador NO tiene mi ID
                if (MI_ID == datos.id_host && jugador.id_usuario != MI_ID && datos.estado === 'esperando') {
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
                            <div class="avatar-wrapper ${esHost} ${esTurno}" onclick="togglePanel(this)">
                                <img src="img/avatars/${jugador.foto}" alt="Avatar">
                            </div>
                            ${panelExpulsar}
                            <div class="palabra-escrita" id="palabra-slot-${index}">${textoEscrito}</div>
                        </div>
                        
                    </div>
                `;
            });

            turnoActivoSlot = tieneTurno;

            // Si el turno del servidor es distinto al que teníamos guardado, limpiamos la palabra
            if (turnoGeneralMesa !== datos.turno_actual) {
                palabraActual = "";
                turnoGeneralMesa = datos.turno_actual;
            }

            // --- INICIO GESTIÓN VISUAL DE LA MESA ---
            const textoBomba = document.querySelector('.contador-jugadores');
            const mesaJuego = document.querySelector('.mesa-juego');

            if (datos.estado === 'iniciada') {
                // Añadimos la clase para que CSS haga desaparecer los botones
                if (mesaJuego){ mesaJuego.classList.add('partida-iniciada');}
                
                // Ocultamos el bloque general de acciones por seguridad extra
                const accionesSala = document.querySelector('.acciones-sala');
                if (accionesSala){ accionesSala.style.display = 'none';}

                // Quitamos el cursor de click a los avatares
                document.querySelectorAll('.avatar-wrapper').forEach(avatar => avatar.style.cursor = 'default');

                // Cambiamos los números por la sílaba gigante
                if (textoBomba) {
                    textoBomba.innerText = datos.silaba_actual;
                    textoBomba.style.fontSize = "32px";
                    textoBomba.style.fontWeight = "bold";
                }
            } else if (datos.estado !== 'finalizada'){
                // Modo sala de espera normal
                if (textoBomba) {
                    textoBomba.innerText = `${datos.jugadores.length}/${datos.max_jugadores}`;
                    textoBomba.style.fontSize = ""; 
                    textoBomba.style.fontWeight = "";
                }
            }
            
            //Compruebo que sean más de 1 jugadores para poder empezar
            if (SOY_HOST) {
                const btnArrancar = document.querySelector('.btn-arrancar');
                const txtEsperando = document.querySelector('.esperando-mini');
                const btnClon = document.querySelector('.btn-anadir-clon');
                
                // Bloqueamos o dejamos acceso a los botones de arrancar y de clones
                if (btnArrancar) {
                    if (datos.jugadores.length >= 2) {
                        btnArrancar.classList.remove('bloqueado');
                        btnArrancar.classList.add('listo');
                        btnArrancar.href = '#';
                        
                        if (txtEsperando) {
                            txtEsperando.style.display = 'none';
                        }
                    } else {
                        btnArrancar.classList.remove('listo');
                        btnArrancar.classList.add('bloqueado');
                        btnArrancar.href = '#';
                        
                        if (txtEsperando) {
                            txtEsperando.style.display = 'block';
                        }
                    }
                }

                if (btnClon) {
                    if (datos.jugadores.length >= datos.max_jugadores) {
                        btnClon.classList.add('bloqueado');
                    } else {
                        btnClon.classList.remove('bloqueado');
                    }
                }
            }

            // Si el host se fue paso los botones
            if (MI_ID == datos.id_host && !SOY_HOST) {
                
                SOY_HOST = true;
                
                // Oculto el texto de invitado y muestro el panel de control
                const panelInvitado = document.getElementById('panel-invitado');
                const panelHost = document.getElementById('panel-host');
                
                if (panelInvitado) panelInvitado.style.display = 'none';
                if (panelHost) panelHost.style.display = 'block';
            }

            if (heAcertado) {
            const divPalabra = document.getElementById(`palabra-slot-${turnoActivoSlot}`);
            if (divPalabra) {
                divPalabra.classList.add('correcta');
            }
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

// Si la pestaña se cierra o se recarga por cualquier motivo, abandonamos la sala
window.addEventListener('beforeunload', function () {
    navigator.sendBeacon(`?c=partida&a=Abandonar&id=${ID_PARTIDA}`);
});

document.addEventListener('click', function(e) {
    // Si hacemos clic en el botón de arrancar
    const btnArrancar = e.target.closest('.btn-arrancar');
    
    if (btnArrancar) {
        e.preventDefault(); // Evitamos que el '#' mueva la pantalla hacia arriba
        
        // Si el botón está bloqueado porque no hay suficientes jugadores, no hacemos nada
        if (btnArrancar.classList.contains('bloqueado')) return;

        // Hacemos la petición
        fetch(`?c=partida&a=Empezar&id=${ID_PARTIDA}`)
            .then(respuesta => respuesta.json())
            .then(datos => {
                if (datos.status === 'expulsar') {
                    // Si PHP nos dice que nos vayamos, nos vamos al inicio
                    window.location.href = 'index.php';
                } else if (datos.status === 'ok') {
                    // Si todo fue bien, forzamos una lectura de la mesa.
                    // SincronizarMesa() leerá el estado 'iniciada' y ocultará todo.
                    sincronizarMesa();
                }
            })
            .catch(error => console.error("Error ao arrincar a partida:", error));
    }
});

// --- LÓGICA DE ESCRITURA ---

document.addEventListener('keydown', function(e) {

    // Si no es mi turno, ignoramos el teclado por completo
    if (turnoActivoSlot === null) return;

    // Si pulsa Retroceso borramos la última letra
    if (e.key === "Backspace") {
        palabraActual = palabraActual.slice(0, -1);
    
    // --- Si pulsa Enter comprobamos la sílaba ---
    } else if (e.key === "Enter") {
        // Leemos la sílaba que está escrita en la pantalla
        const silabaBomba = document.querySelector('.contador-jugadores').innerText.toLowerCase();
        const divPalabra = document.getElementById(`palabra-slot-${turnoActivoSlot}`);
        
        // Verificamos si la sílaba está dentro de lo que hemos escrito
        if (palabraActual.includes(silabaBomba)) {
            validarPalabra(palabraActual, divPalabra);
        } else {
            
        }
        
        return; // Cortamos aquí para que no ejecute lo de enviar letras a los demás
    // Si pulsa una letra (de la A a la Z, o la Ñ)
    } else if (/^[a-zA-ZñÑ]$/.test(e.key)) {
        if (palabraActual.length < 50) {
            palabraActual += e.key.toLowerCase();
        }
    }

    // Inyectamos la letra instantáneamente en el HTML del jugador activo sin esperar al Fetch
    const divPalabra = document.getElementById(`palabra-slot-${turnoActivoSlot}`);
    if (divPalabra) {
        divPalabra.innerText = palabraActual;
    }

    socket.send(JSON.stringify({
        tipo: 'tecleando',
        palabra: palabraActual,
        slot: turnoActivoSlot,
        id_partida: ID_PARTIDA
    }));
});

// Función para comprobar si la palabra existe en nuestro JSON local
function validarPalabra(palabra, divPalabra) {
    fetch(`?c=partida&a=ValidarPalabra&palabra=${palabra}&id_partida=${ID_PARTIDA}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.existe) {
                console.log("✅ Palabra válida:", datos.palabra);

                heAcertado = true;
                
                // Si existe el div
                if (divPalabra) {
                    divPalabra.classList.add('correcta');
                }
                
                // Aquí mañana meteremos el código para guardar en la BD y saltar el turno
                
            } else {
                console.log("❌ La palabra no existe en el diccionario");
            }
        })
        .catch(error => console.error("Error en la validación:", error));
}


// Por si la terminal está apagada
socket.addEventListener('error', function (event) {
    console.error("No se pudo conectar.");
});


setInterval(sincronizarMesa, 1000);