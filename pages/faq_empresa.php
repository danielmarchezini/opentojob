<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o usuário está logado como empresa
$is_empresa = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'empresa';

// Perguntas frequentes para empresas
$faqs = [
    [
        'categoria' => 'Cadastro e Conta',
        'perguntas' => [
            [
                'pergunta' => 'Como cadastrar minha empresa no OpenToJob?',
                'resposta' => 'Para cadastrar sua empresa, clique no botão "Cadastre sua Empresa" na página inicial ou no menu superior. Preencha o formulário com as informações da sua empresa, incluindo nome, CNPJ, segmento, descrição, e informações de contato. Após o cadastro, você receberá um e-mail de confirmação e poderá começar a usar a plataforma imediatamente.'
            ],
            [
                'pergunta' => 'Quais informações são necessárias para criar um perfil de empresa?',
                'resposta' => 'Para criar um perfil completo, você precisará fornecer: nome da empresa, CNPJ, segmento de atuação, descrição da empresa, site, localização (cidade e estado), e-mail corporativo, telefone de contato, logo da empresa (opcional) e informações sobre a cultura organizacional.'
            ],
            [
                'pergunta' => 'Posso ter múltiplos usuários para a mesma empresa?',
                'resposta' => 'Sim, é possível adicionar múltiplos usuários para a mesma empresa. Após o cadastro inicial, acesse o painel da empresa e vá em "Configurações > Usuários" para adicionar novos usuários com diferentes níveis de permissão.'
            ],
            [
                'pergunta' => 'Como recuperar minha senha?',
                'resposta' => 'Na página de login, clique em "Esqueci minha senha". Você receberá um e-mail com instruções para criar uma nova senha. Se não receber o e-mail, verifique sua pasta de spam ou entre em contato com nosso suporte.'
            ]
        ]
    ],
    [
        'categoria' => 'Busca de Talentos',
        'perguntas' => [
            [
                'pergunta' => 'Como encontrar talentos específicos para minha vaga?',
                'resposta' => 'Utilize nossa ferramenta de busca avançada em "Buscar Talentos". Você pode filtrar por nível profissional, habilidades específicas, localização e palavras-chave. Os resultados mostrarão talentos que correspondem aos critérios selecionados, todos disponíveis para contratação imediata.'
            ],
            [
                'pergunta' => 'O que significa o nível profissional exibido nos perfis?',
                'resposta' => 'O nível profissional (Estágio, Júnior, Pleno, Sênior, etc.) é autodeclarado pelo talento e indica seu grau de experiência e autonomia na área. Este indicador ajuda a filtrar candidatos de acordo com a senioridade necessária para sua posição.'
            ],
            [
                'pergunta' => 'Posso salvar talentos para contato futuro?',
                'resposta' => 'Sim, você pode adicionar talentos aos seus favoritos clicando no ícone de coração no perfil do talento. Para acessar sua lista de favoritos, vá para "Minha Conta > Talentos Favoritos".'
            ],
            [
                'pergunta' => 'Todos os talentos estão realmente disponíveis para trabalhar?',
                'resposta' => 'Sim, o OpenToJob é especializado em conectar empresas a talentos que estão prontos para iniciar imediatamente. Diferente de outras plataformas, nosso foco são profissionais que estão ativamente buscando oportunidades e disponíveis para contratação imediata.'
            ]
        ]
    ],
    [
        'categoria' => 'Contato e Comunicação',
        'perguntas' => [
            [
                'pergunta' => 'Como entrar em contato com um talento?',
                'resposta' => 'Para entrar em contato, acesse o perfil do talento e clique no botão "Entrar em Contato". Você poderá enviar uma mensagem direta através da plataforma. O talento receberá uma notificação e poderá responder pela própria plataforma ou pelo e-mail cadastrado.'
            ],
            [
                'pergunta' => 'Os talentos podem entrar em contato com minha empresa?',
                'resposta' => 'Não, no OpenToJob apenas empresas podem iniciar o contato com talentos. Isso garante que os talentos recebam apenas propostas relevantes e genuinamente interessadas, melhorando a qualidade das interações.'
            ],
            [
                'pergunta' => 'Como saber se um talento visualizou minha mensagem?',
                'resposta' => 'Na seção "Mensagens" do seu painel, você pode acompanhar o status de todas as mensagens enviadas. Um ícone indicará se a mensagem foi entregue, visualizada ou respondida.'
            ],
            [
                'pergunta' => 'Posso exportar os dados de contato dos talentos?',
                'resposta' => 'Por questões de privacidade e segurança, não permitimos a exportação em massa de dados de contato. No entanto, ao estabelecer comunicação com um talento, você terá acesso às informações necessárias para prosseguir com o processo seletivo.'
            ]
        ]
    ],
    [
        'categoria' => 'Função "Procura-se"',
        'perguntas' => [
            [
                'pergunta' => 'O que é a função "Procura-se"?',
                'resposta' => 'A função "Procura-se" permite que empresas publiquem anúncios específicos sobre os perfis profissionais que estão buscando, mesmo que não tenham uma vaga formal aberta. É uma forma de sinalizar para os talentos quais perfis sua empresa está interessada em conhecer.'
            ],
            [
                'pergunta' => 'Como criar um anúncio "Procura-se"?',
                'resposta' => 'Acesse seu painel de empresa e clique em "Gerenciar Demandas > Nova Demanda". Preencha o formulário especificando o título, descrição detalhada do perfil desejado, nível de experiência, modelo de trabalho e outras informações relevantes.'
            ],
            [
                'pergunta' => 'Qual a diferença entre "Procura-se" e uma vaga tradicional?',
                'resposta' => 'Enquanto uma vaga tradicional geralmente está associada a uma posição específica e imediata na empresa, o "Procura-se" é mais flexível e pode ser usado para mapear talentos para futuras oportunidades ou para necessidades que ainda não foram formalizadas como vagas.'
            ],
            [
                'pergunta' => 'Por quanto tempo meu anúncio "Procura-se" fica ativo?',
                'resposta' => 'Os anúncios "Procura-se" ficam ativos por 30 dias por padrão, mas você pode alterar este período ou desativar manualmente a qualquer momento através do painel de gerenciamento de demandas.'
            ]
        ]
    ],
    [
        'categoria' => 'Planos e Pagamentos',
        'perguntas' => [
            [
                'pergunta' => 'O cadastro de empresa é gratuito?',
                'resposta' => 'Sim, o cadastro básico de empresa é gratuito e permite acesso a funcionalidades essenciais da plataforma.'
            ],
            [
                'pergunta' => 'Quais são os planos disponíveis para empresas?',
                'resposta' => 'Oferecemos um plano único e gratuito para todas as empresas, com acesso completo a todas as funcionalidades da plataforma.'
            ],
            [
                'pergunta' => 'Como funciona a cobrança dos planos pagos?',
                'resposta' => 'O OpenToJob é totalmente gratuito para empresas. Não há planos pagos ou cobranças adicionais.'
            ],
            [
                'pergunta' => 'Existe algum custo por contratação?',
                'resposta' => 'Não, o OpenToJob é completamente gratuito. Não há custos por contratação ou taxas adicionais de qualquer tipo.'
            ]
        ]
    ],
    [
        'categoria' => 'Suporte e Ajuda',
        'perguntas' => [
            [
                'pergunta' => 'Como obter suporte técnico?',
                'resposta' => 'Para suporte técnico, acesse a seção "Ajuda" no rodapé do site ou envie um e-mail para contato@opentojob.com.br.'
            ],
            [
                'pergunta' => 'Como reportar um problema com um perfil de talento?',
                'resposta' => 'Se encontrar algum problema com um perfil de talento (informações incorretas, comportamento inadequado, etc.), clique no botão "Reportar" disponível no perfil do talento ou entre em contato com nosso suporte.'
            ]
        ]
    ]
];
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar com categorias -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Categorias</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($faqs as $index => $categoria): ?>
                        <a href="#categoria-<?php echo $index; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?php echo $categoria['categoria']; ?>
                            <span class="badge bg-primary rounded-pill"><?php echo count($categoria['perguntas']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-info text-dark">
                    <h5 class="mb-0">Precisa de mais ajuda?</h5>
                </div>
                <div class="card-body">
                    <p>Não encontrou o que procurava? Entre em contato com nosso suporte:</p>
                    <a href="<?php echo SITE_URL; ?>/?route=contato" class="btn btn-primary w-100">Contato</a>
                    <div class="mt-3">
                        <small>
                            <i class="fas fa-envelope me-2"></i> contato@opentojob.com.br<br>
                            <a href="https://wa.me/5531972063752" target="_blank" class="btn btn-success btn-sm mt-2 w-100">
                                <i class="fab fa-whatsapp me-2"></i> (31) 97206-3752
                            </a><br>
                            <i class="fas fa-clock me-2"></i> Segunda a Sexta, 9h às 18h
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Perguntas Frequentes - Empresas</h1>
            </div>
            
            <div class="alert alert-info">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-info-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5>Bem-vindo ao centro de ajuda para empresas</h5>
                        <p class="mb-0">Aqui você encontra respostas para as dúvidas mais comuns sobre como utilizar o OpenToJob para encontrar talentos prontos para trabalhar. Navegue pelas categorias ou utilize a busca para encontrar informações específicas.</p>
                    </div>
                </div>
            </div>
            
            <!-- Acordeão com perguntas e respostas -->
            <?php foreach ($faqs as $index => $categoria): ?>
            <div class="faq-category mb-4" id="categoria-<?php echo $index; ?>">
                <h2 class="border-bottom pb-2"><?php echo $categoria['categoria']; ?></h2>
                
                <div class="accordion" id="accordion-<?php echo $index; ?>">
                    <?php foreach ($categoria['perguntas'] as $i => $item): ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading-<?php echo $index; ?>-<?php echo $i; ?>">
                            <button class="accordion-button <?php echo $i > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $index; ?>-<?php echo $i; ?>" aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $index; ?>-<?php echo $i; ?>">
                                <?php echo $item['pergunta']; ?>
                            </button>
                        </h3>
                        <div id="collapse-<?php echo $index; ?>-<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $index; ?>-<?php echo $i; ?>" data-bs-parent="#accordion-<?php echo $index; ?>">
                            <div class="accordion-body">
                                <?php echo $item['resposta']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- CTA para empresas não logadas -->
            <?php if (!$is_empresa): ?>
            <div class="card mt-5">
                <div class="card-body text-center py-4">
                    <h3>Pronto para encontrar talentos disponíveis?</h3>
                    <p class="mb-4">Cadastre sua empresa e comece a conectar-se com profissionais prontos para trabalhar imediatamente.</p>
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-primary btn-lg">Cadastrar Empresa</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.faq-category {
    scroll-margin-top: 20px;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

@media (max-width: 991.98px) {
    .faq-category {
        scroll-margin-top: 70px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ativar links da categoria ao clicar
    const categoryLinks = document.querySelectorAll('.list-group-item');
    categoryLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            categoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Ativar categoria com base na URL hash
    if (window.location.hash) {
        const activeLink = document.querySelector(`a[href="${window.location.hash}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
            setTimeout(() => {
                window.scrollTo({
                    top: document.querySelector(window.location.hash).offsetTop - 20,
                    behavior: 'smooth'
                });
            }, 100);
        }
    } else {
        // Ativar primeiro link por padrão
        categoryLinks[0]?.classList.add('active');
    }
});
</script>
