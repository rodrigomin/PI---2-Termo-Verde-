<nav aria-label="Menu principal" id='nav' class='flex flex-col justify-start items-center' style='border-right: solid #d9d9d9 3px; padding: 50px 0 50px 15px; box-sizing: border-box'>
        <img id='logo' src="./src/public/assets/cps-logo.png" alt="Logo do Escritório de Projetos" style='margin: 0 15px 20px 0; aspect-ratio: 45px; transition: all 0.3s ease-in-out;'>
        
        <hr>

        <ul id='optns' class='flex flex-col items-start w-full h-full' style="text-decoration: none; list-style-type: none; padding: 0px; margin-top: 20px; gap: 2.5rem;">
            <li id='visao-geral' class='selected'>
                <div class='interactive flex items-center' style='gap: 15px;'> 
                    <img class='icon' src='./src/public/svgs/house-solid-full.svg' alt='Visão Geral' style='aspect-ratio: 1/1;'>
                    <a class='optn semibold selected' href="visao_geral.php" aria-current="page">Visão Geral</a>
                </div>
            </li>
            <li>
                <div class='interactive flex items-center' style='gap: 15px;'>
                    <img class='icon' src='./src/public/svgs/clipboard-solid-full.svg' alt='Cadastro de Demanda' style='aspect-ratio: 1/1;'>
                    <a id="cadastro-demanda" class='optn semibold interactive' href="cadastro_demanda.php">Cadastro</a>
                </div>
            </li>
            <li>
                <div class='interactive flex items-center' style='gap: 15px;'>
                    <img class='icon' src='./src/public/svgs/users-solid-full.svg' alt='Parceiros' style='aspect-ratio: 1/1;'>
                    <a id="parceiros" class='optn semibold interactive' href="parceiros.php">Parceiros</a>
                </div>
            </li>
            <li>
                <div class='interactive flex items-center' style='gap: 15px;'>
                    <img class='icon' src='./src/public/svgs/right-from-bracket-solid-full.svg' alt='Envio de Demandas' style='aspect-ratio: 1/1;'>
                    <a id="envio-demandas" class='optn semibold interactive' href="envio_demandas.php">Envio Demandas</a>
                </div>
            </li>
        </ul>

        <button class='interactive flex flex-row items-center w-full' style='gap: 10px'>
            <img class='icon' src='./src/public/svgs/user-solid-full.svg' alt='Conta' style='aspect-ratio: 1/1;'>
            <a href="#" class='optn interactive text-red semibold'>Conta</a>
        </button>
</nav>