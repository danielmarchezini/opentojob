<?php
// Esta página é exibida após a confirmação da exclusão de dados
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Exclusão de Dados Confirmada</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h5 class="mb-3">Sua solicitação de exclusão de dados foi processada com sucesso!</h5>
                    
                    <p>Conforme solicitado, seus dados pessoais foram anonimizados e sua conta foi desativada de acordo com a Lei Geral de Proteção de Dados (LGPD).</p>
                    
                    <p>Agradecemos por ter utilizado o OpenToJob. Se você mudar de ideia no futuro, será necessário criar uma nova conta.</p>
                    
                    <div class="mt-4">
                        <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">Voltar para a Página Inicial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
