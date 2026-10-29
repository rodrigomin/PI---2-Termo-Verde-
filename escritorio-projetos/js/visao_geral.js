
const allStatus = document.querySelectorAll('.status');
const statusMain = document.getElementById('status');

var data = [
    {
        value: 0,
        color:"#F7464A",
        highlight: "#FF5A5E",
        label: "Em Excecução"
    },
    {
        value: 0,
        color: "#4658bf",
        highlight: "#5a66d3",
        label: "Em Triagem"
    },
    {
        value: 0,
        color: "#FDB45C",
        highlight: "#FFC870",
        label: "Submetidos"
    },
    {
        value: 0,
        color: "#72fd5c",
        highlight: "#379f27",
        label: "Concluído"
    },
    {
        value: 0,
        color: "#c3c3c3",
        highlight: "#d9d9d9",
        label: "Sem Status"
    }
];

var data2 = [
    {
        value: 0,
        color:"#F7464A",
        highlight: "#FF5A5E",
        label: "Em Execução"
    },
    {
        value: statusMain.dataset.total,
        color: "#82181a",
        highlight: "#82181a",
        label: "Não em execução"
    }
];


allStatus.forEach((status) => {
    let nome = status.dataset.name;
    let qnt = status.dataset.qnt;

    switch (nome) {
        case "Em Execução":
            data[0].value = qnt;
            data2[0].value = qnt;
            break;
        case "Em Triagem":
            data[1].value = qnt;
            break;
        case "Submetidos":
            data[2].value = qnt;
            break;
        case "Concluido":
            data[3].value = qnt;
            break;
        case "Sem status":
            data[4].value = qnt;
            break;
        default:
            console.log(nome, ": Não está presente na possibilidade de nomes do visao_geral.js")
    }
})


var ctx = document.getElementById("chart").getContext("2d");
new Chart(ctx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: data.map(item => item.value),
            backgroundColor: data.map(item => item.color),
            borderColor: data.map(item => item.color),
            hoverOffset: 20,
            hoverBorderWidth: 0,
            spacing: 5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true, 
        
        radius: '80%', 
        layout: {
            padding: 0
        },

        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1000 // Tempo da animação em milissegundos (opcional)
        }
    }
}); 


var semiCircle = document.getElementById("semi-circle").getContext("2d");
new Chart(semiCircle, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: data2.map(item => item.value),
            backgroundColor: data2.map(item => item.color),
            borderColor: data2.map(item => item.color),
            hoverOffset: 0,
            hoverBorderWidth: 0,
            spacing: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true, 
        
        circumference: 180,
        rotation: -90,
        radius: '90%', 

        layout: {
            top: 0,
            bottom: 0,
            left: 0,
            right: 0
        },
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1000
        }
    }
}); 

const demandasCadastradas = document.getElementById('demandasCadastradas');

const kpis = document.querySelectorAll('.kpi')

kpis.forEach((kpi) => {
    if (kpi && !kpi.classList.contains('show')) {
        const final = kpi.innerText;
        let inicial = 0;

        const tempoMinimo = 1;
        const tempoMaximo = 1000;

        function atualizarContador() {
            let atual = Number(inicial);

            if (atual < final) {
                inicial += 1;
                kpi.innerText = atual + 1;

                let progresso = atual / final; 

                let tempo = tempoMinimo + (tempoMaximo - tempoMinimo) * (progresso ** 2); 

                //console.log(`Valor: ${atual} | Próximo passo em: ${tempo.toFixed(0)}ms`);

                setTimeout(atualizarContador, tempo);
            }
        }

        // Inicia o processo
        atualizarContador();
    }
})

