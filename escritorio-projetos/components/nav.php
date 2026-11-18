<?php

$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
$usuarioMenu = ep_usuario_atual(); 

$fechado = $_SESSION['fechado'] ?? false;
$destino = $_SESSION['destino'] ?? 'index.php';

?>
<nav aria-label="Menu principal" id='nav' class='flex flex-col justify-start items-center <?php echo $fechado ? 'closed' : '' ?>' style='border-right: solid #d9d9d9 3px; padding: 50px 0 50px 15px; box-sizing: border-box'>
        <img id='logo' src="./src/public/assets/cps-logo.png" alt="Logo do Escritório de Projetos" style='margin: 0 15px 20px 0; aspect-ratio: 45px; transition: all 0.3s ease-in-out;'>
        <hr>

        <ul id='optns' class='flex flex-col items-start w-full h-full' style="text-decoration: none; list-style-type: none; padding: 0px; margin-top: 20px; gap: 2.5rem;">
            <a class='optn semibold <?= $paginaAtual === 'visao_geral.php' ? 'selected' : 'interactive' ?>' href="./app/nav.php?destino=visao_geral.php<?= $fechado ? '&fechado=1' : '' ?>" <?= $paginaAtual === 'visao_geral.php' ? 'aria-current="page"' : '' ?>>
                <li id='visao-geral' class='<?= $paginaAtual === 'visao_geral.php' ? 'selected' : '' ?>'>
                    <div class='interactive flex items-center' style='gap: 15px;'> 
                        <img class='icon' src='./src/public/svgs/house-solid-full.svg' alt='Visão Geral' style='aspect-ratio: 1/1;'>
                        <p>Visão Geral</p>
                    </div>
                </li>
            </a>

            <a id="cadastro-demanda" class='optn semibold interactive' href="./app/nav.php?destino=cadastro_demanda.php<?= $fechado ? '&fechado=1' : '' ?>" <?= $paginaAtual === 'cadastro_demanda.php' || $paginaAtual === 'cadastro_parceiro.php' ? 'aria-current="page"' : '' ?>>
                <li class='<?= $paginaAtual === 'cadastro_demanda.php' || $paginaAtual === 'cadastro_parceiro.php' ? 'selected' : '' ?>'>
                    <div class='interactive flex items-center' style='gap: 15px;'>
                        <img class='icon' src='./src/public/svgs/clipboard-solid-full.svg' alt='Cadastro de Demanda' style='aspect-ratio: 1/1;'>
                        <p>Cadastro</p>
                    </div>
                </li>
            </a>

            <a id="parceiros" class='optn semibold interactive' href="./app/nav.php?destino=parceiros.php<?= $fechado ? '&fechado=1' : '' ?>" <?= $paginaAtual === 'parceiros.php' || $paginaAtual === 'sobre_parceiro.php' ? 'aria-current="page"' : '' ?>>
                <li class='<?= $paginaAtual === 'parceiros.php' || $paginaAtual === 'sobre_parceiro.php' ? 'selected' : '' ?>'>
                    <div class='interactive flex items-center' style='gap: 15px;'>
                        <img class='icon' src='./src/public/svgs/users-solid-full.svg' alt='Parceiros' style='aspect-ratio: 1/1;'>
                        <p>Parceiros</p>
                    </div>
                </li>
            </a>

            <a id="envio-demandas" class='optn semibold interactive' href="./app/nav.php?destino=envio_demandas.php<?= $fechado ? '&fechado=1' : '' ?>" <?= $paginaAtual === 'envio_demandas.php' ? 'aria-current="page"' : '' ?>>
                <li class='<?= $paginaAtual === 'envio_demandas.php' ? 'selected' : '' ?>'>
                    <div class='interactive flex items-center' style='gap: 15px;'>
                        <img class='icon' src='./src/public/svgs/right-from-bracket-solid-full.svg' alt='Envio de Demandas' style='aspect-ratio: 1/1;'>
                        <p>Envio Demandas</p>
                    </div>
                </li>
            </a>
        </ul>

        <?php if (
            $usuarioMenu !== null
            && ($usuarioMenu['perfil'] ?? '') === 'administrador'
        ): ?>
            <ul class='w-full'>
                <a class='optn semibold <?= $paginaAtual === 'aprovacao_de_parceiros.php' ? 'selected' : 'interactive' ?>' href="./app/nav.php?destino=aprovacao_de_parceiros.php<?= $fechado ? '&fechado=1' : '' ?>" <?= $paginaAtual === 'aprovacao_de_parceiros.php' ? 'aria-current="page"' : '' ?>> 
                    <li class="<?= $paginaAtual === 'aprovacao_de_parceiros.php' ? 'selected' : '' ?>">
                        <button id='aprovacao-parceiros' class='interactive flex flex-row items-center w-full semibold' style='gap: 10px; color: #B71111;'>
                                <img class='icon' src='./src/public/svgs/square-check-solid-full.svg' alt='Aprovação de Parceiros' style='aspect-ratio: 1/1;'>
                                <p>Aprovação</p> 
                        </button>
                    </li>
                </a>
            </ul>
        <?php endif; ?>
</nav>