<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o usuário está logado como talento
$is_talento = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'talento';

// Perguntas frequentes para talentos
$faqs = [
    [
        'categoria' => 'Cadastro e Perfil',
        'perguntas' => [
            [
                'pergunta' => 'Como me cadastrar no OpenToJob?',
                'resposta' => 'Para se cadastrar, clique no botão "Cadastre-se como Talento" na página inicial ou no menu superior. Preencha o formulário com seus dados pessoais e profissionais. Após o cadastro, você receberá um e-mail de confirmação e poderá completar seu perfil para aumentar suas chances de ser encontrado por empresas.'
            ],
            [
                'pergunta' => 'Quais informações devo incluir no meu perfil?',
                'resposta' => 'Para um perfil completo e atrativo, inclua: dados pessoais, foto profissional, profissão, nível profissional (Estágio, Júnior, Pleno, Sênior, etc.), carta de apresentação, resumo profissional, habilidades técnicas, formação acadêmica, experiências anteriores e links para portfólio ou GitHub, se aplicável. Quanto mais completo seu perfil, maiores as chances de ser encontrado por empresas.'
            ],
            [
                'pergunta' => 'Como definir meu nível profissional?',
                'resposta' => 'Ao editar seu perfil, você encontrará um campo para selecionar seu nível profissional. Escolha a opção que melhor representa sua experiência e autonomia: Estágio (iniciante), Júnior (1-2 anos de experiência), Pleno (3-5 anos), Sênior (mais de 5 anos com autonomia), Especialista ou Gerente. Seja honesto para garantir correspondências adequadas com as expectativas das empresas.'
            ],
            [
                'pergunta' => 'Posso controlar quem vê meu perfil?',
                'resposta' => 'Sim, nas configurações de privacidade do seu perfil, você pode escolher se seus dados estarão visíveis para todas as empresas cadastradas ou apenas para aquelas com quem você já interagiu. Também é possível ocultar informações específicas como telefone e e-mail até que você aceite iniciar uma conversa com a empresa.'
            ]
        ]
    ],
    [
        'categoria' => 'Visibilidade e Busca',
        'perguntas' => [
            [
                'pergunta' => 'Como aumentar minhas chances de ser encontrado por empresas?',
                'resposta' => 'Para aumentar sua visibilidade: 1) Mantenha seu perfil 100% completo; 2) Detalhe suas habilidades técnicas e soft skills; 3) Escreva uma carta de apresentação clara e objetiva; 4) Mantenha seu status como "Disponível para contratação imediata"; 5) Atualize regularmente suas informações; 6) Adicione palavras-chave relevantes para sua área no resumo profissional.'
            ],
            [
                'pergunta' => 'Quais empresas podem ver meu perfil?',
                'resposta' => 'Seu perfil pode ser visualizado por todas as empresas cadastradas e verificadas no OpenToJob, a menos que você tenha restringido a visibilidade nas configurações de privacidade. Empresas buscam talentos com base em critérios como habilidades, nível profissional e localização.'
            ],
            [
                'pergunta' => 'Posso saber quais empresas visualizaram meu perfil?',
                'resposta' => 'Sim, na seção "Estatísticas" do seu painel, você pode ver quais empresas visualizaram seu perfil nos últimos 30 dias. Esta funcionalidade está disponível para todos os talentos cadastrados.'
            ],
            [
                'pergunta' => 'O que significa estar "OpenToJob"?',
                'resposta' => 'Estar "OpenToJob" significa que você está disponível para iniciar um novo trabalho imediatamente. Diferente de outras plataformas onde profissionais podem estar apenas "abertos a oportunidades" enquanto empregados, no OpenToJob o foco são pessoas que precisam de uma oportunidade agora e estão prontas para começar imediatamente.'
            ]
        ]
    ],
    [
        'categoria' => 'Comunicação com Empresas',
        'perguntas' => [
            [
                'pergunta' => 'Como as empresas entram em contato comigo?',
                'resposta' => 'As empresas podem entrar em contato através do sistema de mensagens da plataforma. Você receberá uma notificação por e-mail quando receber uma nova mensagem. Para visualizar e responder, acesse a seção "Mensagens" no seu painel.'
            ],
            [
                'pergunta' => 'Posso iniciar contato com empresas?',
                'resposta' => 'No OpenToJob, apenas empresas podem iniciar o contato com talentos. Isso garante que você receba apenas propostas relevantes e genuinamente interessadas. Você pode responder às mensagens recebidas e continuar a conversa, mas não pode iniciar uma nova conversa com uma empresa.'
            ],
            [
                'pergunta' => 'Como responder a uma proposta de empresa?',
                'resposta' => 'Quando receber uma mensagem de uma empresa, acesse a seção "Mensagens" no seu painel. Clique na mensagem para abri-la e utilize o campo de resposta para continuar a conversa. Seja profissional e objetivo em suas respostas, fornecendo as informações solicitadas pela empresa.'
            ],
            [
                'pergunta' => 'Posso bloquear uma empresa?',
                'resposta' => 'Sim, se você receber mensagens inadequadas ou não deseja mais receber contato de uma empresa específica, você pode bloqueá-la. Na mensagem da empresa, clique no botão "Opções" e selecione "Bloquear Empresa". A empresa não será notificada sobre o bloqueio, mas não poderá mais enviar mensagens para você.'
            ]
        ]
    ],
    [
        'categoria' => 'Currículo e Documentos',
        'perguntas' => [
            [
                'pergunta' => 'Como adicionar meu currículo ao perfil?',
                'resposta' => 'Para adicionar seu currículo, acesse "Editar Perfil" e na seção "Documentos", clique em "Enviar Currículo". Você pode fazer upload de arquivos nos formatos PDF, DOC ou DOCX com tamanho máximo de 5MB. Recomendamos o formato PDF para garantir que a formatação seja mantida.'
            ],
            [
                'pergunta' => 'Quem pode baixar meu currículo?',
                'resposta' => 'Apenas empresas que já iniciaram contato com você podem baixar seu currículo. Isso protege suas informações pessoais e garante que apenas empresas genuinamente interessadas tenham acesso ao documento completo.'
            ],
            [
                'pergunta' => 'Posso adicionar mais de um currículo?',
                'resposta' => 'Atualmente, o sistema permite apenas um currículo ativo por perfil. Se desejar atualizar seu currículo, você pode substituir o arquivo existente a qualquer momento através da seção "Editar Perfil".'
            ],
            [
                'pergunta' => 'Como adicionar links para portfólio ou GitHub?',
                'resposta' => 'Na seção "Editar Perfil", você encontrará campos específicos para adicionar links para seu portfólio, GitHub, LinkedIn e outros sites profissionais. Estes links serão exibidos no seu perfil e podem ser acessados diretamente pelas empresas interessadas.'
            ]
        ]
    ],
    [
        'categoria' => 'Habilidades e Competências',
        'perguntas' => [
            [
                'pergunta' => 'Como adicionar habilidades ao meu perfil?',
                'resposta' => 'Na seção "Editar Perfil", você encontrará um campo para adicionar suas habilidades. Digite cada habilidade separada por vírgula. Recomendamos incluir tanto habilidades técnicas (linguagens de programação, ferramentas, etc.) quanto soft skills (comunicação, trabalho em equipe, etc.).'
            ],
            [
                'pergunta' => 'Quantas habilidades devo adicionar?',
                'resposta' => 'Recomendamos adicionar entre 5 e 15 habilidades relevantes para sua área de atuação. Priorize as habilidades que você domina melhor e que são mais requisitadas pelo mercado. Evite adicionar habilidades que você não possui realmente, pois isso pode gerar expectativas que não serão atendidas durante o processo seletivo.'
            ],
            [
                'pergunta' => 'Como as empresas filtram por habilidades?',
                'resposta' => 'As empresas podem utilizar o sistema de busca avançada para filtrar talentos com base em habilidades específicas. Por isso, é importante incluir as palavras-chave corretas e mais utilizadas na sua área, aumentando suas chances de aparecer nos resultados de busca.'
            ],
            [
                'pergunta' => 'Posso destacar minhas principais habilidades?',
                'resposta' => 'Atualmente, todas as habilidades têm o mesmo peso de visualização. No entanto, a ordem em que você as adiciona pode influenciar como elas são exibidas. Coloque suas habilidades mais relevantes primeiro. Além disso, você pode destacar suas principais competências na sua carta de apresentação e resumo profissional.'
            ]
        ]
    ],
    [
        'categoria' => 'Processos Seletivos',
        'perguntas' => [
            [
                'pergunta' => 'Como funciona o processo seletivo no OpenToJob?',
                'resposta' => 'O processo seletivo varia de acordo com cada empresa, mas geralmente segue este fluxo: 1) A empresa visualiza seu perfil; 2) Se houver interesse, envia uma mensagem inicial; 3) Vocês conversam pela plataforma; 4) A empresa pode solicitar entrevistas, testes ou mais informações; 5) Se aprovado, a empresa faz a proposta formal. Todo o contato inicial acontece dentro da plataforma.'
            ],
            [
                'pergunta' => 'Devo responder a todas as mensagens de empresas?',
                'resposta' => 'Recomendamos responder a todas as mensagens, mesmo que não tenha interesse na oportunidade. Uma resposta educada agradecendo o contato, mas informando que não há interesse no momento, mantém uma boa relação profissional e sua reputação na plataforma.'
            ],
            [
                'pergunta' => 'Como me preparar para entrevistas?',
                'resposta' => 'Acesse nosso blog na seção "Dicas de Carreira" para encontrar artigos sobre preparação para entrevistas. Recomendamos: pesquisar sobre a empresa antes, revisar as principais perguntas da sua área, preparar exemplos concretos de suas experiências, testar equipamentos para entrevistas remotas e estar pronto para falar sobre suas expectativas salariais.'
            ],
            [
                'pergunta' => 'Posso negociar salário pela plataforma?',
                'resposta' => 'Sim, você pode discutir expectativas salariais e benefícios através do sistema de mensagens. Recomendamos ser transparente sobre suas expectativas desde o início para evitar perda de tempo em processos que não atendam suas necessidades financeiras.'
            ]
        ]
    ],
    [
        'categoria' => 'Suporte e Ajuda',
        'perguntas' => [
            [
                'pergunta' => 'Como obter suporte técnico?',
                'resposta' => 'Para suporte técnico, acesse a seção "Ajuda" no rodapé do site ou envie um e-mail para suporte@opentojob.com.br. Nossa equipe está disponível de segunda a sexta, das 9h às 18h, para ajudar com qualquer problema técnico.'
            ],
            [
                'pergunta' => 'Como reportar uma empresa ou mensagem inadequada?',
                'resposta' => 'Se receber mensagens inadequadas ou identificar comportamento impróprio de uma empresa, clique no botão "Reportar" disponível na mensagem ou no perfil da empresa. Nosso time de moderação analisará o caso e tomará as medidas necessárias.'
            ],
            [
                'pergunta' => 'Posso excluir minha conta?',
                'resposta' => 'Sim, você pode excluir sua conta a qualquer momento. Acesse "Configurações > Privacidade > Excluir Conta". Ao excluir sua conta, todos os seus dados pessoais serão removidos permanentemente da plataforma, conforme nossa política de privacidade.'
            ],
            [
                'pergunta' => 'Onde encontro mais dicas sobre carreira?',
                'resposta' => 'Acesse nosso blog na seção "Dicas de Carreira" para encontrar artigos, tutoriais e vídeos sobre desenvolvimento profissional, preparação para entrevistas, tendências do mercado e muito mais. Também oferecemos webinars gratuitos mensalmente sobre temas relevantes para quem está em busca de recolocação.'
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
                            <i class="fas fa-envelope me-2"></i> suporte@opentojob.com.br<br>
                            <i class="fas fa-phone me-2"></i> (11) 3456-7890<br>
                            <i class="fas fa-clock me-2"></i> Segunda a Sexta, 9h às 18h
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Perguntas Frequentes - Talentos</h1>
            </div>
            
            <div class="alert alert-info">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-info-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5>Bem-vindo ao centro de ajuda para talentos</h5>
                        <p class="mb-0">Aqui você encontra respostas para as dúvidas mais comuns sobre como utilizar o OpenToJob para se conectar com empresas e encontrar novas oportunidades. Navegue pelas categorias ou utilize a busca para encontrar informações específicas.</p>
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
            
            <!-- CTA para talentos não logados -->
            <?php if (!$is_talento): ?>
            <div class="card mt-5">
                <div class="card-body text-center py-4">
                    <h3>Pronto para encontrar novas oportunidades?</h3>
                    <p class="mb-4">Cadastre-se gratuitamente e conecte-se com empresas que estão buscando profissionais como você.</p>
                    <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-primary btn-lg">Cadastrar como Talento</a>
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
