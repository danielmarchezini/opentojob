<?php
// Página explicativa sobre a função "Procura-se"
?>

<div class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>Função "Procura-se" - OpenToJob</h1>
                <p class="lead">Encontre exatamente o profissional que sua empresa precisa contratar imediatamente</p>
            </div>
            <div class="col-lg-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Sobre Procura-se</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title">O que é a função "Procura-se"?</h2>
                    <p class="card-text">A função "Procura-se" é uma inovação do OpenToJob que permite às empresas publicarem anúncios específicos para encontrar profissionais com perfis exatos que precisam contratar imediatamente.</p>
                    
                    <p>Diferente das vagas tradicionais, os anúncios "Procura-se" são mais diretos e focados em perfis específicos, aumentando a eficiência do processo de recrutamento.</p>
                    
                    <div class="alert alert-primary">
                        <i class="fas fa-info-circle me-2"></i> <strong>Importante:</strong> A função "Procura-se" foi desenvolvida para ajudar empresas a encontrarem talentos disponíveis para contratação imediata, alinhando-se com o propósito do OpenToJob de "conectar talentos prontos a oportunidades imediatas".
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0">Como funciona?</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-2 text-center">
                            <div class="feature-icon">
                                <i class="fas fa-edit fa-2x text-primary"></i>
                            </div>
                            <div class="step-number">1</div>
                        </div>
                        <div class="col-md-10">
                            <h4>Publique seu anúncio</h4>
                            <p>Especifique exatamente o perfil profissional que você está procurando, incluindo habilidades, experiência e qualificações necessárias.</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-2 text-center">
                            <div class="feature-icon">
                                <i class="fas fa-search fa-2x text-primary"></i>
                            </div>
                            <div class="step-number">2</div>
                        </div>
                        <div class="col-md-10">
                            <h4>Talentos demonstram interesse</h4>
                            <p>Profissionais qualificados que atendem ao perfil solicitado podem demonstrar interesse no seu anúncio, enviando seus currículos e informações de contato.</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-2 text-center">
                            <div class="feature-icon">
                                <i class="fas fa-comments fa-2x text-primary"></i>
                            </div>
                            <div class="step-number">3</div>
                        </div>
                        <div class="col-md-10">
                            <h4>Comunique-se diretamente</h4>
                            <p>Entre em contato diretamente com os talentos interessados, agende entrevistas e avalie se são adequados para sua necessidade.</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <div class="feature-icon">
                                <i class="fas fa-handshake fa-2x text-primary"></i>
                            </div>
                            <div class="step-number">4</div>
                        </div>
                        <div class="col-md-10">
                            <h4>Contrate rapidamente</h4>
                            <p>Finalize o processo de contratação de forma rápida e eficiente, economizando tempo e recursos.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h3 class="mb-0">Benefícios da função "Procura-se"</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="benefits-list">
                                <li><i class="fas fa-check-circle text-success me-2"></i> Encontre talentos específicos rapidamente</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> Receba candidaturas apenas de profissionais qualificados</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> Reduza o tempo de busca por profissionais</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="benefits-list">
                                <li><i class="fas fa-check-circle text-success me-2"></i> Economize recursos no processo de recrutamento</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> Comunique-se diretamente com os candidatos</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i> Aumente a eficiência das suas contratações</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4 cta-card">
                <div class="card-body text-center">
                    <h3 class="card-title">Comece agora mesmo!</h3>
                    <p class="card-text">Publique seu primeiro anúncio "Procura-se" e encontre o profissional ideal para sua empresa.</p>
                    
                    <?php if (Auth::isLoggedIn() && Auth::checkUserType('empresa')): ?>
                        <a href="<?php echo SITE_URL; ?>/?route=criar_demanda" class="btn btn-primary btn-lg btn-block mb-3">Publicar anúncio</a>
                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_demandas" class="btn btn-outline-primary btn-block">Gerenciar meus anúncios</a>
                    <?php elseif (Auth::isLoggedIn() && Auth::checkUserType('talento')): ?>
                        <div class="alert alert-info">
                            <p>Você está logado como talento. Para publicar anúncios "Procura-se", é necessário ter uma conta de empresa.</p>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/?route=demandas" class="btn btn-primary btn-lg btn-block">Ver anúncios disponíveis</a>
                    <?php else: ?>
                        <div class="account-options">
                            <p class="mb-3">Para utilizar esta função, você precisa ter uma conta de empresa:</p>
                            <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-primary btn-lg btn-block mb-3">Cadastrar empresa</a>
                            <a href="<?php echo SITE_URL; ?>/?route=entrar" class="btn btn-outline-primary btn-block">Já tenho uma conta</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Perguntas frequentes</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Qual a diferença entre "Procura-se" e vagas tradicionais?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Os anúncios "Procura-se" são mais específicos e focados em encontrar profissionais com perfis exatos para contratação imediata, enquanto vagas tradicionais podem ser mais abrangentes e para preenchimento a médio prazo.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Quanto custa publicar um anúncio "Procura-se"?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Atualmente, a publicação de anúncios "Procura-se" é gratuita para empresas cadastradas no OpenToJob. Aproveite esta oportunidade!
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Por quanto tempo meu anúncio ficará disponível?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Os anúncios "Procura-se" ficam disponíveis por 30 dias, mas você pode desativá-los a qualquer momento se já encontrou o profissional que procurava.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.feature-icon {
    margin-bottom: 15px;
    color: var(--primary-color);
}

.step-number {
    display: inline-block;
    width: 30px;
    height: 30px;
    line-height: 30px;
    text-align: center;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    font-weight: bold;
}

.benefits-list {
    list-style: none;
    padding-left: 0;
}

.benefits-list li {
    margin-bottom: 12px;
    display: flex;
    align-items: flex-start;
}

.cta-card {
    background-color: #f8f9fa;
    border-left: 4px solid var(--accent-color);
}

.accordion-button:not(.collapsed) {
    background-color: rgba(0, 52, 120, 0.1);
    color: var(--primary-color);
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0, 52, 120, 0.25);
}
</style>
