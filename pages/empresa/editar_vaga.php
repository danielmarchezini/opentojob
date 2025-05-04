<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa') && !Auth::checkUserType('admin')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Verificar se o ID da vaga foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "ID da vaga não fornecido.";
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/?route=gerenciar_vagas');
    exit;
}

$vaga_id = (int)$_GET['id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Verificar se a vaga existe e pertence à empresa logada
try {
    $vaga = $db->fetch("
        SELECT * FROM vagas 
        WHERE id = :id AND empresa_id = :empresa_id
    ", [
        'id' => $vaga_id,
        'empresa_id' => $usuario_id
    ]);
    
    if (!$vaga) {
        $_SESSION['flash_message'] = "Vaga não encontrada ou você não tem permissão para editá-la.";
        $_SESSION['flash_type'] = "danger";
        header('Location: ' . SITE_URL . '/?route=gerenciar_vagas');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar vaga: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao buscar dados da vaga: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/?route=gerenciar_vagas');
    exit;
}

// Verificar se o recrutamento interno está habilitado
try {
    $recrutamento_interno_habilitado = $db->fetch("
        SELECT valor FROM configuracoes WHERE chave = 'recrutamento_interno_habilitado'
    ");
    $recrutamento_interno_habilitado = $recrutamento_interno_habilitado['valor'] ?? '1';
} catch (PDOException $e) {
    error_log("Erro ao verificar configuração de recrutamento interno: " . $e->getMessage());
    $recrutamento_interno_habilitado = '1'; // Valor padrão caso ocorra erro
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $requisitos = trim($_POST['requisitos'] ?? '');
    $beneficios = trim($_POST['beneficios'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $tipo_contrato = $_POST['tipo_contrato'] ?? '';
    $regime_trabalho = $_POST['regime_trabalho'] ?? '';
    $nivel_experiencia = $_POST['nivel_experiencia'] ?? '';
    $tipo_vaga = $_POST['tipo_vaga'] ?? 'interna';
    $url_externa = $tipo_vaga === 'externa' ? trim($_POST['url_externa'] ?? '') : '';
    $salario_min = !empty($_POST['salario_min']) ? (float)$_POST['salario_min'] : null;
    $salario_max = !empty($_POST['salario_max']) ? (float)$_POST['salario_max'] : null;
    $mostrar_salario = isset($_POST['mostrar_salario']) ? 1 : 0;
    $status = $_POST['status'] ?? 'ativa';
    
    // Debug - registrar valores recebidos
    error_log("Dados do formulário: " . 
        "tipo_contrato=" . $tipo_contrato . ", " . 
        "regime_trabalho=" . $regime_trabalho . ", " . 
        "nivel_experiencia=" . $nivel_experiencia);
    
    // Validar campos obrigatórios
    $errors = [];
    
    if (empty($titulo)) {
        $errors[] = "O título da vaga é obrigatório.";
    }
    
    if (empty($descricao)) {
        $errors[] = "A descrição da vaga é obrigatória.";
    }
    
    if (empty($cidade)) {
        $errors[] = "A cidade é obrigatória.";
    }
    
    if (empty($estado)) {
        $errors[] = "O estado é obrigatório.";
    }
    
    if (empty($tipo_contrato)) {
        $errors[] = "O tipo de contrato é obrigatório.";
    }
    
    if (empty($regime_trabalho)) {
        $errors[] = "O regime de trabalho é obrigatório.";
    }
    
    if (empty($nivel_experiencia)) {
        $errors[] = "O nível de experiência é obrigatório.";
    }
    
    // Se não houver erros, atualizar a vaga
    if (empty($errors)) {
        try {
            // Verificar se a coluna data_atualizacao existe na tabela vagas
            $column_exists = false;
            try {
                $columns = $db->fetchAll("SHOW COLUMNS FROM vagas LIKE 'data_atualizacao'");
                $column_exists = !empty($columns);
            } catch (PDOException $e) {
                error_log("Erro ao verificar coluna data_atualizacao: " . $e->getMessage());
            }
            
            // Preparar dados para atualização
            $data = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'requisitos' => $requisitos,
                'beneficios' => $beneficios,
                'cidade' => $cidade,
                'estado' => $estado,
                'tipo_contrato' => $tipo_contrato,
                'regime_trabalho' => $regime_trabalho,
                'nivel_experiencia' => $nivel_experiencia,
                'tipo_vaga' => $tipo_vaga,
                'url_externa' => $url_externa,
                'salario_min' => $salario_min,
                'salario_max' => $salario_max,
                'mostrar_salario' => $mostrar_salario,
                'status' => $status
            ];
            
            // Debug - registrar dados que serão salvos
            error_log("Dados para salvar: " . json_encode($data));
            
            // Adicionar data_atualizacao apenas se a coluna existir
            if ($column_exists) {
                $data['data_atualizacao'] = date('Y-m-d H:i:s');
            }
            
            // Construir a query manualmente para garantir que todos os campos sejam atualizados
            $query = "UPDATE vagas SET 
                titulo = :titulo,
                descricao = :descricao,
                requisitos = :requisitos,
                beneficios = :beneficios,
                cidade = :cidade,
                estado = :estado,
                tipo_contrato = :tipo_contrato,
                regime_trabalho = :regime_trabalho,
                nivel_experiencia = :nivel_experiencia,
                tipo_vaga = :tipo_vaga,
                url_externa = :url_externa,
                salario_min = :salario_min,
                salario_max = :salario_max,
                mostrar_salario = :mostrar_salario,
                status = :status";
                
            // Adicionar data_atualizacao se a coluna existir
            if ($column_exists) {
                $query .= ",\n                data_atualizacao = :data_atualizacao";
                $data['data_atualizacao'] = date('Y-m-d H:i:s');
            }
            
            $query .= "\n            WHERE id = :id";
            $data['id'] = $vaga_id;
            
            $db->query($query, $data);
            
            $_SESSION['flash_message'] = "Vaga atualizada com sucesso!";
            $_SESSION['flash_type'] = "success";
            
            // Redirecionar para a página de gerenciamento de vagas
            header('Location: ' . SITE_URL . '/?route=gerenciar_vagas');
            exit;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar vaga: " . $e->getMessage());
            $_SESSION['flash_message'] = "Erro ao atualizar vaga: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // Exibir erros
        $_SESSION['flash_message'] = implode("<br>", $errors);
        $_SESSION['flash_type'] = "danger";
    }
}

// Obter estados brasileiros para o select
$estados = [
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AP' => 'Amapá',
    'AM' => 'Amazonas',
    'BA' => 'Bahia',
    'CE' => 'Ceará',
    'DF' => 'Distrito Federal',
    'ES' => 'Espírito Santo',
    'GO' => 'Goiás',
    'MA' => 'Maranhão',
    'MT' => 'Mato Grosso',
    'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais',
    'PA' => 'Pará',
    'PB' => 'Paraíba',
    'PR' => 'Paraná',
    'PE' => 'Pernambuco',
    'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia',
    'RR' => 'Roraima',
    'SC' => 'Santa Catarina',
    'SP' => 'São Paulo',
    'SE' => 'Sergipe',
    'TO' => 'Tocantins'
];
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Editar Vaga</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="titulo" class="form-label">Título da Vaga *</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($vaga['titulo']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="descricao" class="form-label">Descrição da Vaga *</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($vaga['descricao']); ?></textarea>
                                <small class="text-muted">Descreva detalhadamente as responsabilidades e o dia a dia do profissional.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="requisitos" class="form-label">Requisitos</label>
                                <textarea class="form-control" id="requisitos" name="requisitos" rows="4"><?php echo htmlspecialchars($vaga['requisitos']); ?></textarea>
                                <small class="text-muted">Liste as habilidades, conhecimentos e experiências necessárias.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="beneficios" class="form-label">Benefícios</label>
                                <textarea class="form-control" id="beneficios" name="beneficios" rows="4"><?php echo htmlspecialchars($vaga['beneficios']); ?></textarea>
                                <small class="text-muted">Liste os benefícios oferecidos pela empresa.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cidade" class="form-label">Cidade *</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($vaga['cidade']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado *</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="">Selecione o estado</option>
                                    <?php foreach ($estados as $sigla => $nome): ?>
                                        <option value="<?php echo $sigla; ?>" <?php echo $vaga['estado'] === $sigla ? 'selected' : ''; ?>>
                                            <?php echo $nome; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_vaga" class="form-label">Tipo de Vaga *</label>
                                <select class="form-select" id="tipo_vaga" name="tipo_vaga" required onchange="toggleUrlExterna()">
                                    <option value="interna" <?php echo $vaga['tipo_vaga'] === 'interna' ? 'selected' : ''; ?> <?php echo $recrutamento_interno_habilitado === '0' ? 'disabled' : ''; ?>>Interna (Open2W)</option>
                                    <option value="externa" <?php echo $vaga['tipo_vaga'] === 'externa' ? 'selected' : ''; ?>>Externa (Link)</option>
                                </select>
                                <?php if ($recrutamento_interno_habilitado === '0'): ?>
                                    <small class="text-danger">O recrutamento interno está desabilitado pelo administrador.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8 mb-3" id="url_externa_container" style="display: <?php echo $vaga['tipo_vaga'] === 'externa' ? 'block' : 'none'; ?>">
                                <label for="url_externa" class="form-label">URL da Vaga Externa *</label>
                                <input type="url" class="form-control" id="url_externa" name="url_externa" value="<?php echo htmlspecialchars($vaga['url_externa'] ?? ''); ?>" <?php echo $vaga['tipo_vaga'] === 'externa' ? 'required' : ''; ?>>
                                <small class="text-muted">Link completo para onde o candidato será redirecionado (ex: https://site.com/vaga)</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_contrato" class="form-label">Tipo de Contrato *</label>
                                <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="CLT" <?php echo $vaga['tipo_contrato'] === 'CLT' ? 'selected' : ''; ?>>CLT</option>
                                    <option value="PJ" <?php echo $vaga['tipo_contrato'] === 'PJ' ? 'selected' : ''; ?>>PJ</option>
                                    <option value="Estágio" <?php echo $vaga['tipo_contrato'] === 'Estágio' ? 'selected' : ''; ?>>Estágio</option>
                                    <option value="Freelancer" <?php echo $vaga['tipo_contrato'] === 'Freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                                    <option value="Temporário" <?php echo $vaga['tipo_contrato'] === 'Temporário' ? 'selected' : ''; ?>>Temporário</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="regime_trabalho" class="form-label">Regime de Trabalho *</label>
                                <select class="form-select" id="regime_trabalho" name="regime_trabalho" required>
                                    <option value="">Selecione o modelo</option>
                                    <option value="Presencial" <?php echo $vaga['regime_trabalho'] === 'Presencial' ? 'selected' : ''; ?>>Presencial</option>
                                    <option value="Remoto" <?php echo $vaga['regime_trabalho'] === 'Remoto' ? 'selected' : ''; ?>>Remoto</option>
                                    <option value="Híbrido" <?php echo $vaga['regime_trabalho'] === 'Híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nivel_experiencia" class="form-label">Nível de Experiência *</label>
                                <select class="form-select" id="nivel_experiencia" name="nivel_experiencia" required>
                                    <option value="">Selecione o nível</option>
                                    <option value="Estágio" <?php echo $vaga['nivel_experiencia'] === 'Estágio' ? 'selected' : ''; ?>>Estágio</option>
                                    <option value="Júnior" <?php echo $vaga['nivel_experiencia'] === 'Júnior' ? 'selected' : ''; ?>>Júnior</option>
                                    <option value="Pleno" <?php echo $vaga['nivel_experiencia'] === 'Pleno' ? 'selected' : ''; ?>>Pleno</option>
                                    <option value="Sênior" <?php echo $vaga['nivel_experiencia'] === 'Sênior' ? 'selected' : ''; ?>>Sênior</option>
                                    <option value="Especialista" <?php echo $vaga['nivel_experiencia'] === 'Especialista' ? 'selected' : ''; ?>>Especialista</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="salario_min" class="form-label">Salário Mínimo</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salario_min" name="salario_min" value="<?php echo htmlspecialchars($vaga['salario_min'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="salario_max" class="form-label">Salário Máximo</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="salario_max" name="salario_max" value="<?php echo htmlspecialchars($vaga['salario_max'] ?? ''); ?>">
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="mostrar_salario" name="mostrar_salario" <?php echo ($vaga['mostrar_salario'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="mostrar_salario">
                                        Mostrar salário no anúncio
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status da Vaga *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="ativa" <?php echo $vaga['status'] === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                                    <option value="inativa" <?php echo $vaga['status'] === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                                    <option value="encerrada" <?php echo $vaga['status'] === 'encerrada' ? 'selected' : ''; ?>>Encerrada</option>
                                    <option value="rascunho" <?php echo $vaga['status'] === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                </button>
                                <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                        </div>

                        <script>
                        function toggleUrlExterna() {
                            const tipoVaga = document.getElementById('tipo_vaga').value;
                            const urlExternaContainer = document.getElementById('url_externa_container');
                            const urlExternaInput = document.getElementById('url_externa');
                            
                            if (tipoVaga === 'externa') {
                                urlExternaContainer.style.display = 'block';
                                urlExternaInput.required = true;
                            } else {
                                urlExternaContainer.style.display = 'none';
                                urlExternaInput.required = false;
                                urlExternaInput.value = '';
                            }
                        }
                        </script>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
