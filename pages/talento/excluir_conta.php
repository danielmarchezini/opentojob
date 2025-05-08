<?php
// Verificar se o usuário está logado e é um talento
if (!Auth::checkUserType('talento')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Inicializar variáveis para controlar o fluxo
$erro = false;
$mensagem_erro = '';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se a senha foi fornecida e está correta
    if (isset($_POST['senha']) && !empty($_POST['senha'])) {
        $senha = $_POST['senha'];
        
        // Verificar se a senha está correta
        $usuario = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario_id]);
        
        if ($usuario && md5($senha) === $usuario['senha']) {
            // Senha correta, processar a exclusão
            $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
            $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
            
            try {
                // Iniciar transação
                $db->beginTransaction();
                
                // Registrar feedback de exclusão
                $db->execute("
                    INSERT INTO feedback_exclusao (usuario_id, tipo_usuario, motivo, feedback, data_exclusao)
                    VALUES (:usuario_id, 'talento', :motivo, :feedback, NOW())
                ", [
                    'usuario_id' => $usuario_id,
                    'motivo' => $motivo,
                    'feedback' => $feedback
                ]);
                
                // Marcar usuário como excluído (soft delete)
                $db->execute("
                    UPDATE usuarios 
                    SET status = 'excluido', data_exclusao = NOW() 
                    WHERE id = :id
                ", ['id' => $usuario_id]);
                
                // Anonimizar dados pessoais
                $email_anonimo = 'deleted_' . $usuario_id . '@' . uniqid() . '.com';
                $db->execute("
                    UPDATE usuarios 
                    SET email = :email, nome = 'Usuário Excluído'
                    WHERE id = :id
                ", [
                    'email' => $email_anonimo,
                    'id' => $usuario_id
                ]);
                
                // Anonimizar dados do talento
                $db->execute("
                    UPDATE talentos 
                    SET 
                        telefone = NULL,
                        linkedin = NULL,
                        github = NULL,
                        portfolio = NULL,
                        website = NULL,
                        resumo = 'Este perfil foi excluído pelo usuário.',
                        experiencia_profissional = NULL,
                        formacao = NULL
                    WHERE usuario_id = :usuario_id
                ", ['usuario_id' => $usuario_id]);
                
                // Confirmar transação
                $db->commit();
                
                // Destruir sessão
                session_destroy();
                
                // Redirecionar para a página inicial com mensagem de sucesso
                $_SESSION['flash_message'] = "Sua conta foi excluída com sucesso. Agradecemos por ter utilizado nossos serviços.";
                $_SESSION['flash_type'] = "success";
                header('Location: ' . SITE_URL);
                exit;
                
            } catch (PDOException $e) {
                // Reverter transação em caso de erro
                $db->rollBack();
                
                error_log("Erro ao excluir conta: " . $e->getMessage());
                $_SESSION['flash_message'] = "Erro ao excluir conta: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } else {
            $erro = true;
            $mensagem_erro = "Senha incorreta. Por favor, tente novamente.";
        }
    } else {
        $erro = true;
        $mensagem_erro = "Por favor, informe sua senha para confirmar a exclusão.";
    }
}

// Obter dados do talento
$talento = $db->fetch("
    SELECT u.nome, u.email, t.profissao
    FROM usuarios u
    LEFT JOIN talentos t ON u.id = t.usuario_id
    WHERE u.id = :id
", ['id' => $usuario_id]);

// Lista de motivos para exclusão
$motivos = [
    'privacidade' => 'Preocupações com privacidade',
    'nao_util' => 'Não achei o serviço útil',
    'encontrei_trabalho' => 'Encontrei trabalho/oportunidade',
    'experiencia_ruim' => 'Tive uma experiência ruim',
    'outro' => 'Outro motivo'
];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $mensagem_erro; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Excluir Conta</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Atenção!</h5>
                        <p>Você está prestes a excluir sua conta do OpenToJob. Esta ação <strong>não pode ser desfeita</strong>.</p>
                        <p>Ao excluir sua conta:</p>
                        <ul>
                            <li>Seus dados pessoais serão anonimizados</li>
                            <li>Seu perfil não estará mais visível para empresas</li>
                            <li>Suas candidaturas e interações serão mantidas apenas para fins estatísticos, sem identificação pessoal</li>
                            <li>Você perderá acesso a todas as funcionalidades da plataforma</li>
                        </ul>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Dados da Conta</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($talento['nome']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($talento['email']); ?></p>
                            <p><strong>Profissão:</strong> <?php echo htmlspecialchars($talento['profissao'] ?? 'Não informada'); ?></p>
                        </div>
                    </div>
                    
                    <form method="post" id="formExcluirConta">
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Por que você está excluindo sua conta? <span class="text-danger">*</span></label>
                            <select class="form-select" id="motivo" name="motivo" required>
                                <option value="">Selecione um motivo</option>
                                <?php foreach ($motivos as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>"><?php echo $texto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Conte-nos mais sobre sua decisão (opcional)</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Seu feedback é importante para melhorarmos nossos serviços"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Digite sua senha para confirmar a exclusão <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="confirmar" required>
                            <label class="form-check-label" for="confirmar">
                                Confirmo que desejo excluir permanentemente minha conta e entendo que esta ação não pode ser desfeita.
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/?route=painel_talento" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger" id="btnExcluir" disabled>
                                <i class="fas fa-user-times me-2"></i>Excluir Minha Conta
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
    // Habilitar/desabilitar botão de exclusão com base na confirmação
    const checkboxConfirmar = document.getElementById('confirmar');
    const btnExcluir = document.getElementById('btnExcluir');
    
    // Verificar estado inicial do checkbox
    btnExcluir.disabled = !checkboxConfirmar.checked;
    
    checkboxConfirmar.addEventListener('change', function() {
        btnExcluir.disabled = !this.checked;
        console.log('Checkbox alterado. Botão desabilitado: ' + btnExcluir.disabled);
    });
    
    // Confirmar exclusão antes de enviar o formulário
    document.getElementById('formExcluirConta').addEventListener('submit', function(e) {
        if (!confirm('ATENÇÃO: Você está prestes a excluir permanentemente sua conta. Esta ação não pode ser desfeita. Deseja continuar?')) {
            e.preventDefault();
        }
    });
});
</script>
