

const abrirEscopo = document.getElementById('ver-escopo');
const escopo = document.getElementById('escopo')
const fecharEscopo = document.getElementById('fechar-escopo')

if (abrirEscopo) {
    abrirEscopo.addEventListener('click', () => {
        escopo.classList.remove('hidden')
    })
}
if (fecharEscopo) {
    fecharEscopo.addEventListener('click', () => {
        escopo.classList.add('hidden')
    })
}

