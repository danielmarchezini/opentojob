<?php
// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter tipo de usuário
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se o formulário foi enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
    // Verificar senha
    $senha = $_POST['senha'];
    
    // Buscar usuário no banco
    $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $user_id]);
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        try {
            // Iniciar transação
            $db->beginTransaction();
            
            // Registrar solicitação de exclusão
            $db->query("INSERT INTO solicitacoes_exclusao (usuario_id, tipo_usuario, data_solicitacao, status) 
                       VALUES (:usuario_id, :tipo_usuario, NOW(), 'pendente')", [
                'usuario_id' => $user_id,
                'tipo_usuario' => $user_type
            ]);
            
            // Se for talento, anonimizar dados
            if ($user_type === 'talento') {
                // Anonimizar dados do talento
                $db->query("UPDATE talentos SET 
                           profissao = 'Dados removidos', 
                           experiencia = 'Dados removidos',
                           formacao = 'Dados removidos',
                           resumo = 'Dados removidos',
                           habilidades = '',
                           areas_interesse = '',
                           carta_apresentacao = 'Dados removidos',
                           curriculo = NULL,
                           github = NULL,
                           portfolio = NULL,
                           telefone = NULL,
                           linkedin = NULL,
                           website = NULL,
                           nivel = NULL
                           WHERE usuario_id = :usuario_id", [
                    'usuario_id' => $user_id
                ]);
                
                // Remover candidaturas
                $db->query("DELETE FROM candidaturas WHERE talento_id = :talento_id", [
                    'talento_id' => $user_id
                ]);
                
                // Remover dos favoritos
                $db->query("DELETE FROM talentos_favoritos WHERE talento_id = :talento_id", [
                    'talento_id' => $user_id
                ]);
            }
            
            // Se for empresa, anonimizar dados
            if ($user_type === 'empresa') {
                // Anonimizar dados da empresa
                $db->query("UPDATE empresas SET 
                           nome_empresa = 'Dados removidos', 
                           razao_social = 'Dados removidos',
                           cnpj = 'Dados removidos',
                           descricao = 'Dados removidos',
                           segmento = 'Dados removidos',
                           site = NULL,
                           telefone = NULL,
                           linkedin = NULL
                           WHERE usuario_id = :usuario_id", [
                    'usuario_id' => $user_id
                ]);
                
                // Marcar vagas como fechadas
                $db->query("UPDATE vagas SET status = 'fechada' WHERE empresa_id = :empresa_id", [
                    'empresa_id' => $user_id
                ]);
                
                // Marcar demandas como inativas
                $db->query("UPDATE demandas_talentos SET status = 'inativa' WHERE empresa_id = :empresa_id", [
                    'empresa_id' => $user_id
                ]);
                
                // Remover favoritos
                $db->query("DELETE FROM talentos_favoritos WHERE empresa_id = :empresa_id", [
                    'empresa_id' => $user_id
                ]);
            }
            
            // Anonimizar dados do usuário
            $email_anonimo = 'usuario_removido_' . $user_id . '@anonimo.com';
            $db->query("UPDATE usuarios SET 
                       nome = 'Usuário Removido', 
                       email = :email,
                       foto_perfil = NULL,
                       cidade = NULL,
                       estado = NULL,
                       status = 'inativo'
                       WHERE id = :id", [
                'id' => $user_id,
                'email' => $email_anonimo
            ]);
            
            // Remover mensagens
            $db->query("UPDATE mensagens SET 
                       assunto = 'Mensagem removida',
                       conteudo = 'Conteúdo removido conforme solicitação LGPD'
                       WHERE remetente_id = :user_id OR destinatario_id = :user_id", [
                'user_id' => $user_id
            ]);
            
            // Confirmar transação
            $db->commit();
            
            // Encerrar sessão
            session_destroy();
            
            // Redirecionar para página de confirmação
            header('Location: ' . SITE_URL . '/?route=exclusao_confirmada');
            exit;
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $db->rollBack();
            $mensagem = "Erro ao processar exclusão de dados: " . $e->getMessage();
            $tipo_mensagem = "danger";
        }
    } else {
        $mensagem = "Senha incorreta. Por favor, tente novamente.";
        $tipo_mensagem = "danger";
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Exclusão de Dados Pessoais (LGPD)</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Atenção!</h5>
                        <p>Você está prestes a solicitar a exclusão de seus dados pessoais do OpenToJob.</p>
                        <p><strong>Esta ação não pode ser desfeita e resultará em:</strong></p>
                        <ul>
                            <?php if ($user_type === 'talento'): ?>
                            <li>Anonimização de todos os seus dados pessoais e profissionais</li>
                            <li>Remoção do seu currículo e links externos (GitHub, LinkedIn, etc.)</li>
                            <li>Remoção das suas candidaturas a vagas</li>
                            <li>Remoção do seu perfil das listas de favoritos de empresas</li>
                            <?php elseif ($user_type === 'empresa'): ?>
                            <li>Anonimização de todos os dados da empresa</li>
                            <li>Fechamento de todas as vagas publicadas</li>
                            <li>Desativação de todas as demandas "Procura-se"</li>
                            <li>Remoção de todos os talentos da sua lista de favoritos</li>
                            <?php endif; ?>
                            <li>Desativação permanente da sua conta</li>
                        </ul>
                        <p>Conforme a Lei Geral de Proteção de Dados (LGPD), você tem o direito de solicitar a exclusão dos seus dados pessoais. Após a confirmação, seus dados serão anonimizados e sua conta será desativada.</p>
                    </div>
                    
                    <form method="post" action="" id="form-exclusao">
                        <div class="form-group mb-3">
                            <label for="senha" class="form-label">Digite sua senha para confirmar a exclusão:</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="confirmar" required>
                            <label class="form-check-label" for="confirmar">
                                Eu confirmo que desejo excluir meus dados e entendo que esta ação não pode ser desfeita.
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/?route=<?php echo $user_type === 'talento' ? 'perfil_talento_editar' : 'perfil_empresa_editar'; ?>" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" name="confirmar_exclusao" class="btn btn-danger" id="btn-confirmar-exclusao" disabled>
                                Confirmar Exclusão de Dados
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Habilitar/desabilitar botão de confirmação
    const checkboxConfirmar = document.getElementById('confirmar');
    const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
    
    checkboxConfirmar.addEventListener('change', function() {
        btnConfirmar.disabled = !this.checked;
    });
    
    // Confirmação adicional antes de enviar
    document.getElementById('form-exclusao').addEventListener('submit', function(e) {
        if (!confirm('ATENÇÃO: Esta ação é irreversível. Seus dados serão anonimizados e sua conta será desativada. Deseja realmente prosseguir?')) {
            e.preventDefault();
        }
    });
});
</script>
