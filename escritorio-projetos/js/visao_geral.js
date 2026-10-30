
var data = [
    {
        value: 100,
        color:"#F7464A",
        highlight: "#FF5A5E",
        label: "Red"
    },
    {
        value: 50,
        color: "#4658bf",
        highlight: "#5a66d3",
        label: "Green"
    },
    {
        value: 100,
        color: "#FDB45C",
        highlight: "#FFC870",
        label: "Yellow"
    },
    {
        value: 100,
        color: "#72fd5c",
        highlight: "#379f27",
        label: "Yellow"
    }
];

var data2 = [
    {
        value: 100,
        color:"#F7464A",
        highlight: "#FF5A5E",
        label: "Red"
    },
    {
        value: 140,
        color: "#82181a",
        highlight: "#82181a",
        label: "Blue"
    }
];

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

if (demandasCadastradas && !demandasCadastradas.classList.contains('show')) {
    const final = 500;
    
    const tempoMinimo = 1;
    const tempoMaximo = 1000;

    function atualizarContador() {
        let atual = Number(demandasCadastradas.innerText);

        if (atual < final) {
            atual += 1;
            demandasCadastradas.innerText = atual;
            
            let progresso = atual / final; 
            
            let tempo = tempoMinimo + (tempoMaximo - tempoMinimo) * (progresso ** 99); 

            //console.log(`Valor: ${atual} | Próximo passo em: ${tempo.toFixed(0)}ms`);

            setTimeout(atualizarContador, tempo);
        }
    }

    // Inicia o processo
    atualizarContador();
}
