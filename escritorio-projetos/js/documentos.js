const docBtn = document.querySelectorAll('.doc-btn')
const docModal = document.getElementById('documentos')
const docFechar = document.getElementById('fechar-doc')

if (docFechar) {
    docFechar.addEventListener('click', () => {
        docModal.classList.add('hidden')
    })
}

docBtn.forEach((btn) => {
    btn.addEventListener('click', () => {
        docModal.classList.contains('hidden') ? docModal.classList.remove('hidden') : docModal.classList.add('hidden')
    })
})
