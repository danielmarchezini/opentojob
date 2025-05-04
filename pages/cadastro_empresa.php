<div class="auth-container">
    <div class="auth-box">
        <h2 class="auth-title">Cadastro de Empresa</h2>
        <p class="auth-subtitle">Crie sua conta e encontre os melhores talentos para sua empresa</p>
        
        <?php
        // Processar o formulário quando enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
            $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
            // Campos telefone e CNPJ removidos conforme solicitado
            $segmento = isset($_POST['segmento']) ? trim($_POST['segmento']) : '';
            $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
            $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
            $termos = isset($_POST['termos']) ? true : false;
            
            $erros = [];
            
            // Validação básica
            if (empty($nome)) {
                $erros[] = "O nome da empresa é obrigatório.";
            }
            
            if (empty($email)) {
                $erros[] = "O e-mail é obrigatório.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "Por favor, insira um e-mail válido.";
            }
            
            if (empty($senha)) {
                $erros[] = "A senha é obrigatória.";
            } elseif (strlen($senha) < 6) {
                $erros[] = "A senha deve ter pelo menos 6 caracteres.";
            }
            
            if ($senha !== $confirmar_senha) {
                $erros[] = "As senhas não coincidem.";
            }
            
            if (!$termos) {
                $erros[] = "Você precisa aceitar os termos de uso.";
            }
            
            // Se não houver erros, prosseguir com o cadastro
            if (empty($erros)) {
                $db = Database::getInstance();
                
                // Verificar se o e-mail já está cadastrado
                $usuario = $db->fetch("SELECT id FROM usuarios WHERE email = :email", ['email' => $email]);
                
                if ($usuario) {
                    echo '<div class="alert alert-danger">Este e-mail já está cadastrado.</div>';
                } else {
                    // Dados do usuário
                    $dadosUsuario = [
                        'nome' => $nome,
                        'email' => $email,
                        'senha' => password_hash($senha, PASSWORD_BCRYPT, ['cost' => HASH_COST]),
                        'tipo' => 'empresa',
                        'status' => 'pendente',
                        'data_cadastro' => date('Y-m-d H:i:s'),
                        'telefone' => $telefone,
                        'website' => isset($_POST['website']) ? trim($_POST['website']) : ''
                    ];
                    
                    // Iniciar transação
                    $db->query("START TRANSACTION");
                    
                    try {
                        // Inserir usuário
                        $usuarioId = $db->insert('usuarios', $dadosUsuario);
                        
                        // Inserir empresa
                        $dadosEmpresa = [
                            'usuario_id' => $usuarioId,
                            'cnpj' => $cnpj,
                            'razao_social' => $nome,
                            'segmento' => $segmento,
                            'cidade' => $cidade,
                            'estado' => $estado,
                            'publicar_vagas' => 0 // Precisa ser habilitado pelo admin
                        ];
                        
                        $db->insert('empresas', $dadosEmpresa);
                        
                        // Commit da transação
                        $db->query("COMMIT");
                        
                        // Preparar dados do usuário para envio de e-mails
                        $dadosUsuarioCompleto = [
                            'id' => $usuarioId,
                            'nome' => $nome,
                            'email' => $email,
                            'tipo' => 'empresa',
                            'data_cadastro' => date('Y-m-d H:i:s')
                        ];
                        
                        // Enviar e-mail de instruções para a empresa
                        require_once 'includes/Mailer.php';
                        $mailer = Mailer::getInstance();
                        $mailer->enviarEmailInstrucoesAprovacao($dadosUsuarioCompleto);
                        
                        // Enviar e-mail de notificação para o administrador
                        $mailer->enviarEmailNovoUsuarioAdmin($dadosUsuarioCompleto);
                        
                        // Redirecionar para a página de sucesso
                        $_SESSION['flash_message'] = "Cadastro realizado com sucesso! Verifique seu e-mail para os próximos passos e aguarde a aprovação do administrador para acessar sua conta.";
                        $_SESSION['flash_type'] = "success";
                        
                        // Redirecionar para a página de login
                        header("Location: " . SITE_URL . "/?route=entrar");
                        exit;
                    } catch (Exception $e) {
                        // Rollback em caso de erro
                        $db->query("ROLLBACK");
                        echo '<div class="alert alert-danger">Erro ao cadastrar: ' . $e->getMessage() . '</div>';
                    }
                }
            } else {
                // Exibir erros
                echo '<div class="alert alert-danger"><ul>';
                foreach ($erros as $erro) {
                    echo '<li>' . $erro . '</li>';
                }
                echo '</ul></div>';
            }
        }
        ?>
        
        <form method="post" action="<?php echo SITE_URL; ?>/?route=cadastro_empresa" class="auth-form">
            <div class="form-group">
                <label for="nome" class="form-label">Nome da empresa *</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">E-mail corporativo *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="senha" class="form-label">Senha *</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                    <small class="form-text">Mínimo de 6 caracteres</small>
                </div>
                
                <div class="form-group col-md-6">
                    <label for="confirmar_senha" class="form-label">Confirmar senha *</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                </div>
            </div>
            
            <!-- Campos telefone e CNPJ removidos conforme solicitado -->
            
            <div class="form-group">
                <label for="website" class="form-label">Website</label>
                <input type="url" id="website" name="website" class="form-control" value="<?php echo isset($website) ? htmlspecialchars($website) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="segmento" class="form-label">Segmento de atuação</label>
                <select id="segmento" name="segmento" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="Tecnologia" <?php echo (isset($segmento) && $segmento == 'Tecnologia') ? 'selected' : ''; ?>>Tecnologia</option>
                    <option value="Saúde" <?php echo (isset($segmento) && $segmento == 'Saúde') ? 'selected' : ''; ?>>Saúde</option>
                    <option value="Educação" <?php echo (isset($segmento) && $segmento == 'Educação') ? 'selected' : ''; ?>>Educação</option>
                    <option value="Finanças" <?php echo (isset($segmento) && $segmento == 'Finanças') ? 'selected' : ''; ?>>Finanças</option>
                    <option value="Varejo" <?php echo (isset($segmento) && $segmento == 'Varejo') ? 'selected' : ''; ?>>Varejo</option>
                    <option value="Indústria" <?php echo (isset($segmento) && $segmento == 'Indústria') ? 'selected' : ''; ?>>Indústria</option>
                    <option value="Serviços" <?php echo (isset($segmento) && $segmento == 'Serviços') ? 'selected' : ''; ?>>Serviços</option>
                    <option value="Marketing" <?php echo (isset($segmento) && $segmento == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                    <option value="Outro" <?php echo (isset($segmento) && $segmento == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo isset($cidade) ? htmlspecialchars($cidade) : ''; ?>">
                </div>
                
                <div class="form-group col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="AC" <?php echo (isset($estado) && $estado == 'AC') ? 'selected' : ''; ?>>AC</option>
                        <option value="AL" <?php echo (isset($estado) && $estado == 'AL') ? 'selected' : ''; ?>>AL</option>
                        <option value="AP" <?php echo (isset($estado) && $estado == 'AP') ? 'selected' : ''; ?>>AP</option>
                        <option value="AM" <?php echo (isset($estado) && $estado == 'AM') ? 'selected' : ''; ?>>AM</option>
                        <option value="BA" <?php echo (isset($estado) && $estado == 'BA') ? 'selected' : ''; ?>>BA</option>
                        <option value="CE" <?php echo (isset($estado) && $estado == 'CE') ? 'selected' : ''; ?>>CE</option>
                        <option value="DF" <?php echo (isset($estado) && $estado == 'DF') ? 'selected' : ''; ?>>DF</option>
                        <option value="ES" <?php echo (isset($estado) && $estado == 'ES') ? 'selected' : ''; ?>>ES</option>
                        <option value="GO" <?php echo (isset($estado) && $estado == 'GO') ? 'selected' : ''; ?>>GO</option>
                        <option value="MA" <?php echo (isset($estado) && $estado == 'MA') ? 'selected' : ''; ?>>MA</option>
                        <option value="MT" <?php echo (isset($estado) && $estado == 'MT') ? 'selected' : ''; ?>>MT</option>
                        <option value="MS" <?php echo (isset($estado) && $estado == 'MS') ? 'selected' : ''; ?>>MS</option>
                        <option value="MG" <?php echo (isset($estado) && $estado == 'MG') ? 'selected' : ''; ?>>MG</option>
                        <option value="PA" <?php echo (isset($estado) && $estado == 'PA') ? 'selected' : ''; ?>>PA</option>
                        <option value="PB" <?php echo (isset($estado) && $estado == 'PB') ? 'selected' : ''; ?>>PB</option>
                        <option value="PR" <?php echo (isset($estado) && $estado == 'PR') ? 'selected' : ''; ?>>PR</option>
                        <option value="PE" <?php echo (isset($estado) && $estado == 'PE') ? 'selected' : ''; ?>>PE</option>
                        <option value="PI" <?php echo (isset($estado) && $estado == 'PI') ? 'selected' : ''; ?>>PI</option>
                        <option value="RJ" <?php echo (isset($estado) && $estado == 'RJ') ? 'selected' : ''; ?>>RJ</option>
                        <option value="RN" <?php echo (isset($estado) && $estado == 'RN') ? 'selected' : ''; ?>>RN</option>
                        <option value="RS" <?php echo (isset($estado) && $estado == 'RS') ? 'selected' : ''; ?>>RS</option>
                        <option value="RO" <?php echo (isset($estado) && $estado == 'RO') ? 'selected' : ''; ?>>RO</option>
                        <option value="RR" <?php echo (isset($estado) && $estado == 'RR') ? 'selected' : ''; ?>>RR</option>
                        <option value="SC" <?php echo (isset($estado) && $estado == 'SC') ? 'selected' : ''; ?>>SC</option>
                        <option value="SP" <?php echo (isset($estado) && $estado == 'SP') ? 'selected' : ''; ?>>SP</option>
                        <option value="SE" <?php echo (isset($estado) && $estado == 'SE') ? 'selected' : ''; ?>>SE</option>
                        <option value="TO" <?php echo (isset($estado) && $estado == 'TO') ? 'selected' : ''; ?>>TO</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group form-check">
                <input type="checkbox" id="termos" name="termos" class="form-check-input" <?php echo isset($termos) && $termos ? 'checked' : ''; ?> required>
                <label for="termos" class="form-check-label">Concordo com os <a href="<?php echo SITE_URL; ?>/?route=termos" target="_blank">termos de uso</a> e <a href="<?php echo SITE_URL; ?>/?route=privacidade" target="_blank">política de privacidade</a></label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
            </div>
            
            <div class="auth-footer">
                Já tem uma conta? <a href="<?php echo SITE_URL; ?>/?route=entrar">Faça login</a>
            </div>
        </form>
    </div>
    
    <div class="auth-info">
        <!-- Espaço para anúncio na barra lateral da página de cadastro de empresa -->
        <?php if ($adsense->isPosicaoAtiva('cadastro_empresa_lateral')): ?>
        <div class="ad-container mb-4">
            <?php echo $adsense->exibirAnuncio('cadastro_empresa_lateral', 'vertical'); ?>
        </div>
        <?php endif; ?>
        
        <h3>Por que cadastrar sua empresa no OpenToJob?</h3>
        <ul class="auth-benefits">
            <li><i class="fas fa-check-circle"></i> Acesso a talentos qualificados e disponíveis</li>
            <li><i class="fas fa-check-circle"></i> Filtros avançados para encontrar o perfil ideal</li>
            <li><i class="fas fa-check-circle"></i> Comunique-se diretamente com os candidatos</li>
            <li><i class="fas fa-check-circle"></i> Reduza o tempo e custo de contratação</li>
        </ul>
        <!-- Destaque para a função "Procura-se" -->
        <div class="procura-se-highlight">
            <h4><i class="fas fa-search"></i> Nova função: Procura-se</h4>
            <p>Publique anúncios de "Procura-se" para encontrar profissionais específicos que sua empresa precisa contratar imediatamente.</p>
            <ul>
                <li><i class="fas fa-check"></i> Especifique exatamente o perfil que você procura</li>
                <li><i class="fas fa-check"></i> Receba candidaturas apenas de talentos qualificados</li>
                <li><i class="fas fa-check"></i> Reduza o tempo de busca por profissionais</li>
            </ul>
            <div class="procura-se-cta">
                <a href="<?php echo SITE_URL; ?>/?route=sobre_procura_se" class="btn btn-primary btn-sm">Conheça a função Procura-se</a>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para o destaque da função Procura-se */
.procura-se-highlight {
    background-color: #f0f4f8; /* Fundo azulado claro para melhor contraste */
    border-left: 4px solid var(--accent-color);
    padding: 20px;
    margin-top: 30px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    color: #333; /* Cor de texto escura explícita */
}

.procura-se-highlight h4 {
    color: var(--accent-color);
    margin-bottom: 15px;
    font-weight: 600;
}

.procura-se-highlight p {
    margin-bottom: 15px;
    line-height: 1.5;
    color: #333; /* Cor de texto escura explícita */
}

.procura-se-highlight ul {
    list-style: none;
    padding-left: 0;
    margin-bottom: 20px;
}

.procura-se-highlight ul li {
    margin-bottom: 8px;
    display: flex;
    align-items: flex-start;
    color: #333; /* Cor de texto escura explícita */
}

.procura-se-highlight ul li i {
    color: var(--accent-color);
    margin-right: 10px;
    margin-top: 4px;
}

.procura-se-cta {
    text-align: center;
    margin-top: 20px;
}

.auth-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

@media (min-width: 992px) {
    .auth-container {
        grid-template-columns: 1fr 1fr;
    }
}

.auth-box {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    padding: 30px;
}

.auth-title {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: var(--primary-color);
}

.auth-subtitle {
    color: var(--gray-color);
    margin-bottom: 30px;
}

.auth-form .form-group {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -10px;
    margin-left: -10px;
}

.form-row > .form-group {
    padding-right: 10px;
    padding-left: 10px;
    flex: 1;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 84, 166, 0.1);
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 0.8rem;
    color: var(--gray-color);
}

.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.form-check-input {
    margin-right: 10px;
}

.btn {
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    padding: 10px 20px;
    font-size: 1rem;
    border-radius: 5px;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: #004080;
}

.btn-block {
    display: block;
    width: 100%;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    color: var(--gray-color);
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
}

.auth-info {
    padding: 30px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 10px;
    align-self: center;
}

.auth-info h3 {
    font-size: 1.5rem;
    margin-bottom: 20px;
}

.auth-benefits {
    list-style: none;
    padding: 0;
    margin-bottom: 30px;
}

.auth-benefits li {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.auth-benefits i {
    color: #ffcc00;
    margin-right: 10px;
    font-size: 1.2rem;
}

.auth-testimonial {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 5px;
    margin-top: 30px;
}

.auth-testimonial p {
    font-style: italic;
    margin-bottom: 10px;
}

.testimonial-author {
    text-align: right;
    font-size: 0.9rem;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-md-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
}

@media (max-width: 768px) {
    .col-md-6, .col-md-4, .col-md-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
