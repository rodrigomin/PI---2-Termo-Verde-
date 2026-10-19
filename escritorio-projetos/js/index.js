import switchNav from "./nav.js";

window.onload = () => {
    switchNav();
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
        threshold: 0.90
    })


    const observados = document.querySelectorAll('.observado-me')
    observados.forEach(elemento => {
        observer.observe(elemento);
    });
}

