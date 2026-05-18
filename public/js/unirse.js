const socket = new WebSocket('ws://127.0.0.1:8080');

// Escucha los avisos del servidor
socket.addEventListener('message', function (event) {
    const datos = JSON.parse(event.data);

    // Si hay cambios en las salas, actualiza la lista
    if (datos.tipo === 'recargar_lista_partidas') {
        actualizarLista();
    }
});

function actualizarLista() {
    // Pide la información actualizada al controlador
    fetch('?c=partida&a=ListaPartidasJSON')
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.status === 'ok') {
                const contenedor = document.getElementById('lista-partidas-tiempo-real');
                const mensajeVacio = document.getElementById('mensaje-sin-resultados');
                
                // Limpia las tarjetas actuales
                contenedor.innerHTML = ''; 

                // Si no hay partidas devueltas por la base de datos
                if (datos.partidas.length === 0) {
                    contenedor.innerHTML += `<p class="sin-datos">SEN DATOS</p>`;
                    return;
                }

                // Vuelve a añadir el mensaje oculto para el buscador
                if (mensajeVacio) {
                    contenedor.appendChild(mensajeVacio);
                }

                // Genera una tarjeta nueva por cada partida recibida
                datos.partidas.forEach(p => {
                    const llena = (p.num_jugadores >= p.max_jugadores);
                    const claseBoton = llena ? 'btn-unirse-bloqueado' : 'btn-unirse-accion';
                    const textoBoton = llena ? 'CHEA' : 'UNIRSE';
                    const claseTarjeta = llena ? 'tarjeta-partida llena' : 'tarjeta-partida';

                    // Clona el bloque del botón VER si es administrador (id_rol == 1)
                    let botonVer = '';
                    if (datos.esAdmin) {
                        botonVer = `
                            <a href="?c=partida&a=Espectar&id=${p.id_partida}" class="btn-espectar-accion">
                                VER
                            </a>
                        `;
                    }

                    // Preparamos el botón principal (si es privada y no está llena, abre modal)
                    let botonPrincipal = '';
                    if (p.visibilidad === 'privada' && !llena) {
                        botonPrincipal = `
                            <a style="cursor: pointer;" class="${claseBoton}" onclick="abrirModalContrasinal(${p.id_partida})">
                                ${textoBoton}
                            </a>
                        `;
                    } else {
                        botonPrincipal = `
                            <a href="?c=partida&a=Acceder&id=${p.id_partida}" class="${claseBoton}">
                                ${textoBoton}
                            </a>
                        `;
                    }

                    contenedor.innerHTML += `
                        <div class="${claseTarjeta}">
                            <div class="col-nombre">
                                <span class="nombre-sala">${p.nombre}</span>
                            </div>
                            <div class="col-jugadores">
                                <span class="dato-partida">${p.num_jugadores} / ${p.max_jugadores}</span>
                            </div>
                            <div class="col-bomba">
                                <span class="dato-partida">${p.tiempo_bomba}s</span>
                            </div>
                            <div class="col-vidas">
                                <span class="dato-partida">${p.vidas} ❤</span>
                            </div>
                            <div class="col-visibilidad">
                                <span class="dato-partida">${p.visibilidad}</span>
                            </div>
                            <div class="col-accion">
                                ${botonPrincipal}
                                ${botonVer}
                            </div>
                        </div>
                    `;
                });
                
                // Aplica de nuevo el filtro del buscador si el usuario estaba escribiendo
                const buscador = document.getElementById('inputBuscador');
                if (buscador && buscador.value.trim() !== '') {
                    buscador.dispatchEvent(new Event('keyup'));
                }
            }
        })
        .catch(error => console.error('Error al actualizar las partidas:', error));
}