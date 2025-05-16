<div class="about-header">
    <div class="container">
        <h1 class="about-title">Sobre o OpenToJob</h1>
        <p class="about-subtitle">Conectando talentos prontos a oportunidades imediatas</p>
    </div>
</div>

<div class="container about-container">
    <?php
    // Verificar se a tabela equipe existe
    $db = Database::getInstance();
    $table_exists = $db->fetchColumn("SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = 'open2w' 
        AND table_name = 'equipe'");
        
    if ($table_exists) {
        // Buscar membros da equipe ativos
        $membros_equipe = $db->fetchAll("SELECT * FROM equipe WHERE ativo = 1 ORDER BY ordem ASC, nome ASC");
        
        if (!empty($membros_equipe)) {
    ?>
    <div class="about-team mb-5">
        <h2 class="section-title text-center">Nossa Equipe</h2>
        <div class="team-grid">
            <?php foreach ($membros_equipe as $membro): ?>
            <div class="team-member">
                <div class="member-photo">
                    <?php 
                    $tem_foto = false;
                    if (!empty($membro['foto'])) {
                        $caminho_foto = $_SERVER['DOCUMENT_ROOT'] . '/open2w/' . $membro['foto'];
                        if (file_exists($caminho_foto)) {
                            $tem_foto = true;
                        }
                    }
                    
                    if ($tem_foto): 
                    ?>
                        <img src="<?php echo SITE_URL . '/open2w/' . $membro['foto']; ?>" alt="Foto de <?php echo htmlspecialchars((string)$membro['nome']); ?>">
                    <?php else: 
                        // Gerar iniciais do nome
                        $nome_partes = explode(' ', $membro['nome']);
                        $iniciais = '';
                        
                        if (count($nome_partes) >= 2) {
                            // Pegar a primeira letra do primeiro e último nome
                            $iniciais = strtoupper(substr($nome_partes[0], 0, 1) . substr(end($nome_partes), 0, 1));
                        } else {
                            // Se tiver apenas um nome, pegar as duas primeiras letras
                            $iniciais = strtoupper(substr($membro['nome'], 0, 2));
                            if (strlen($iniciais) < 2) {
                                $iniciais = strtoupper(substr($membro['nome'], 0, 1)) . 'T';
                            }
                        }
                        
                        // Gerar cor baseada no nome para o background
                        $hash = md5($membro['nome']);
                        $cor_bg = '#' . substr($hash, 0, 6);
                        
                        // Garantir contraste adequado para o texto
                        $r = hexdec(substr($cor_bg, 1, 2));
                        $g = hexdec(substr($cor_bg, 3, 2));
                        $b = hexdec(substr($cor_bg, 5, 2));
                        $luminosidade = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                        $cor_texto = ($luminosidade > 128) ? '#000000' : '#FFFFFF';
                    ?>
                        <div class="member-photo-placeholder d-flex align-items-center justify-content-center" style="background-color: <?php echo $cor_bg; ?>; color: <?php echo $cor_texto; ?>; height: 100%; width: 100%;">
                            <span style="font-size: 3rem; font-weight: bold;"><?php echo $iniciais; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <h3 class="member-name"><?php echo htmlspecialchars((string)$membro['nome']); ?></h3>
                <p class="member-position"><?php echo htmlspecialchars((string)$membro['profissao']); ?></p>
                
                <?php if (!empty($membro['subtitulo'])): ?>
                <p class="member-subtitle"><?php echo htmlspecialchars((string)$membro['subtitulo']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($membro['comentarios'])): ?>
                <div class="member-bio">
                    <?php echo nl2br(htmlspecialchars((string)$membro['comentarios'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($membro['linkedin'])): ?>
                <div class="member-social">
                    <a href="<?php echo htmlspecialchars((string)$membro['linkedin']); ?>" class="social-link" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php 
        }
    }
    ?>
    
    <div class="about-section">
        <div class="about-content">
            <h2 class="section-title">Nossa Missão</h2>
            <div class="multi-column-text">
            <p>O OpenToJob nasceu com a missão de revolucionar a forma como profissionais e empresas se conectam no mercado de trabalho. Acreditamos que o processo de recrutamento pode ser mais eficiente, transparente e focado nas reais necessidades de ambas as partes. Em um cenário onde o tempo é um recurso valioso, nossa plataforma elimina a frustração de processos seletivos longos e improdutivos, oferecendo uma solução direta e objetiva.</p>
            <p>Nossa plataforma foi desenvolvida para permitir que profissionais sinalizem sua disponibilidade imediata para novas oportunidades e que empresas encontrem talentos qualificados que estão prontos para iniciar imediatamente. Diferentemente de outras plataformas de recrutamento, o OpenToJob foca exclusivamente em conectar pessoas que estão realmente disponíveis para trabalhar agora com empresas que precisam preencher posições rapidamente, criando um ecossistema de oportunidades genuínas e imediatas.</p>
            <p>Entendemos que o mercado de trabalho atual exige agilidade e precisão. Por isso, desenvolvemos ferramentas e recursos que facilitam a identificação de compatibilidades entre talentos e oportunidades, reduzindo o tempo de contratação e aumentando as chances de sucesso para ambas as partes. Nossa missão vai além de simplesmente conectar pessoas a vagas - buscamos transformar positivamente a experiência de busca por emprego e recrutamento.</p>
            </div>
        </div>
        <!-- Espaço removido para layout mais limpo -->
    </div>
    
    <div class="about-section reverse">
        <div class="about-content">
            <h2 class="section-title">Nosso Compromisso</h2>
            <div class="multi-column-text">
            <p>O OpenToJob tem um compromisso fundamental: conectar empresas apenas com talentos que estão prontos para trabalhar imediatamente. Diferente de outras plataformas, não trabalhamos com profissionais que estão apenas "abertos a oportunidades" enquanto empregados. Nosso foco está em criar conexões genuínas e produtivas, eliminando o desperdício de tempo com candidatos que não estão realmente disponíveis ou interessados em mudar de emprego no curto prazo.</p>
            <p>Nosso foco são pessoas que precisam de uma oportunidade agora e estão disponíveis para iniciar imediatamente. Esse posicionamento claro beneficia tanto os talentos, que recebem oportunidades reais, quanto as empresas, que encontram profissionais realmente disponíveis. Entendemos que a busca por emprego pode ser um período desafiador, e nosso compromisso é tornar esse processo mais eficiente, respeitoso e produtivo para todos os envolvidos.</p>
            <p>Acreditamos que a hashtag #opentojob representa melhor nosso propósito: profissionais que estão abertos e prontos para uma nova posição, não apenas "abertos ao trabalho" de forma abstrata. Esta distinção é crucial para nossa proposta de valor e reflete nosso compromisso com a transparência e eficiência. Quando uma empresa encontra um talento no OpenToJob, ela tem a certeza de que está diante de alguém genuinamente interessado e disponível para iniciar um novo desafio profissional.</p>
            <p>Além disso, nos comprometemos com a qualidade das conexões estabelecidas em nossa plataforma. Trabalhamos constantemente para aprimorar nossos algoritmos de correspondência e ferramentas de comunicação, garantindo que as interações entre empresas e talentos sejam relevantes, respeitosas e produtivas. Nosso compromisso com a excelência se estende a todos os aspectos da experiência do usuário, desde o cadastro até a concretização de uma nova oportunidade profissional.</p>
            </div>
        </div>
        <!-- Espaço removido para layout mais limpo -->
    </div>
    
    <div class="about-values">
        <h2 class="section-title text-center">Nossos Valores</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 class="value-title">Transparência</h3>
                <p class="value-description">Promovemos a comunicação clara e honesta entre empresas e talentos, criando um ambiente de confiança mútua.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="value-title">Inclusão</h3>
                <p class="value-description">Valorizamos a diversidade e trabalhamos para criar oportunidades iguais para todos os profissionais, independentemente de sua origem ou trajetória.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3 class="value-title">Inovação</h3>
                <p class="value-description">Buscamos constantemente novas soluções e tecnologias para melhorar a experiência de recrutamento e seleção.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="value-title">Privacidade</h3>
                <p class="value-description">Protegemos os dados dos nossos usuários e garantimos que eles tenham controle sobre suas informações pessoais.</p>
            </div>
        </div>
    </div>
    
    <div class="about-section">
        <div class="about-content">
            <h2 class="section-title">Como Funciona</h2>
            <h3>Para Talentos</h3>
            <div class="multi-column-text">
            <ol class="flow-list">
                <li><strong>Cadastro Completo:</strong> Crie seu perfil detalhando sua experiência, habilidades e nível profissional. Um perfil completo e bem estruturado aumenta significativamente suas chances de ser encontrado por empresas que buscam profissionais com seu conjunto de competências. Dedique tempo para destacar suas realizações, projetos relevantes e certificações que demonstrem sua expertise.</li>
                <li><strong>Visibilidade Controlada:</strong> Escolha se seus dados serão públicos ou visíveis apenas para empresas cadastradas. Esta funcionalidade permite que você gerencie sua privacidade de acordo com sua situação atual, protegendo informações sensíveis enquanto mantém sua disponibilidade para oportunidades relevantes. Você pode ajustar estas configurações a qualquer momento, conforme suas necessidades mudam.</li>
                <li><strong>Destaque seu Nível:</strong> Informe seu nível profissional (Estágio, Júnior, Pleno, Sênior) para facilitar a busca das empresas. Esta classificação ajuda as empresas a filtrar candidatos de acordo com o nível de experiência desejado, aumentando a relevância das oportunidades que chegam até você. Seja honesto na avaliação do seu nível para garantir expectativas alinhadas com potenciais empregadores.</li>
                <li><strong>Aguarde Contato:</strong> As empresas visualizarão seu perfil e iniciarão contato se houver interesse. Nossa plataforma notificará você imediatamente quando uma empresa demonstrar interesse, permitindo que você responda rapidamente e não perca oportunidades valiosas. Enquanto isso, continue aprimorando seu perfil e mantendo suas informações atualizadas.</li>
                <li><strong>Responda Oportunidades:</strong> Você poderá responder diretamente às mensagens das empresas interessadas. Nossa interface de comunicação foi projetada para facilitar interações profissionais e eficientes, permitindo que você esclareça dúvidas, forneça informações adicionais e agende entrevistas diretamente pela plataforma, sem a necessidade de intermediários.</li>
            </ol>
            </div>
            
            <h3>Para Empresas</h3>
            <div class="multi-column-text">
            <ol class="flow-list">
                <li><strong>Cadastro Empresarial:</strong> Crie o perfil da sua empresa com informações detalhadas sobre o negócio. Um perfil completo transmite credibilidade aos talentos e aumenta o interesse em suas oportunidades. Inclua informações sobre a cultura organizacional, benefícios oferecidos e diferenciais que tornam sua empresa um ótimo lugar para trabalhar. Este é seu cartão de visitas digital para atrair os melhores profissionais.</li>
                <li><strong>Busca Avançada:</strong> Utilize filtros por nível profissional, habilidades e localização para encontrar talentos. Nossa ferramenta de busca avançada permite que você refine seus resultados com precisão, economizando tempo e garantindo que você encontre exatamente o perfil que sua empresa necessita. Os algoritmos inteligentes da plataforma também sugerem perfis compatíveis com base nos seus critérios de busca e histórico de interações.</li>
                <li><strong>Publique "Procura-se":</strong> Crie anúncios específicos sobre os perfis que sua empresa está buscando. Esta funcionalidade exclusiva permite que você comunique claramente suas necessidades, mesmo quando não há uma vaga formal aberta. É uma forma eficiente de atrair profissionais qualificados que se identificam com o perfil desejado e estão prontos para novos desafios.</li>
                <li><strong>Inicie Contato:</strong> Envie mensagens diretamente aos talentos que se encaixam nas suas necessidades. Nossa plataforma facilita a comunicação direta e transparente, eliminando intermediários e acelerando o processo de recrutamento. Você pode personalizar suas mensagens para cada candidato, destacando aspectos específicos que tornaram o perfil interessante para sua empresa.</li>
                <li><strong>Gerencie Favoritos:</strong> Salve perfis interessantes para contato futuro. Esta funcionalidade permite que você mantenha um banco de talentos organizado, facilitando o acesso a profissionais qualificados quando surgem novas oportunidades. Você também pode adicionar notas e classificações aos perfis salvados, criando um sistema personalizado de gestão de talentos.</li>
            </ol>
            </div>
        </div>
        <!-- Espaço removido para layout mais limpo -->
    </div>
    
    <div class="about-section reverse">
        <div class="about-content">
            <h2 class="section-title">Recursos Exclusivos</h2>
            
            <div class="resources-grid">
                <div class="resource-card">
                    <h3>Função "Procura-se"</h3>
                    <p>Nossa função "Procura-se" permite que empresas publiquem exatamente o tipo de profissional que estão buscando, mesmo que não tenham uma vaga formal aberta. Isso cria um canal direto para talentos que se identificam com o perfil desejado. Esta abordagem inovadora vai além do modelo tradicional de anúncios de vagas, possibilitando às empresas mapearem o mercado de talentos disponíveis e criarem um banco de candidatos qualificados para necessidades futuras. Para os profissionais, representa a oportunidade de se conectar com empresas que valorizam seu conjunto específico de habilidades, mesmo antes da abertura formal de uma posição.</p>
                </div>
                
                <div class="resource-card">
                    <h3>Privacidade Controlada</h3>
                    <p>Talentos podem escolher o nível de visibilidade de seus dados, protegendo informações sensíveis enquanto ainda permanecem disponíveis para oportunidades. Entendemos que a busca por uma nova posição é um processo que exige discrição, especialmente para profissionais que ainda estão empregados. Nosso sistema de privacidade em camadas permite que você controle exatamente quais informações são visíveis e para quem, garantindo que você possa explorar novas oportunidades com segurança e tranquilidade. Além disso, oferecemos opções para ocultar seu perfil de empresas específicas, como seu empregador atual, eliminando preocupações com conflitos de interesse.</p>
                </div>
                
                <div class="resource-card">
                    <h3>Blog Informativo</h3>
                    <p>Nosso blog oferece conteúdo exclusivo para ajudar pessoas em busca de emprego, com dicas de entrevista, preparação de currículo e estratégias para se destacar no mercado. Produzido por especialistas em recrutamento, desenvolvimento de carreira e recursos humanos, o conteúdo é constantemente atualizado para refletir as tendências e demandas do mercado de trabalho contemporâneo. Além de artigos informativos, disponibilizamos webinars, podcasts e estudos de caso que ilustram histórias de sucesso e oferecem insights valiosos para profissionais em diferentes estágios de carreira. O blog também serve como um espaço para discussão e troca de experiências entre membros da comunidade OpenToJob.</p>
                </div>
                
                <div class="resource-card">
                    <h3>Comunicação Direta</h3>
                    <p>Priorizamos a comunicação direta entre empresas e talentos. Por isso, apenas empresas podem iniciar contato, garantindo que os talentos recebam apenas propostas relevantes e genuinamente interessadas. Este modelo elimina o spam e abordagens inadequadas, criando um ambiente de respeito mútuo e profissionalismo. Nossa plataforma de mensagens integrada facilita o acompanhamento de todas as interações, permitindo que ambas as partes mantenham um histórico organizado de comunicações. Além disso, oferecemos recursos como modelos de mensagens personalizáveis para empresas e ferramentas de agendamento integradas que simplificam a marcação de entrevistas e reuniões de follow-up, tornando todo o processo de recrutamento mais eficiente e produtivo.</p>
                </div>
                
                <div class="resource-card">
                    <h3>Avaliações e Feedback</h3>
                    <p>Nossa plataforma incorpora um sistema de avaliações mútuas que permite que empresas e talentos compartilhem feedback sobre suas interações. Este mecanismo promove a transparência e incentiva práticas profissionais de alta qualidade em ambos os lados. Para os talentos, representa a oportunidade de conhecer a reputação das empresas quanto a seus processos seletivos e cultura organizacional. Para as empresas, oferece insights valiosos sobre a seriedade e profissionalismo dos candidatos. Todas as avaliações são moderadas para garantir que sejam construtivas e baseadas em experiências reais, contribuindo para um ecossistema de recrutamento mais ético e eficaz.</p>
                </div>
            </div>
        </div>
        <!-- Espaço removido para layout mais limpo -->
    </div>
    
    <?php
    // Consultar os números reais do banco de dados
    $db = Database::getInstance();
    
    // Contar vagas internas ativas
    try {
        $vagas_internas = $db->fetchColumn("SELECT COUNT(*) FROM vagas WHERE status = 'ativa'") ?? 0;
    } catch (PDOException $e) {
        $vagas_internas = 0;
    }
    
    // Contar vagas externas ativas - verificar se a tabela existe
    try {
        // Verificar se a tabela existe
        $table_exists = $db->fetchColumn("SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = 'open2w' 
            AND table_name = 'vagas_externas'");
            
        if ($table_exists) {
            $vagas_externas = $db->fetchColumn("SELECT COUNT(*) FROM vagas_externas WHERE status = 'ativa'") ?? 0;
        } else {
            $vagas_externas = 0;
        }
    } catch (PDOException $e) {
        $vagas_externas = 0;
    }
    
    // Total de vagas ativas
    $total_vagas = $vagas_internas + $vagas_externas;
    
    // Contar empresas cadastradas
    try {
        $total_empresas = $db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE tipo = 'empresa' AND status = 'ativo'") ?? 0;
    } catch (PDOException $e) {
        $total_empresas = 0;
    }
    
    // Contar talentos cadastrados
    try {
        $total_talentos = $db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE tipo = 'talento' AND status = 'ativo'") ?? 0;
    } catch (PDOException $e) {
        $total_talentos = 0;
    }
    
    // Contar demandas (Procura-se) ativas
    try {
        $table_exists = $db->fetchColumn("SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = 'open2w' 
            AND table_name = 'demandas_talentos'");
            
        if ($table_exists) {
            $total_demandas = $db->fetchColumn("SELECT COUNT(*) FROM demandas_talentos WHERE status = 'ativa'") ?? 0;
        } else {
            $total_demandas = 0;
        }
    } catch (PDOException $e) {
        $total_demandas = 0;
    }
    ?>
    
    <div class="about-stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_vagas, 0, ',', '.'); ?></div>
            <div class="stat-label">Vagas ativas</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_empresas, 0, ',', '.'); ?></div>
            <div class="stat-label">Empresas cadastradas</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_talentos, 0, ',', '.'); ?></div>
            <div class="stat-label">Talentos cadastrados</div>
        </div>
        
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_demandas, 0, ',', '.'); ?></div>
            <div class="stat-label">Procura-se cadastrados</div>
        </div>
    </div>
    
    <div class="about-cta">
        <h2>Faça parte da revolução #opentojob</h2>
        <p>Junte-se a milhares de profissionais e empresas que já estão transformando o mercado de trabalho</p>
        <div class="cta-buttons">
            <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-accent">Cadastre-se como Talento</a>
            <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-outline">Cadastre sua Empresa</a>
        </div>
    </div>
</div>

<style>
.about-header {
    background-color: var(--primary-color);
    color: white;
    padding: 60px 0;
    text-align: center;
    margin-bottom: 60px;
}

.about-title {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.about-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
}

.about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.about-section {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin-bottom: 60px;
    gap: 40px;
}

.about-content {
    flex: 1;
    min-width: 300px;
    max-width: 100%;
}

/* Estilo para criar colunas nos parágrafos longos */
.multi-column-text {
    column-count: 2;
    column-gap: 40px;
    text-align: justify;
}

@media (max-width: 768px) {
    .multi-column-text {
        column-count: 1;
    }
}

.about-section.reverse {
    flex-direction: row-reverse;
}

.about-content h2 {
    font-size: 2rem;
    margin-bottom: 20px;
    color: var(--primary-color);
    column-span: all; /* Título ocupa todas as colunas */
}

.about-content h3 {
    column-span: all; /* Subtítulos ocupam todas as colunas */
    margin-top: 30px;
    margin-bottom: 15px;
}

.about-content p {
    margin-bottom: 20px;
    line-height: 1.8;
}

.about-image img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.about-values {
    margin-bottom: 60px;
}

.section-title {
    font-size: 2rem;
    margin-bottom: 30px;
    color: var(--primary-color);
}

.text-center {
    text-align: center;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.value-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 30px;
    text-align: center;
    transition: transform 0.3s ease;
}

.value-card:hover {
    transform: translateY(-10px);
}

.value-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 20px;
}

.value-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.value-description {
    color: var(--gray-color);
    line-height: 1.6;
}

/* Estilo para listas em múltiplas colunas */
.flow-list {
    padding-left: 20px;
    margin-bottom: 30px;
}

.flow-list li {
    margin-bottom: 15px;
    break-inside: avoid; /* Evita quebra de itens entre colunas */
}

/* Estilo para seções de recursos */
.resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.resource-card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 25px;
    transition: transform 0.3s ease;
}

