<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class DemoEscritorioProjetos extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data_empresas = [
            [
                'CNPJ' => '52.034.139/0001-50',
                'razão_social' => 'MARILAN ALIMENTOS S/A',
                'contato_nome' => 'Maria Júlia',
                'email_corporativo' => 'marilan.alimentos@outlook.com.br',
                'telefone' => '(14) 2105-6000'
            ],
            [
                'CNPJ' => '54.423.934/0001-65',
                'razão_social' => 'Aranao Transportes Rodoviarios LTDA',
                'contato_nome' => 'Leandro',
                'email_corporativo' => 'aranao.transporte@hotmail.com.br',
                'telefone' => '(14) 3417-4243'
            ],
            [
                'CNPJ' => '53.045.266/0001-90',
                'razão_social' => 'Jad Zogheib & Cia LTDA',
                'contato_nome' => 'Carlos',
                'email_corporativo' => 'confiancasupermercados@outlook.com.br',
                'telefone' => '(14) 3311-2000' 
            ],
            [
                'CNPJ' => '65.897.910/0001-64',
                'razão_social' => 'TAUSTE SUPERMERCADOS LTDA',
                'contato_nome' => 'Viviane',
                'email_corporativo' => 'tauste.supermercados@outlook.com.br',
                'telefone' => '(14) 3402-1500'
            ],
            [
                'CNPJ' => '52.123.916/0001-32',
                'razão_social' => 'Dori Alimentos S.A.',
                'contato_nome' => 'Leonardo',
                'email_corporativo' => 'dori.alimentos@outlook.com.br',
                'telefone' => '(14) 3408-3000' 
            ],
            [
                'CNPJ' => '18.656.414/0001-39',
                'razão_social' => 'DI CHIACCHIO EDUCACIONAL LTDA',
                'contato_nome' => 'Gustavo',
                'email_corporativo' => 'precisaagri@hotmail.com.br',
                'telefone' => '(14) 9999-9999' 
            ]
        ];
        $post_empresa = $this->table('Empresas_Parceiras');
        $post_empresa->insert($data_empresas)->saveData();

        $data_propostas = [
            [
                'título' => 'Sistema de Monitoramento de Validade e Estoque',
                'descricão_problema' => 'A empresa enfrenta dificuldades para acompanhar os prazos de validade dos produtos e controlar os níveis de estoque, o que pode gerar desperdícios, perdas financeiras e falta de mercadorias.',
                'empresa_id' => 1
            ],
            [
                'título' => 'Dashboard de Indicadores de Produção',
                'descricão_problema' => 'A ausência de uma visão centralizada dos indicadores de produção dificulta o acompanhamento da produtividade, a identificação de gargalos operacionais e a tomada de decisões baseada em dados.',
                'empresa_id' => 1
            ],
            [
                'título' => 'Sistema de Controle de Qualidade de Produtos',
                'descricão_problema' => 'O controle de qualidade depende de registros e verificações que podem estar dispersos, dificultando a identificação de não conformidades, o acompanhamento de inspeções e a rastreabilidade dos resultados.',
                'empresa_id' => 1
            ],
            [
                'título' => 'Plataforma de Planejamento de Rotas',
                'descricão_problema' => 'O planejamento manual das rotas de transporte pode aumentar a distância percorrida, o consumo de combustível e o tempo de entrega, reduzindo a eficiência das operações logísticas.',
                'empresa_id' => 2
            ],
            [
                'título' => 'Sistema de Gestão de Manutenção de Frota',
                'descricão_problema' => 'A falta de um controle centralizado das manutenções preventivas e corretivas dificulta o acompanhamento das condições dos veículos e pode provocar paradas inesperadas e custos operacionais elevados.',
                'empresa_id' => 2
            ],
            [
                'título' => 'Painel de Acompanhamento de Entregas',
                'descricão_problema' => 'A dificuldade de acompanhar o andamento das entregas em tempo real prejudica a identificação de atrasos, a comunicação com os clientes e a avaliação do desempenho logístico.',
                'empresa_id' => 2
            ],
            [
                'título' => 'Sistema de Análise de Vendas e Reposição',
                'descricão_problema' => 'A análise descentralizada das vendas dificulta a identificação dos produtos com maior demanda e o planejamento da reposição de mercadorias, podendo ocasionar rupturas de estoque ou excesso de produtos.',
                'empresa_id' => 3
            ],
            [
                'título' => 'Plataforma de Controle de Perdas de Mercadorias',
                'descricão_problema' => 'A falta de consolidação dos dados sobre perdas dificulta a identificação de suas principais causas, a comparação entre períodos e a definição de medidas para reduzir prejuízos.',
                'empresa_id' => 3
            ],
            [
                'título' => 'Sistema de Gestão de Preços e Promoções',
                'descricão_problema' => 'O gerenciamento de preços e promoções em diferentes categorias de produtos pode gerar inconsistências, dificultar a análise dos resultados comerciais e comprometer a competitividade.',
                'empresa_id' => 3
            ],
            [
                'título' => 'Sistema de Previsão de Demanda de Produtos',
                'descricão_problema' => 'A dificuldade de estimar a demanda futura dos produtos prejudica o planejamento de compras e estoques, podendo resultar em excesso de mercadorias ou indisponibilidade de itens procurados pelos clientes.',
                'empresa_id' => 4
            ],
            [
                'título' => 'Plataforma de Gestão de Validade e Desperdício',
                'descricão_problema' => 'A falta de monitoramento sistemático dos prazos de validade dificulta a priorização de produtos próximos do vencimento e aumenta o risco de desperdício e perdas financeiras.',
                'empresa_id' => 4
            ],
            [
                'título' => 'Dashboard de Desempenho Comercial por Categoria',
                'descricão_problema' => 'A ausência de indicadores consolidados por categoria dificulta a comparação do desempenho comercial, a identificação de oportunidades de crescimento e a avaliação dos resultados de vendas.',
                'empresa_id' => 4
            ],
            [
                'título' => 'Sistema de Rastreabilidade de Lotes',
                'descricão_problema' => 'A dificuldade de rastrear lotes de produção e relacioná-los às respectivas etapas produtivas compromete a agilidade na identificação da origem de problemas e no acompanhamento da qualidade dos produtos.',
                'empresa_id' => 5
            ],
            [
                'título' => 'Dashboard de Eficiência da Linha de Produção',
                'descricão_problema' => 'A falta de indicadores centralizados sobre o desempenho das linhas de produção dificulta a identificação de paradas, perdas de produtividade e oportunidades de melhoria nos processos industriais.',
                'empresa_id' => 5
            ],
            [
                'título' => 'Sistema de Controle de Insumos e Embalagens',
                'descricão_problema' => 'O controle pouco integrado dos insumos e das embalagens dificulta o acompanhamento do consumo, a previsão de necessidades e a prevenção de faltas que possam interromper a produção.',
                'empresa_id' => 5
            ]
        ];
        $post_proposta = $this->table('Propostas_Externas');
        $post_proposta->insert($data_propostas)->saveData();
    }
}
