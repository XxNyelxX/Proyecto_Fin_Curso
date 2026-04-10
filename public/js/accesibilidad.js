document.addEventListener("DOMContentLoaded", () => {
    const btnEstatico = document.getElementById('btnEstatico');
    
    if (localStorage.getItem('modoEstatico') === 'activado') {
        document.body.classList.add('modo-estatico');
        if(btnEstatico) {
            btnEstatico.innerText = "ANIMACIONES: APAGADO";
            btnEstatico.classList.remove('on');
        }
    }

    if (btnEstatico) {
        btnEstatico.addEventListener('click', () => {
            document.body.classList.toggle('modo-estatico');
            
            if (document.body.classList.contains('modo-estatico')) {
                localStorage.setItem('modoEstatico', 'activado');
                btnEstatico.innerText = "ANIMACIONES: APAGADO";
                btnEstatico.classList.remove('on');
            } else {
                localStorage.setItem('modoEstatico', 'desactivado');
                btnEstatico.innerText = "ANIMACIONES: ENCENDIDO";
                btnEstatico.classList.add('on');
            }
        });
    }
});