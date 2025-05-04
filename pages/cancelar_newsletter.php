<?php
/**
 * Página de cancelamento de inscrição na newsletter
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */

// Verificar se o token foi fornecido
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $mensagem = [
        'tipo' => 'danger',
        'texto' => 'Token de cancelamento inválido ou não fornecido.'
    ];
} else {
    try {
        // Obter instância do banco de dados
        $db = Database::getInstance();
        
        // Verificar se o token existe
        $inscrito = $db->fetchOne("SELECT id, email, status FROM newsletter_inscritos WHERE token = ?", [$token]);
        
        if (!$inscrito) {
            $mensagem = [
                'tipo' => 'danger',
                'texto' => 'Token de cancelamento inválido ou expirado.'
            ];
        } else if ($inscrito['status'] === 'inativo') {
            $mensagem = [
                'tipo' => 'info',
                'texto' => 'Sua inscrição já foi cancelada anteriormente.'
            ];
        } else {
            // Atualizar status para inativo
            $db->execute("UPDATE newsletter_inscritos SET status = 'inativo' WHERE id = ?", [$inscrito['id']]);
            
            $mensagem = [
                'tipo' => 'success',
                'texto' => 'Sua inscrição foi cancelada com sucesso. Você não receberá mais nossos e-mails.'
            ];
        }
    } catch (Exception $e) {
        error_log('Erro ao cancelar inscrição na newsletter: ' . $e->getMessage());
        $mensagem = [
            'tipo' => 'danger',
            'texto' => 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.'
        ];
    }
}

// Definir título da página
$page_title = "Cancelar Inscrição na Newsletter";
?>

<!-- Conteúdo da página -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Cancelar Inscrição na Newsletter</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?php echo $mensagem['tipo']; ?>" role="alert">
                        <?php echo $mensagem['texto']; ?>
                    </div>
                    
                    <p class="mt-4">
                        <?php if ($mensagem['tipo'] === 'success' || $mensagem['tipo'] === 'info'): ?>
                            Caso mude de ideia, você pode se inscrever novamente a qualquer momento através do formulário no rodapé do nosso site.
                        <?php else: ?>
                            Se você está tendo problemas para cancelar sua inscrição, entre em contato conosco através da página de <a href="<?php echo SITE_URL; ?>/?route=contato">contato</a>.
                        <?php endif; ?>
                    </p>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Voltar para a página inicial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
