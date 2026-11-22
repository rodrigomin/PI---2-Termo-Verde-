import switchNav from "./nav.js";

const nav = document.getElementById('nav')

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
    threshold: 0.25
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


// KPIS

const kpis = document.querySelectorAll('.kpi')

if (kpis) {
    kpis.forEach((kpi) => {
        const container_grid = kpi.closest('.container-grid');
        if (!kpi || !container_grid) return;

        function iniciarAnimacao() {
            // Evita rodar duas vezes se a classe mudar de novo
            if (kpi.dataset.animado) return;
            kpi.dataset.animado = 'true';

            const final = Number(kpi.innerText);
            const duracao = 2000;
            let inicioAnimacao = null;

            function atualizarContador(tempoAtual) {
                if (!inicioAnimacao) inicioAnimacao = tempoAtual;

                const tempoDecorrido = tempoAtual - inicioAnimacao;
                const progresso = Math.min(tempoDecorrido / duracao, 1);
                const progressoSuavizado = 1 - Math.pow(1 - progresso, 3);

                kpi.innerText = Math.floor(progressoSuavizado * final);

                if (progresso < 1) {
                    requestAnimationFrame(atualizarContador);
                } else {
                    kpi.innerText = final;
                }
            }

            requestAnimationFrame(atualizarContador);
        }

        // 1. Caso o container já tenha carregado visível na tela (com 'show')
        if (container_grid.classList.contains('show')) {
            iniciarAnimacao();
        } else {
            // 2. Vigia o container esperando o outro script colocar a classe 'show'
            const vigia = new MutationObserver((mutacoes, obs) => {
                if (container_grid.classList.contains('show')) {
                    iniciarAnimacao();
                    obs.disconnect(); // Para de vigiar depois que disparou
                }
            });

            vigia.observe(container_grid, { 
                attributes: true, 
                attributeFilter: ['class'] 
            });
        }
    })
}
