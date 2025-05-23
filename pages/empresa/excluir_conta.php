<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa')) {
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
                    VALUES (:usuario_id, 'empresa', :motivo, :feedback, NOW())
                ", [
                    'usuario_id' => $usuario_id,
                    'motivo' => $motivo,
                    'feedback' => $feedback
                ]);
                
                // Marcar usuário como inativo (soft delete)
                $db->execute("
                    UPDATE usuarios 
                    SET status = 'inativo', data_exclusao = NOW() 
                    WHERE id = :id
                ", ['id' => $usuario_id]);
                
                // Anonimizar dados pessoais
                $email_anonimo = 'deleted_' . $usuario_id . '@' . uniqid() . '.com';
                $db->execute("
                    UPDATE usuarios 
                    SET email = :email, nome = 'Empresa Excluída'
                    WHERE id = :id
                ", [
                    'email' => $email_anonimo,
                    'id' => $usuario_id
                ]);
                
                // Anonimizar dados da empresa
                $db->execute("
                    UPDATE empresas 
                    SET 
                        razao_social = 'Empresa Excluída',
                        cnpj = NULL,
                        telefone = NULL,
                        website = NULL,
                        linkedin = NULL,
                        descricao = 'Esta empresa excluiu seu perfil.'
                    WHERE usuario_id = :usuario_id
                ", ['usuario_id' => $usuario_id]);
                
                // Marcar vagas como inativas
                $db->execute("
                    UPDATE vagas 
                    SET status = 'inativa', atualizado_em = NOW()
                    WHERE empresa_id = :empresa_id
                ", ['empresa_id' => $usuario_id]);
                
                // Confirmar transação
                $db->commit();
                
                // Armazenar mensagem em uma variável para exibir antes do redirecionamento
                $mensagem_sucesso = "Sua conta foi excluída com sucesso. Agradecemos por ter utilizado nossos serviços.";
                
                // Apenas limpar os dados da sessão sem tentar modificar cookies
                // Isso evita o erro "headers already sent"
                $_SESSION = array();
                session_destroy();
                
                // Definir uma variável para indicar que a sessão foi destruída
                // O JavaScript irá lidar com a remoção do cookie no cliente
                
                // Definir flag para redirecionamento via JavaScript
                $conta_excluida = true;
                
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

// Obter dados da empresa
$empresa = $db->fetch("
    SELECT u.nome, u.email, e.razao_social, e.segmento
    FROM usuarios u
    LEFT JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id
", ['id' => $usuario_id]);

// Lista de motivos para exclusão
$motivos = [
    'privacidade' => 'Preocupações com privacidade',
    'nao_util' => 'Não achei o serviço útil',
    'encontrei_candidatos' => 'Já encontrei os candidatos que precisava',
    'experiencia_ruim' => 'Tive uma experiência ruim',
    'custos' => 'Custos ou questões financeiras',
    'outro' => 'Outro motivo'
];
?>

<?php if (isset($conta_excluida) && $conta_excluida): ?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Conta excluída com sucesso!</h4>
                <p><?php echo $mensagem_sucesso; ?></p>
                <p>Você será redirecionado para a página inicial em alguns segundos...</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para remover todos os cookies
    function deleteAllCookies() {
        const cookies = document.cookie.split(";");
        
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
        }
    }
    
    // Remover todos os cookies e redirecionar para a página inicial após 3 segundos
    deleteAllCookies();
    setTimeout(function() {
        window.location.href = "<?php echo SITE_URL; ?>/";
    }, 3000);
</script>
<?php else: ?>
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
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Excluir Conta da Empresa</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Atenção!</h5>
                        <p>Você está prestes a excluir a conta da sua empresa do OpenToJob. Esta ação <strong>não pode ser desfeita</strong>.</p>
                        <p>Ao excluir sua conta:</p>
                        <ul>
                            <li>Os dados da empresa serão anonimizados</li>
                            <li>Todas as suas vagas serão marcadas como inativas</li>
                            <li>Seu perfil não estará mais visível para talentos</li>
                            <li>Histórico de interações será mantido apenas para fins estatísticos, sem identificação da empresa</li>
                            <li>Você perderá acesso a todas as funcionalidades da plataforma</li>
                        </ul>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Dados da Empresa</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($empresa['nome']); ?></p>
                            <p><strong>Razão Social:</strong> <?php echo htmlspecialchars($empresa['razao_social'] ?? 'Não informada'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($empresa['email']); ?></p>
                            <p><strong>Segmento:</strong> <?php echo htmlspecialchars($empresa['segmento'] ?? 'Não informado'); ?></p>
                        </div>
                    </div>
                    
                    <form method="post" id="formExcluirConta">
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Por que você está excluindo a conta da empresa? <span class="text-danger">*</span></label>
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
                                Confirmo que desejo excluir permanentemente a conta da empresa e entendo que esta ação não pode ser desfeita.
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/?route=painel_empresa" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger" id="btnExcluir" disabled>
                                <i class="fas fa-building-shield me-2"></i>Excluir Conta da Empresa
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
        if (!confirm('ATENÇÃO: Você está prestes a excluir permanentemente a conta da empresa. Esta ação não pode ser desfeita. Deseja continuar?')) {
            e.preventDefault();
        }
    });
});
</script>
<?php endif; ?>
