<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-lock text-primary mb-4" style="font-size: 3rem;"></i>
                    <h2 class="mb-4">Acesso Exclusivo para Empresas</h2>
                    <p class="lead mb-4">Para visualizar o perfil completo deste talento, você precisa estar cadastrado como empresa na plataforma Open2W.</p>
                    <p class="mb-4">O acesso aos perfis de talentos é um recurso exclusivo para empresas que buscam profissionais qualificados.</p>
                    
                    <div class="row mt-5">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-building me-2"></i> Cadastrar como Empresa
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo SITE_URL; ?>/?route=talentos" class="btn btn-outline-secondary btn-lg btn-block">
                                <i class="fas fa-arrow-left me-2"></i> Voltar para Talentos
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted">Já possui cadastro? <a href="<?php echo SITE_URL; ?>/?route=entrar">Faça login</a></p>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Vantagens para Empresas</h5>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div>
                                    <h6>Busca avançada de talentos</h6>
                                    <p class="text-muted small">Encontre profissionais com as habilidades específicas que sua empresa precisa.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h6>Contato direto</h6>
                                    <p class="text-muted small">Comunique-se diretamente com os talentos que mais se destacam.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <h6>Acesso a currículos</h6>
                                    <p class="text-muted small">Visualize e baixe currículos completos dos candidatos.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex mb-3">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <h6>Estatísticas e relatórios</h6>
                                    <p class="text-muted small">Acompanhe métricas de desempenho das suas vagas e interações.</p>
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
.btn-block {
    display: block;
    width: 100%;
}
</style>
