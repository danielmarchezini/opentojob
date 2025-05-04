<?php
// Página de escolha de tipo de cadastro
?>

<div class="escolha-cadastro-container">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-5">
                    <h1 class="display-4 mb-3">Cadastre-se no OpenToJob</h1>
                    <p class="lead">Escolha como você deseja se cadastrar na plataforma</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 option-card option-talento">
                            <div class="card-body text-center">
                                <div class="option-icon mb-4">
                                    <i class="fas fa-user-tie fa-4x"></i>
                                </div>
                                <h2 class="card-title">Sou um Talento</h2>
                                <p class="card-text mb-4">Cadastre-se como profissional para encontrar oportunidades de trabalho que combinam com seu perfil.</p>
                                <ul class="option-benefits mb-4">
                                    <li><i class="fas fa-check-circle"></i> Crie um perfil profissional completo</li>
                                    <li><i class="fas fa-check-circle"></i> Candidate-se a vagas de emprego</li>
                                    <li><i class="fas fa-check-circle"></i> Seja encontrado por empresas</li>
                                    <li><i class="fas fa-check-circle"></i> Demonstre interesse em anúncios "Procura-se"</li>
                                </ul>
                                <a href="<?php echo SITE_URL; ?>/?route=cadastro_talento" class="btn btn-primary btn-lg btn-block">Cadastrar como Talento</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 option-card option-empresa">
                            <div class="card-body text-center">
                                <div class="option-icon mb-4">
                                    <i class="fas fa-building fa-4x"></i>
                                </div>
                                <h2 class="card-title">Sou uma Empresa</h2>
                                <p class="card-text mb-4">Cadastre-se como empresa para encontrar os melhores talentos disponíveis para contratação imediata.</p>
                                <ul class="option-benefits mb-4">
                                    <li><i class="fas fa-check-circle"></i> Crie um perfil empresarial</li>
                                    <li><i class="fas fa-check-circle"></i> Publique vagas de emprego</li>
                                    <li><i class="fas fa-check-circle"></i> Busque talentos qualificados</li>
                                    <li><i class="fas fa-check-circle"></i> Publique anúncios "Procura-se"</li>
                                </ul>
                                <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-primary btn-lg btn-block">Cadastrar como Empresa</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>Já possui uma conta? <a href="<?php echo SITE_URL; ?>/?route=entrar">Faça login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.escolha-cadastro-container {
    background-color: #f8f9fa;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.option-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    overflow: hidden;
}

.option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.option-talento {
    border-top: 5px solid var(--primary-color);
}

.option-empresa {
    border-top: 5px solid var(--accent-color);
}

.option-icon {
    color: var(--primary-color);
}

.option-empresa .option-icon {
    color: var(--accent-color);
}

.option-benefits {
    list-style: none;
    padding-left: 0;
    text-align: left;
}

.option-benefits li {
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
}

.option-benefits i {
    color: var(--success-color);
    margin-right: 10px;
    margin-top: 4px;
}

.option-empresa .option-benefits i {
    color: var(--accent-color);
}

@media (max-width: 767px) {
    .escolha-cadastro-container {
        min-height: auto;
        padding: 40px 0;
    }
}
</style>
