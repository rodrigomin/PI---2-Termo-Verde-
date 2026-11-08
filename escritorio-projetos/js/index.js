import switchNav from "./nav.js";

const nav = document.getElementById('nav')
console.log(nav)
if (nav) {
    switchNav();
}
const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if(entry.isIntersecting) {
            entry.target.classList.add('show')
            observer.unobserve(entry.target)
        }
    })
}, {
    root: null,
    rootMargin: "0px",
    threshold: 0.20
})
const observados = document.querySelectorAll('.observado-me')
observados.forEach(elemento => {
    observer.observe(elemento);
});

const contaHeader = document.getElementById('user-button');

if (contaHeader) {
    contaHeader.addEventListener('click', () => {
        const contaDropdown = document.querySelector('.card-header');
        if (contaDropdown) {
            contaDropdown.classList.toggle('show');
        }
    })
}
