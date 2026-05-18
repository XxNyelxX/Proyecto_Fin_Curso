// Conectamos al servidor WebSocket en el puerto 8080 (usamos la IP local para la máquina virtual)
const socket = new WebSocket('ws://127.0.0.1:8080');

let palabraActual = ""; // Almacena las letras que el jugador está tecleando en tiempo real antes de darle al Enter.
let turnoActivoSlot = null; // Guardará el número de silla (index) donde toca escribir
let turnoGeneralMesa = null; // Para saber de quién es el turno en la sala
let estadoGeneralMesa = null; //Saber si la partida esta iniciada
let heAcertado = false;// Actúa como un seguro. Cuando pasa a 'true', bloquea el envío de más letras y activa la clase correcta
let palabrasCongeladas = {}; //Guarda las palabras correctas de cada jugador para poder visualizarlas
let sillasTemblando = {}; // Guarda qué sillas están temblando por fallar
let tiempoRestante = 0; // Guardará cuántos segundos quedan a la bomba
let intervaloBomba = null; // Guardará el temporizador (setInterval) de la bomba

// Cuando la conexión se abre correctamente
socket.addEventListener('open', function (event) {
    
    // Mandamos el js al resto de jugadores
    const mensajePresentacion = {
        tipo: 'conexion_nueva',
        id_usuario: MI_ID,
        id_partida: ID_PARTIDA,
        username: MI_NOMBRE,
        foto: MI_FOTO,
        es_host: SOY_HOST
    };
    
    // Convierte el objeto JSON a texto
    socket.send(JSON.stringify(mensajePresentacion));

    // Avisa a la pestaña Unirse de la nueva conexión
    socket.send(JSON.stringify({ tipo: 'actualizar_lista_partidas' }));
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
    } else if (datos.tipo === 'palabra_acertada') {
        // Guardamos la palabra en la memoria de los ESPECTADORES
        palabrasCongeladas[datos.slot] = datos.palabra;

        // Se la pintamos en gris instantáneamente sin esperar al setInterval
        const divPalabra = document.getElementById(`palabra-slot-${datos.slot}`);
        if (divPalabra) {
            divPalabra.innerText = datos.palabra;
            divPalabra.classList.add('correcta');
        }

        if (datos.puntos > 0) {
            mostrarAnimacionPuntos(datos.slot, datos.puntos);
        }
    } else if (datos.tipo === 'palabra_fallada') {
        // Encontramos la silla del que ha fallado
        const divPalabra = document.getElementById(`palabra-slot-${datos.slot}`);
        if (divPalabra) {
            // Le ponemos la clase de temblor
            divPalabra.classList.add('fallo');
            
            // Se la quitamos a los 400ms para que pueda volver a temblar si vuelve a fallar
            setTimeout(() => {
                divPalabra.classList.remove('fallo');
            }, 400);
        }
    }

});

