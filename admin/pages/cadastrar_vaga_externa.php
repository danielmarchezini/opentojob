<?php
// Iniciar o buffer de saída para evitar problemas com o header
ob_start();

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter tipos de contrato, regimes de trabalho e níveis de experiência
try {
    $tipos_contrato = $db->fetchAll("SELECT id, nome FROM tipos_contrato ORDER BY nome");
    $regimes_trabalho = $db->fetchAll("SELECT id, nome FROM regimes_trabalho ORDER BY nome");
    $niveis_experiencia = $db->fetchAll("SELECT id, nome FROM niveis_experiencia ORDER BY nome");
} catch (PDOException $e) {
    error_log('Erro ao carregar opções de selects: ' . $e->getMessage());
    $tipos_contrato = [];
    $regimes_trabalho = [];
    $niveis_experiencia = [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar e processar os dados do formulário
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $empresa_externa = isset($_POST['empresa_externa']) ? trim($_POST['empresa_externa']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $requisitos = isset($_POST['requisitos']) ? trim($_POST['requisitos']) : '';
    $beneficios = isset($_POST['beneficios']) ? trim($_POST['beneficios']) : '';
    $tipo_contrato_id = isset($_POST['tipo_contrato_id']) ? (int)$_POST['tipo_contrato_id'] : null;
    $regime_trabalho_id = isset($_POST['regime_trabalho_id']) ? (int)$_POST['regime_trabalho_id'] : null;
    $localizacao = isset($_POST['localizacao']) ? trim($_POST['localizacao']) : '';
    $salario_min = isset($_POST['salario_min']) ? (float)$_POST['salario_min'] : null;
    $salario_max = isset($_POST['salario_max']) ? (float)$_POST['salario_max'] : null;
    $nivel_experiencia_id = isset($_POST['nivel_experiencia_id']) ? (int)$_POST['nivel_experiencia_id'] : null;
    $url_externa = isset($_POST['url_externa']) ? trim($_POST['url_externa']) : '';
    
    // Validação básica
    $erros = [];
    
    if (empty($titulo)) {
        $erros[] = "O título da vaga é obrigatório.";
    }
    
    if (empty($empresa_externa)) {
        $erros[] = "O nome da empresa é obrigatório.";
    }
    
    if (empty($descricao)) {
        $erros[] = "A descrição da vaga é obrigatória.";
    }
    
    // Se não houver erros, inserir a vaga
    if (empty($erros)) {
        try {
            $slug = gerarSlug($titulo);
            
            $vaga_id = $db->insert('vagas', [
                'empresa_id' => null,
                'empresa_externa' => $empresa_externa,
                'titulo' => $titulo,
                'slug' => $slug,
                'descricao' => $descricao,
                'requisitos' => $requisitos,
                'beneficios' => $beneficios,
                'tipo_contrato_id' => $tipo_contrato_id,
                'regime_trabalho_id' => $regime_trabalho_id,
                'localizacao' => $localizacao,
                'salario_min' => $salario_min,
                'salario_max' => $salario_max,
                'nivel_experiencia_id' => $nivel_experiencia_id,
                'status' => 'aberta',
                'tipo_vaga' => 'externa',
                'url_externa' => $url_externa,
                'data_publicacao' => date('Y-m-d H:i:s'),
                'data_atualizacao' => date('Y-m-d H:i:s')
            ]);
            
            // Registrar ação no log
            logAdminAction('cadastrar_vaga', "Vaga ID: $vaga_id, Título: $titulo");
            
            // Mensagem de sucesso
            echo '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-1"></i> Vaga cadastrada com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            
            // Limpar os campos do formulário
            $titulo = $descricao = $requisitos = $beneficios = $localizacao = $empresa_externa = $url_externa = '';
            $tipo_contrato_id = $regime_trabalho_id = $nivel_experiencia_id = $salario_min = $salario_max = null;
            
        } catch (Exception $e) {
            $erros[] = "Erro ao cadastrar a vaga: " . $e->getMessage();
        }
    }
    
    // Exibir erros, se houver
    if (!empty($erros)) {
        echo '<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-1"></i><ul>';
        foreach ($erros as $erro) {
            echo '<li>' . $erro . '</li>';
        }
        echo '</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cadastrar Nova Vaga Externa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin">Gerenciar Vagas</a></li>
        <li class="breadcrumb-item active">Cadastrar Nova Vaga Externa</li>
    </ol>

    <!-- Formulário de Cadastro -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-alt me-1"></i>
            Formulário de Cadastro de Vaga Externa
        </div>
            <div class="card-body">
                <form method="post" action="<?php echo SITE_URL; ?>/?route=cadastrar_vaga_externa">
                    <div class="form-group mb-3">
                        <label for="empresa_externa">Nome da Empresa *</label>
                        <input type="text" name="empresa_externa" id="empresa_externa" class="form-control" value="<?php echo isset($empresa_externa) ? htmlspecialchars($empresa_externa) : ''; ?>" required>
                        <small class="form-text text-muted">Digite o nome da empresa externa (não cadastrada no sistema).</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="titulo">Título da Vaga *</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo isset($titulo) ? htmlspecialchars($titulo) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="descricao">Descrição da Vaga *</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="5" required><?php echo isset($descricao) ? htmlspecialchars($descricao) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="requisitos">Requisitos</label>
                        <textarea name="requisitos" id="requisitos" class="form-control" rows="4"><?php echo isset($requisitos) ? htmlspecialchars($requisitos) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="beneficios">Benefícios</label>
                        <textarea name="beneficios" id="beneficios" class="form-control" rows="4"><?php echo isset($beneficios) ? htmlspecialchars($beneficios) : ''; ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_contrato_id">Tipo de Contrato</label>
                                <select class="form-control" id="tipo_contrato_id" name="tipo_contrato_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($tipos_contrato as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($tipo_contrato_id) && $tipo_contrato_id == $tipo['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="regime_trabalho_id">Regime de Trabalho</label>
                                <select class="form-control" id="regime_trabalho_id" name="regime_trabalho_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($regimes_trabalho as $regime): ?>
                                        <option value="<?php echo $regime['id']; ?>" <?php echo (isset($regime_trabalho_id) && $regime_trabalho_id == $regime['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($regime['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nivel_experiencia_id">Nível de Experiência</label>
                                <select class="form-control" id="nivel_experiencia_id" name="nivel_experiencia_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($niveis_experiencia as $nivel): ?>
                                        <option value="<?php echo $nivel['id']; ?>" <?php echo (isset($nivel_experiencia_id) && $nivel_experiencia_id == $nivel['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nivel['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="localizacao">Localização</label>
                                <input type="text" name="localizacao" id="localizacao" class="form-control" value="<?php echo isset($localizacao) ? htmlspecialchars($localizacao) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="salario_min" name="salario_min" step="0.01" value="<?php echo isset($salario_min) ? htmlspecialchars($salario_min) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="salario_max" name="salario_max" step="0.01" value="<?php echo isset($salario_max) ? htmlspecialchars($salario_max) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="url_externa">URL Externa da Vaga <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                            </div>
                            <input type="url" name="url_externa" id="url_externa" class="form-control" placeholder="https://exemplo.com/vaga" value="<?php echo isset($url_externa) ? htmlspecialchars($url_externa) : ''; ?>" required>
                        </div>
                        <small class="form-text text-muted">Insira o link direto para a vaga no site da empresa ou plataforma de empregos.</small>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Cadastrar Vaga
                        </button>
                        <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Função auxiliar para gerar slug
function gerarSlug(texto) {
    return texto.toLowerCase()
               .replace(/[^\w ]+/g, '')
               .replace(/ +/g, '-');
}
</script>