.resource-card:hover {
    transform: translateY(-5px);
}

.resource-card h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.resource-card p {
    color: var(--gray-color);
    line-height: 1.6;
}

@media (max-width: 768px) {
    .about-section {
        flex-direction: column;
    }
    
    .about-section.reverse {
        flex-direction: column;
    }
    
    .resources-grid {
        grid-template-columns: 1fr;
    }
}

/* Estilos para a seção de estatísticas */
.about-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
    text-align: center;
}

.stat-item {
    background-color: var(--primary-color);
    color: white;
    border-radius: 10px;
    padding: 30px 20px;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* Estilos para a seção de CTA */
.about-cta {
    background-color: var(--light-gray-color);
    border-radius: 10px;
    padding: 50px;
    text-align: center;
    margin-bottom: 60px;
}

.about-cta h2 {
    font-size: 2rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.about-cta p {
    margin-bottom: 30px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    color: var(--gray-color);
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .about-section {
        flex-direction: column;
    }
    
    .about-section.reverse {
        flex-direction: column;
    }
    
    .resources-grid {
        grid-template-columns: 1fr;
    }
    
    .about-cta {
        padding: 30px 20px;
    }
    
    .about-cta h2 {
        font-size: 1.8rem;
    }
}

/* Estilos adicionais para a seção de equipe */
.about-team {
    margin-bottom: 60px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.team-member {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    text-align: center;
    transition: transform 0.3s ease;
}

.team-member:hover {
    transform: translateY(-10px);
}

.member-photo {
    height: 250px;
    overflow: hidden;
    position: relative;
}

.member-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.member-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
}

.member-name {
    font-size: 1.3rem;
    margin: 20px 0 5px;
    color: var(--dark-color);
}

.member-position {
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 10px;
}

.member-subtitle {
    font-style: italic;
    color: var(--gray-color);
    margin-bottom: 15px;
    padding: 0 15px;
}

.member-bio {
    padding: 0 20px;
    margin-bottom: 20px;
    color: var(--gray-color);
    font-size: 0.9rem;
    line-height: 1.6;
    text-align: left;
}

.member-social {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding-bottom: 20px;
}

.social-link {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--light-gray-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--dark-color);
    transition: all 0.3s ease;
}

.social-link:hover {
    background-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .team-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .member-photo {
        height: 200px;
    }
}
</style>