function sincronizarMesa() {
    // Preguntamos al controlador de PHP cómo está la mesa realmente
    fetch(`?c=partida&a=DatosSalaJSON&id=${ID_PARTIDA}`)
        .then(respuesta => respuesta.json())
        .then(datos => {

            // Lee la URL nada más recibir los datos
            const urlParams = new URLSearchParams(window.location.search);
            const accionURL = urlParams.get('a');

            // Comprobamos si nuestra ID sigue existiendo en la base de datos de esta partida
            const sigoEnLaMesa = datos.jugadores.some(jugador => jugador.id_usuario == MI_ID);

            // Si ya no estamos (porque salimos por otra pestaña o nos echaron), a la calle
            if (!sigoEnLaMesa) {
                // Si no eres administrador, no permitimos que te quedes observando el flujo de la mesa
                if (accionURL === 'Sala' && MI_ROL != 1) {
                    window.location.href = 'index.php';
                    return;
                }
            }

            const contenedor = document.querySelector('.circulo-jugadores');

            // El código busca si existe un panel abierto en este instante y guarda el ID del jugador
            const panelAbierto = document.querySelector('.panel-expulsar.abierto');
            let idPanelAbierto = null;
            if (panelAbierto) {
                const slot = panelAbierto.closest('.jugador-slot');
                if (slot) {
                    idPanelAbierto = slot.getAttribute('data-id');
                }
            }

            contenedor.innerHTML = ''; // Limpiamos la mesa por completo

            let tieneTurno = null;

            // Si el turno del servidor es distinto al que teníamos guardado, limpiamos la palabra
            if (turnoGeneralMesa !== datos.turno_actual || estadoGeneralMesa !== datos.estado) {
                palabraActual = "";
                heAcertado = false;
                turnoGeneralMesa = datos.turno_actual;
                estadoGeneralMesa = datos.estado;

                // Borramos la palabra congelada de la silla a la que le toca AHORA
                // para que empiece en blanco su nuevo turno.
                delete palabrasCongeladas[datos.turno_actual];

                // Si la partida está iniciada, reiniciamos el reloj al máximo de tiempo de esta partida
                if (datos.estado === 'iniciada') {
                    tiempoRestante = datos.tiempo_bomba;
                    iniciarRelojBomba();
                }
            }

            // Sentamos a los jugadores uno por uno según la Base de Datos
            datos.jugadores.forEach((jugador, index) => {
                const esHost = (jugador.id_usuario == datos.id_host && datos.estado === 'esperando') ? 'es-host' : '';

                // Si la partida está iniciada y su silla coincide con el turno actual, le damos el verde
                const esTurno = (datos.estado === 'iniciada' && index == datos.turno_actual) ? 'turno-activo' : '';

                const estaEliminado = (jugador.vidas_restantes <= 0) ? 'eliminado' : '';

                // Si el turno actual cae en mi
                if (esTurno && jugador.id_usuario == MI_ID) {
                    tieneTurno = index;
                }

                // Averiguamos qué texto mostrar y con qué clase
                let textoMostrar = "";
                let claseAcertada = "";
                let claseFallo = sillasTemblando[index] ? "fallo" : ""; // Comprueba si debe temblar

                if (esTurno === 'turno-activo') {
                    // Si es su turno, muestra lo que está tecleando
                    textoMostrar = palabraActual;
                    // Si soy yo y acabo de acertar, mantengo el gris para evitar parpadeos 
                    // mientras el servidor procesa el cambio de turno
                    claseAcertada = (heAcertado && jugador.id_usuario == MI_ID) ? "correcta" : "";
                } else if (palabrasCongeladas[index]) {
                    // Si no es su turno pero tiene una palabra congelada, la mostramos en gris
                    textoMostrar = palabrasCongeladas[index];
                    claseAcertada = "correcta";
                }

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
                        <div class="vidas-jugador">❤ ${jugador.vidas_restantes}</div>
                        <span class="nombre-jugador">${jugador.username}</span>
                        
                        <div class="contenedor-avatar-panel">
                            <div class="avatar-wrapper ${esHost} ${esTurno} ${estaEliminado}" onclick="togglePanel(this)">
                                <img src="img/avatars/${jugador.foto}" alt="Avatar">
                            </div>
                            ${panelExpulsar}
                            <div class="palabra-escrita ${claseAcertada} ${claseFallo}" id="palabra-slot-${index}">${textoMostrar}</div>
                        </div>
                    </div>
                `;
            });

            turnoActivoSlot = tieneTurno;

            // El código restaura el panel abierto si existe y la partida sigue en espera
            if (idPanelAbierto && datos.estado === 'esperando') {
                const slotRestaurar = document.querySelector(`.jugador-slot[data-id="${idPanelAbierto}"]`);
                if (slotRestaurar) {
                    const panel = slotRestaurar.querySelector('.panel-expulsar');
                    if (panel) {
                        panel.classList.add('abierto');
                        slotRestaurar.style.zIndex = "100";
                    }
                }
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

                // Limpia el cartel de finalización si existiera de una partida anterior
                const cartelViejo = document.getElementById('cartel-ganador');
                if (cartelViejo) cartelViejo.remove();

            } else if (datos.estado === 'finalizada') {
                
                // Se detiene el reloj
                if (intervaloBomba) {
                    clearInterval(intervaloBomba);
                }

                // Se busca al ganador
                const ganador = datos.jugadores.find(j => j.vidas_restantes > 0);
                const nombreGanador = ganador ? ganador.username : '';

                // Se genera el texto final
                let cartelGanador = document.getElementById('cartel-ganador');
                
                if (!cartelGanador && mesaJuego) {
                    cartelGanador = document.createElement('div');
                    cartelGanador.id = 'cartel-ganador';
                    cartelGanador.className = 'texto-victoria';
                    
                    cartelGanador.innerHTML = `
                        <p style="color: #7fff00; font-size: 3rem; margin: 0; text-shadow: 3px 3px 0 #000;">GAÑOU: ${nombreGanador}</p>
                    `;
                    mesaJuego.appendChild(cartelGanador);
                }

            } else {
                // Modo sala de espera normal
                if (mesaJuego){ mesaJuego.classList.remove('partida-iniciada');}
                const accionesSala = document.querySelector('.acciones-sala');
                if (accionesSala){ accionesSala.style.display = 'flex';}

                if (textoBomba) {
                    textoBomba.innerText = `${datos.jugadores.length}/${datos.max_jugadores}`;
                    textoBomba.style.fontSize = ""; 
                    textoBomba.style.fontWeight = "";
                }
                
                const cartelViejo = document.getElementById('cartel-ganador');
                if (cartelViejo) cartelViejo.remove();
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

                    // Avisa a la pestaña Unirse para restar un jugador
                    socket.send(JSON.stringify({ tipo: 'actualizar_lista_partidas' }));
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

                        // Avisa a la pestaña Unirse para sumar el clon a la tarjeta
                        socket.send(JSON.stringify({ tipo: 'actualizar_lista_partidas' }));
                        
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
                    // Si todo fue bien, fuerza una lectura de la mesa.
                    // SincronizarMesa() leerá el estado 'iniciada' y ocultará todo.
                    sincronizarMesa();

                    // Avisa para que la partida se quite de la lista pública
                    socket.send(JSON.stringify({ tipo: 'actualizar_lista_partidas' }));
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
            // Activa el recuerdo del temblor
            sillasTemblando[turnoActivoSlot] = true; 
            
            if (divPalabra) {
                divPalabra.classList.add('fallo');
            }

            setTimeout(() => {
                sillasTemblando[turnoActivoSlot] = false; 
                
                // Busca el elemento de nuevo por si se recargó el HTML
                const divActualizado = document.getElementById(`palabra-slot-${turnoActivoSlot}`);
                if (divActualizado) {
                    divActualizado.classList.remove('fallo');
                }
            }, 400);

            // Se avisa a los demás para que vean el fallo
            socket.send(JSON.stringify({
                tipo: 'palabra_fallada',
                slot: turnoActivoSlot,
                id_partida: ID_PARTIDA
            }));
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
    // Guardamos de quién era el turno en este exacto milisegundo
    const slotJugado = turnoActivoSlot;

    fetch(`?c=partida&a=ValidarPalabra&palabra=${palabra}&id_partida=${ID_PARTIDA}&tiempo=${tiempoRestante}`)
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.existe) {

                heAcertado = true;
                palabrasCongeladas[slotJugado] = palabra;
                
                // Si existe el div
                if (divPalabra) {
                    divPalabra.classList.remove('fallo');
                    divPalabra.classList.add('correcta');
                }

                // Dispara la animación para el jugador local
                if (datos.puntos_obtenidos > 0) {
                    mostrarAnimacionPuntos(slotJugado, datos.puntos_obtenidos);
                }

                // Le decimos a los demás que congelen esta palabra
                socket.send(JSON.stringify({
                    tipo: 'palabra_acertada',
                    slot: slotJugado,
                    palabra: palabra,
                    puntos: datos.puntos_obtenidos,
                    id_partida: ID_PARTIDA
                }));
                
                // Aquí mañana meteremos el código para guardar en la BD y saltar el turno
                
            }else {
                // -- ANIMACIÓN DE FALLO --
                    sillasTemblando[slotJugado] = true; // Activa el recuerdo del temblor
                if (divPalabra) {
                    // Le volvemos a poner la clase para que inicie la animación desde cero
                    divPalabra.classList.add('fallo');
                        
                    // Se la quitamos pasados 400ms (lo que dura la animación en CSS)
                    setTimeout(() => {
                        sillasTemblando[slotJugado] = false; // Lo apaga

                        // Busca el elemento de nuevo por si se recargó el HTML
                        const divActualizado = document.getElementById(`palabra-slot-${slotJugado}`);
                        if (divActualizado) {
                            divActualizado.classList.remove('fallo');
                        }
                    }, 400);
                }
                // Le decimos a los demás que hagan temblar esta silla
                socket.send(JSON.stringify({
                    tipo: 'palabra_fallada',
                    slot: slotJugado,
                    puntos: datos.puntos_obtenidos,
                    id_partida: ID_PARTIDA
                }));
            }
        })
        .catch(error => console.error("Error en la validación:", error));
}

// --- LÓGICA DE LA BOMBA ---
function iniciarRelojBomba() {
    // Limpiamos cualquier reloj anterior para que no se pisen
    if (intervaloBomba) {
        clearInterval(intervaloBomba);
    }

    // Buscamos dónde dibujar el tiempo (tienes un elemento con la clase .esperando-mini)
    // Vamos a reutilizarlo para mostrar el tiempo debajo de la bomba
    let txtTiempo = document.getElementById('reloj-prueba');

    if (!txtTiempo) {
        // Si no existe, lo creamos y lo metemos dentro de la bomba central
        txtTiempo = document.createElement('div');
        txtTiempo.id = 'reloj-prueba';
        txtTiempo.style.color = '#ff003c';
        txtTiempo.style.fontSize = '1.8rem';
        txtTiempo.style.marginTop = '10px';
        
        const bombaCentral = document.querySelector('.bomba-central');
        if (bombaCentral) {
            bombaCentral.appendChild(txtTiempo);
        }
    }
    
    // Pintamos el primer segundo de inmediato
    if (txtTiempo) {
        txtTiempo.innerText = ` ${tiempoRestante}s`;
        txtTiempo.style.display = 'block'; // Nos aseguramos de que sea visible
    }

    // Arrancamos el contador que resta 1 cada segundo
    intervaloBomba = setInterval(() => {
        tiempoRestante--;

        // Si llega a cero (o menos)
        if (tiempoRestante <= 0) {
            clearInterval(intervaloBomba); // Detenemos el reloj

            // El HOST es el juez de la partida. Él avisa a PHP para todos.
            if (SOY_HOST) {
                fetch(`?c=partida&a=TiempoAgotado&id_partida=${ID_PARTIDA}&turno_esperado=${turnoGeneralMesa}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            sincronizarMesa();
                            socket.send(JSON.stringify({ tipo: 'recargar_mesa', id_partida: ID_PARTIDA }));
                        }
                    })
                    .catch(error => console.error("Error de conexión:", error));
            }

        } else {
            // Si no es cero, actualizamos el texto
            if (txtTiempo) {
                txtTiempo.innerText = ` ${tiempoRestante}s`;
            }
        }
    }, 1000); // Se ejecuta cada 1000 milisegundos (1 segundo)
}

// Genera un texto flotante con los puntos ganados sobre el avatar
function mostrarAnimacionPuntos(slot, puntos) {
    const avatarWrapper = document.querySelector(`.slot-${slot} .avatar-wrapper`);
    
    if (avatarWrapper) {
        // Se calculan las coordenadas del avatar en la pantalla
        const rect = avatarWrapper.getBoundingClientRect();
        
        const textoPuntos = document.createElement('div');
        textoPuntos.className = 'puntos-flotantes';
        textoPuntos.innerText = `+${puntos}`;
        
        // Se posiciona absolutamente basándose en el avatar
        textoPuntos.style.left = `${rect.left + window.scrollX + (rect.width / 2)}px`;
        textoPuntos.style.top = `${rect.top + window.scrollY - 10}px`;
        
        // Se añade al body para que la recarga de la mesa no lo elimine
        document.body.appendChild(textoPuntos);
        
        // Se elimina pasados 2.5 segundos
        setTimeout(() => {
            textoPuntos.remove();
        }, 2500);
    }
}


// Por si la terminal está apagada
socket.addEventListener('error', function (event) {
    console.error("No se pudo conectar.");
});


setInterval(sincronizarMesa, 1000);
