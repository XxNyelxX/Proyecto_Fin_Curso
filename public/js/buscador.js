document.getElementById('inputBuscador').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    let tarjetas = document.querySelectorAll('.tarjeta-partida');

    tarjetas.forEach(tarjeta => {
        let nombreSala = tarjeta.querySelector('.nombre-sala').innerText.toLowerCase();
        if (nombreSala.includes(filtro)) {
            tarjeta.style.display = 'flex';
        } else {
            tarjeta.style.display = 'none';
        }
    });
});