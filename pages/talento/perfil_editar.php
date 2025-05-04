<?php
// Verificar se o usuário está logado e é um talento
if (!Auth::checkUserType('talento') && !Auth::checkUserType('admin')) {
    echo "<script>window.location.href = '" . SITE_URL . "/?route=entrar';</script>";
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter detalhes do talento
try {
    $talento = $db->fetchRow("
        SELECT u.nome, u.email, u.status, t.*
        FROM usuarios u
        LEFT JOIN talentos t ON u.id = t.usuario_id
        WHERE u.id = :id
    ", ['id' => $usuario_id]);
    
    if (!$talento) {
        $_SESSION['flash_message'] = "Perfil não encontrado.";
        $_SESSION['flash_type'] = "danger";
        echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes do talento: " . $e->getMessage());
    $_SESSION['flash_message'] = "Erro ao carregar perfil: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
    echo "<script>window.location.href = '" . SITE_URL . "/?route=painel_talento';</script>";
    exit;
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do usuário
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Dados do talento
    $profissao = isset($_POST['profissao']) ? trim($_POST['profissao']) : '';
    $nivel = isset($_POST['nivel']) ? trim($_POST['nivel']) : '';
    $resumo = isset($_POST['resumo']) ? trim($_POST['resumo']) : '';
    $experiencia = isset($_POST['experiencia']) ? trim($_POST['experiencia']) : '';
    $formacao = isset($_POST['formacao']) ? trim($_POST['formacao']) : '';
    $habilidades = isset($_POST['habilidades']) ? trim($_POST['habilidades']) : '';
    $areas_interesse = isset($_POST['areas_interesse']) ? trim($_POST['areas_interesse']) : '';
    $cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $data_nascimento = isset($_POST['data_nascimento']) ? trim($_POST['data_nascimento']) : '';
    $linkedin = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : '';
    $github = isset($_POST['github']) ? trim($_POST['github']) : '';
    $portfolio = isset($_POST['portfolio']) ? trim($_POST['portfolio']) : '';
    $website = isset($_POST['website']) ? trim($_POST['website']) : '';
    
    // Novos campos adicionados
    $genero = isset($_POST['genero']) ? trim($_POST['genero']) : '';
    $idiomas = isset($_POST['idiomas']) ? trim($_POST['idiomas']) : '';
    $pretensao_salarial = isset($_POST['pretensao_salarial']) ? floatval($_POST['pretensao_salarial']) : null;
    $disponibilidade = isset($_POST['disponibilidade']) ? trim($_POST['disponibilidade']) : '';
    $apresentacao = isset($_POST['apresentacao']) ? trim($_POST['apresentacao']) : '';
    $opentowork = isset($_POST['opentowork']) ? 1 : 0;
    $opentowork_visibilidade = isset($_POST['opentowork_visibilidade']) ? trim($_POST['opentowork_visibilidade']) : 'privado';
    $detalhes_experiencia = isset($_POST['detalhes_experiencia']) ? trim($_POST['detalhes_experiencia']) : '';
    
    // Manter os valores existentes para os campos removidos da interface
    $cpf = $talento['cpf'] ?? '';
    $endereco = $talento['endereco'] ?? '';
    $cep = $talento['cep'] ?? '';
    $pais = $talento['pais'] ?? 'Brasil';
    
    // Validar dados
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório.";
    }
    
    if (empty($email)) {
        $erros[] = "O e-mail é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O e-mail informado é inválido.";
    }
    
    if (empty($profissao)) {
        $erros[] = "A profissão é obrigatória.";
    }
    
    // Verificar se o e-mail já está em uso por outro usuário
    if ($email !== $talento['email']) {
        try {
            $email_existente = $db->fetchRow("
                SELECT id FROM usuarios WHERE email = :email AND id != :id
            ", [
                'email' => $email,
                'id' => $usuario_id
            ]);
            
            if ($email_existente) {
                $erros[] = "Este e-mail já está sendo utilizado por outro usuário.";
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar e-mail: " . $e->getMessage());
            $erros[] = "Erro ao verificar e-mail: " . $e->getMessage();
        }
    }
    
    // Processar upload de foto de perfil
    $foto_perfil = $talento['foto_perfil'] ?? '';
    
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['foto_perfil'];
        $nome_arquivo = $arquivo['name'];
        $tamanho_arquivo = $arquivo['size'];
        $tipo_arquivo = $arquivo['type'];
        $tmp_nome = $arquivo['tmp_name'];
        
        // Validar tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($tipo_arquivo, $tipos_permitidos)) {
            $erros[] = "Tipo de arquivo não permitido. Apenas JPEG, PNG e GIF são aceitos.";
        }
        
        // Validar tamanho do arquivo (máximo 2MB)
        $tamanho_maximo = 2 * 1024 * 1024; // 2MB em bytes
        
        if ($tamanho_arquivo > $tamanho_maximo) {
            $erros[] = "O arquivo é muito grande. O tamanho máximo permitido é 2MB.";
        }
        
        // Se não houver erros, processar o upload
        if (empty($erros)) {
            // Criar diretório de uploads se não existir
            $diretorio_uploads = dirname(dirname(dirname(__FILE__))) . '/uploads/talentos';
            
            if (!file_exists($diretorio_uploads)) {
                mkdir($diretorio_uploads, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
            $novo_nome = 'talento_' . $usuario_id . '_' . time() . '.' . $extensao;
            $caminho_destino = $diretorio_uploads . '/' . $novo_nome;
            
            // Mover arquivo para o diretório de uploads
            if (move_uploaded_file($tmp_nome, $caminho_destino)) {
                // Remover foto antiga se existir
                if (!empty($foto_perfil) && file_exists($diretorio_uploads . '/' . $foto_perfil)) {
                    unlink($diretorio_uploads . '/' . $foto_perfil);
                }
                
                $foto_perfil = $novo_nome;
            } else {
                $erros[] = "Falha ao fazer upload da foto de perfil.";
            }
        }
    }
    
    // Se não houver erros, atualizar dados
    if (empty($erros)) {
        try {
            // Iniciar transação
            $db->beginTransaction();
            
            // Atualizar dados do usuário
            $db->execute("
                UPDATE usuarios
                SET nome = :nome, email = :email
                WHERE id = :id
            ", [
                'nome' => $nome,
                'email' => $email,
                'id' => $usuario_id
            ]);
            
            // Verificar se já existe registro na tabela talentos
            $talento_existente = $db->fetchRow("
                SELECT usuario_id FROM talentos WHERE usuario_id = :usuario_id
            ", ['usuario_id' => $usuario_id]);
            
            if ($talento_existente) {
                // Processar upload de currículo
                $curriculo = $talento['curriculo'] ?? '';
                
                if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
                    $arquivo_curriculo = $_FILES['curriculo'];
                    $nome_arquivo_curriculo = $arquivo_curriculo['name'];
                    $tamanho_arquivo_curriculo = $arquivo_curriculo['size'];
                    $tipo_arquivo_curriculo = $arquivo_curriculo['type'];
                    $tmp_nome_curriculo = $arquivo_curriculo['tmp_name'];
                    
                    // Validar tipo de arquivo
                    $tipos_permitidos_curriculo = ['application/pdf'];
                    
                    if (!in_array($tipo_arquivo_curriculo, $tipos_permitidos_curriculo)) {
                        $erros[] = "Tipo de arquivo de currículo não permitido. Apenas PDF é aceito.";
                    }
                    
                    // Validar tamanho do arquivo (máximo 5MB)
                    $tamanho_maximo_curriculo = 5 * 1024 * 1024; // 5MB em bytes
                    
                    if ($tamanho_arquivo_curriculo > $tamanho_maximo_curriculo) {
                        $erros[] = "O arquivo de currículo é muito grande. O tamanho máximo permitido é 5MB.";
                    }
                    
                    // Se não houver erros, processar o upload
                    if (empty($erros)) {
                        // Criar diretório de uploads se não existir
                        $diretorio_curriculos = dirname(dirname(dirname(__FILE__))) . '/uploads/curriculos';
                        
                        if (!file_exists($diretorio_curriculos)) {
                            mkdir($diretorio_curriculos, 0755, true);
                        }
                        
                        // Gerar nome único para o arquivo
                        $extensao_curriculo = pathinfo($nome_arquivo_curriculo, PATHINFO_EXTENSION);
                        $novo_nome_curriculo = 'curriculo_' . $usuario_id . '_' . time() . '.' . $extensao_curriculo;
                        $caminho_destino_curriculo = $diretorio_curriculos . '/' . $novo_nome_curriculo;
                        
                        // Mover arquivo para o diretório de uploads
                        if (move_uploaded_file($tmp_nome_curriculo, $caminho_destino_curriculo)) {
                            // Remover currículo antigo se existir
                            if (!empty($curriculo) && file_exists($diretorio_curriculos . '/' . $curriculo)) {
                                unlink($diretorio_curriculos . '/' . $curriculo);
                            }
                            
                            $curriculo = $novo_nome_curriculo;
                        } else {
                            $erros[] = "Erro ao fazer upload do currículo.";
                        }
                    }
                }
                
                // Atualizar dados do talento
                $db->execute("
                    UPDATE talentos
                    SET profissao = :profissao,
                        nivel = :nivel,
                        resumo = :resumo,
                        experiencia = :experiencia,
                        formacao = :formacao,
                        habilidades = :habilidades,
                        areas_interesse = :areas_interesse,
                        cidade = :cidade,
                        estado = :estado,
                        telefone = :telefone,
                        data_nascimento = :data_nascimento,
                        linkedin = :linkedin,
                        github = :github,
                        portfolio = :portfolio,
                        website = :website,
                        foto_perfil = :foto_perfil,
                        cpf = :cpf,
                        genero = :genero,
                        endereco = :endereco,
                        cep = :cep,
                        pais = :pais,
                        idiomas = :idiomas,
                        pretensao_salarial = :pretensao_salarial,
                        disponibilidade = :disponibilidade,
                        curriculo = :curriculo,
                        apresentacao = :apresentacao,
                        detalhes_experiencia = :detalhes_experiencia,
                        opentowork = :opentowork,
                        opentowork_visibilidade = :opentowork_visibilidade
                    WHERE usuario_id = :usuario_id
                ", [
                    'profissao' => $profissao,
                    'nivel' => $nivel,
                    'resumo' => $resumo,
                    'experiencia' => $experiencia,
                    'formacao' => $formacao,
                    'habilidades' => $habilidades,
                    'areas_interesse' => $areas_interesse,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'telefone' => $telefone,
                    'data_nascimento' => $data_nascimento,
                    'linkedin' => $linkedin,
                    'github' => $github,
                    'portfolio' => $portfolio,
                    'website' => $website,
                    'foto_perfil' => $foto_perfil,
                    'cpf' => $cpf,
                    'genero' => $genero,
                    'endereco' => $endereco,
                    'cep' => $cep,
                    'pais' => $pais,
                    'idiomas' => $idiomas,
                    'pretensao_salarial' => $pretensao_salarial,
                    'disponibilidade' => $disponibilidade,
                    'curriculo' => $curriculo,
                    'apresentacao' => $apresentacao,
                    'detalhes_experiencia' => $detalhes_experiencia,
                    'opentowork' => $opentowork,
                    'opentowork_visibilidade' => $opentowork_visibilidade,
                    'usuario_id' => $usuario_id
                ]);
            } else {
                // Inserir dados do talento
                $db->execute("
                    INSERT INTO talentos (
                        usuario_id, profissao, nivel, resumo, experiencia, formacao, 
                        habilidades, areas_interesse, cidade, estado, telefone, 
                        data_nascimento, linkedin, github, portfolio, website, foto_perfil,
                        cpf, genero, endereco, cep, pais, idiomas, pretensao_salarial,
                        disponibilidade, curriculo, apresentacao, detalhes_experiencia, opentowork, opentowork_visibilidade
                    ) VALUES (
                        :usuario_id, :profissao, :nivel, :resumo, :experiencia, :formacao, 
                        :habilidades, :areas_interesse, :cidade, :estado, :telefone, 
                        :data_nascimento, :linkedin, :github, :portfolio, :website, :foto_perfil,
                        :cpf, :genero, :endereco, :cep, :pais, :idiomas, :pretensao_salarial,
                        :disponibilidade, :curriculo, :apresentacao, :detalhes_experiencia, :opentowork, :opentowork_visibilidade
                    )
                ", [
                    'usuario_id' => $usuario_id,
                    'profissao' => $profissao,
                    'nivel' => $nivel,
                    'resumo' => $resumo,
                    'experiencia' => $experiencia,
                    'formacao' => $formacao,
                    'habilidades' => $habilidades,
                    'areas_interesse' => $areas_interesse,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'telefone' => $telefone,
                    'data_nascimento' => $data_nascimento,
                    'linkedin' => $linkedin,
                    'github' => $github,
                    'portfolio' => $portfolio,
                    'website' => $website,
                    'foto_perfil' => $foto_perfil,
                    'cpf' => $cpf,
                    'genero' => $genero,
                    'endereco' => $endereco,
                    'cep' => $cep,
                    'pais' => $pais,
                    'idiomas' => $idiomas,
                    'pretensao_salarial' => $pretensao_salarial,
                    'disponibilidade' => $disponibilidade,
                    'curriculo' => $curriculo,
                    'apresentacao' => $apresentacao,
                    'detalhes_experiencia' => $detalhes_experiencia,
                    'opentowork' => $opentowork,
                    'opentowork_visibilidade' => $opentowork_visibilidade
                ]);
            }
            
            // Confirmar transação
            $db->commit();
            
            // Atualizar sessão com novo nome
            $_SESSION['user_name'] = $nome;
            
            // Redirecionar com mensagem de sucesso
            $_SESSION['flash_message'] = "Perfil atualizado com sucesso!";
            $_SESSION['flash_type'] = "success";
            echo "<script>window.location.href = '" . SITE_URL . "/?route=perfil_talento';</script>";
            exit;
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            $db->rollBack();
            
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
            $_SESSION['flash_message'] = "Erro ao atualizar perfil: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        // Exibir erros
        $_SESSION['flash_message'] = "Erro ao atualizar perfil: " . implode(" ", $erros);
        $_SESSION['flash_type'] = "danger";
    }
}

// Lista de estados brasileiros
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
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Editar Perfil</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <style>
                            /* Estilos para as abas sempre visíveis */
                            .perfil-abas {
                                display: flex;
                                flex-wrap: wrap;
                                gap: 10px;
                                margin-bottom: 20px;
                            }
                            
                            .perfil-abas .aba-btn {
                                padding: 10px 15px;
                                background-color: #f8f9fa;
                                border: 1px solid #dee2e6;
                                border-radius: 5px;
                                cursor: pointer;
                                flex-grow: 1;
                                text-align: center;
                                font-weight: 500;
                                color: #495057;
                                transition: all 0.2s;
                            }
                            
                            .perfil-abas .aba-btn:hover {
                                background-color: #e9ecef;
                            }
                            
                            .perfil-abas .aba-btn.ativa {
                                background-color: #007bff;
                                color: white;
                                border-color: #007bff;
                            }
                            
                            @media (max-width: 768px) {
                                .perfil-abas {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                }
                            }
                            
                            @media (max-width: 480px) {
                                .perfil-abas {
                                    grid-template-columns: 1fr;
                                }
                            }
                            
                            /* Esconder todas as seções por padrão */
                            .secao-perfil {
                                display: none;
                            }
                            
                            /* Mostrar apenas a seção ativa */
                            .secao-perfil.ativa {
                                display: block;
                            }
                        </style>
                        
                        <!-- Abas de navegação simplificadas para edição de perfil -->
                        <div class="perfil-abas">
                            <button type="button" class="aba-btn" data-secao="pessoal">
                                <i class="fas fa-user me-1"></i> Dados Pessoais
                            </button>
                            <button type="button" class="aba-btn ativa" data-secao="resumo-tab-content">
                                <i class="fas fa-file-alt me-1"></i> Resumo
                            </button>
                            <button type="button" class="aba-btn" data-secao="formacao-tab-content">
                                <i class="fas fa-graduation-cap me-1"></i> Formação
                            </button>
                            <button type="button" class="aba-btn" data-secao="experiencia-tab-content">
                                <i class="fas fa-briefcase me-1"></i> Experiência
                            </button>
                            <button type="button" class="aba-btn" data-secao="habilidades-tab-content">
                                <i class="fas fa-tools me-1"></i> Habilidades
                            </button>
                            <button type="button" class="aba-btn" data-secao="redes">
                                <i class="fas fa-link me-1"></i> Redes
                            </button>
                        </div>
                        
                        <script>
                        // Script para controlar as abas
                        document.addEventListener('DOMContentLoaded', function() {
                            // Selecionar todos os botões de aba
                            const botoes = document.querySelectorAll('.aba-btn');
                            
                            // Adicionar evento de clique a cada botão
                            botoes.forEach(botao => {
                                botao.addEventListener('click', function() {
                                    // Remover classe ativa de todos os botões
                                    botoes.forEach(b => b.classList.remove('ativa'));
                                    
                                    // Adicionar classe ativa ao botão clicado
                                    this.classList.add('ativa');
                                    
                                    // Obter a seção a ser mostrada
                                    const secaoId = this.getAttribute('data-secao');
                                    
                                    // Esconder todas as seções
                                    document.querySelectorAll('.secao-perfil').forEach(secao => {
                                        secao.classList.remove('ativa');
                                    });
                                    
                                    // Mostrar a seção selecionada
                                    document.getElementById(secaoId).classList.add('ativa');
                                    
                                    // Salvar a aba ativa no localStorage
                                    localStorage.setItem('abaAtivaPerfil', secaoId);
                                });
                            });
                            
                            // Verificar se há uma aba salva no localStorage
                            const abaSalva = localStorage.getItem('abaAtivaPerfil');
                            if (abaSalva) {
                                // Encontrar o botão correspondente à aba salva
                                const botaoSalvo = document.querySelector(`.aba-btn[data-secao="${abaSalva}"]`);
                                if (botaoSalvo) {
                                    // Simular um clique no botão
                                    botaoSalvo.click();
                                }
                            }
                        });
                        </script>

                        
                        <!-- Conteúdo das abas -->
                        <div>
                            <!-- Aba de Dados Pessoais -->
                            <div class="secao-perfil" id="pessoal">
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center">
                                            <div class="mb-3">
                                                <?php if (!empty($talento['foto_perfil'])): ?>
                                                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $talento['foto_perfil']; ?>" 
                                                         class="img-fluid rounded-circle" style="width: 180px; height: 180px; object-fit: cover;" 
                                                         alt="Foto de perfil">
                                                <?php else: ?>
                                                    <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center" 
                                                         style="width: 180px; height: 180px;">
                                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">
                                                <label for="foto_perfil" class="form-label">Foto de Perfil</label>
                                                <input class="form-control" type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                                                <div class="form-text">Formatos aceitos: JPEG, PNG, GIF. Tamanho máximo: 2MB.</div>
                                            </div>
                                            
                                            <!-- Upload de Currículo -->
                                            <div class="mb-3">
                                                <label for="curriculo" class="form-label">Currículo (PDF)</label>
                                                <input class="form-control" type="file" id="curriculo" name="curriculo" accept="application/pdf">
                                                <div class="form-text">
                                                    <?php if (!empty($talento['curriculo'])): ?>
                                                        <a href="<?php echo SITE_URL; ?>/uploads/curriculos/<?php echo $talento['curriculo']; ?>" target="_blank">
                                                            <i class="fas fa-file-pdf me-1"></i>Ver currículo atual
                                                        </a>
                                                    <?php else: ?>
                                                        Nenhum currículo enviado
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nome" class="form-label">Nome Completo *</label>
                                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($talento['nome']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">E-mail *</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($talento['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="genero" class="form-label">Gênero</label>
                                                <select class="form-select" id="genero" name="genero">
                                                    <option value="">Selecione</option>
                                                    <option value="Masculino" <?php echo (isset($talento['genero']) && $talento['genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                                    <option value="Feminino" <?php echo (isset($talento['genero']) && $talento['genero'] == 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                                                    <option value="Não-binário" <?php echo (isset($talento['genero']) && $talento['genero'] == 'Não-binário') ? 'selected' : ''; ?>>Não-binário</option>
                                                    <option value="Prefiro não informar" <?php echo (isset($talento['genero']) && $talento['genero'] == 'Prefiro não informar') ? 'selected' : ''; ?>>Prefiro não informar</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <!-- Campo vazio para manter o layout -->
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="telefone" class="form-label">Telefone</label>
                                                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($talento['telefone'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($talento['data_nascimento'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="cidade" class="form-label">Cidade</label>
                                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($talento['cidade'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="estado" class="form-label">Estado</label>
                                                <select class="form-select" id="estado" name="estado">
                                                    <option value="">Selecione um estado</option>
                                                    <?php foreach ($estados as $sigla => $nome): ?>
                                                        <option value="<?php echo $sigla; ?>" <?php echo (isset($talento['estado']) && $talento['estado'] == $sigla) ? 'selected' : ''; ?>>
                                                            <?php echo $nome; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba de Resumo -->
                            <div class="secao-perfil" id="resumo-tab-content">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="profissao" class="form-label">Profissão/Cargo *</label>
                                        <input type="text" class="form-control" id="profissao" name="profissao" value="<?php echo htmlspecialchars($talento['profissao'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="nivel" class="form-label">Nível Profissional</label>
                                        <select class="form-select" id="nivel" name="nivel">
                                            <option value="">Selecione um nível</option>
                                            <option value="Estágio" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Estágio') ? 'selected' : ''; ?>>Estágio</option>
                                            <option value="Trainee" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Trainee') ? 'selected' : ''; ?>>Trainee</option>
                                            <option value="Júnior" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Júnior') ? 'selected' : ''; ?>>Júnior</option>
                                            <option value="Pleno" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Pleno') ? 'selected' : ''; ?>>Pleno</option>
                                            <option value="Sênior" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Sênior') ? 'selected' : ''; ?>>Sênior</option>
                                            <option value="Especialista" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Especialista') ? 'selected' : ''; ?>>Especialista</option>
                                            <option value="Coordenador" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Coordenador') ? 'selected' : ''; ?>>Coordenador</option>
                                            <option value="Supervisor" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                            <option value="Gerente" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Gerente') ? 'selected' : ''; ?>>Gerente</option>
                                            <option value="Diretor" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'Diretor') ? 'selected' : ''; ?>>Diretor</option>
                                            <option value="CEO" <?php echo (isset($talento['nivel']) && $talento['nivel'] == 'CEO') ? 'selected' : ''; ?>>CEO</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="apresentacao" class="form-label">Carta de Apresentação</label>
                                    <textarea class="form-control" id="apresentacao" name="apresentacao" rows="4" placeholder="Escreva um breve resumo sobre você, suas motivações e o que busca profissionalmente"><?php echo htmlspecialchars($talento['apresentacao'] ?? ''); ?></textarea>
                                    <small class="text-muted">Esta carta será exibida no seu perfil público e ajudará os recrutadores a conhecê-lo melhor.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="resumo" class="form-label">Resumo Profissional</label>
                                    <textarea class="form-control" id="resumo" name="resumo" rows="6"><?php echo htmlspecialchars($talento['resumo'] ?? ''); ?></textarea>
                                    <div class="form-text">Descreva brevemente seu perfil profissional, principais competências e objetivos de carreira.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="pretensao_salarial" class="form-label">Pretensão Salarial (R$)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="pretensao_salarial" name="pretensao_salarial" value="<?php echo htmlspecialchars($talento['pretensao_salarial'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="disponibilidade" class="form-label">Disponibilidade</label>
                                        <select class="form-select" id="disponibilidade" name="disponibilidade">
                                            <option value="">Selecione</option>
                                            <option value="Imediata" <?php echo (isset($talento['disponibilidade']) && $talento['disponibilidade'] == 'Imediata') ? 'selected' : ''; ?>>Imediata</option>
                                            <option value="15 dias" <?php echo (isset($talento['disponibilidade']) && $talento['disponibilidade'] == '15 dias') ? 'selected' : ''; ?>>15 dias</option>
                                            <option value="30 dias" <?php echo (isset($talento['disponibilidade']) && $talento['disponibilidade'] == '30 dias') ? 'selected' : ''; ?>>30 dias</option>
                                            <option value="Mais de 30 dias" <?php echo (isset($talento['disponibilidade']) && $talento['disponibilidade'] == 'Mais de 30 dias') ? 'selected' : ''; ?>>Mais de 30 dias</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="opentowork" name="opentowork" value="1" <?php echo (isset($talento['opentowork']) && $talento['opentowork'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="opentowork">Estou disponível para novas oportunidades</label>
                                    </div>
                                </div>
                                <div class="mb-3" id="opentowork_visibilidade_container" <?php echo (isset($talento['opentowork']) && $talento['opentowork'] == 1) ? '' : 'style="display:none;"'; ?>>
                                    <label class="form-label">Visibilidade da disponibilidade</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="opentowork_visibilidade" id="visibilidade_publica" value="publico" <?php echo (isset($talento['opentowork_visibilidade']) && $talento['opentowork_visibilidade'] == 'publico') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="visibilidade_publica">
                                            Pública (visível para todas as empresas)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="opentowork_visibilidade" id="visibilidade_privada" value="privado" <?php echo (isset($talento['opentowork_visibilidade']) && $talento['opentowork_visibilidade'] == 'privado') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="visibilidade_privada">
                                            Privada (visível apenas para empresas selecionadas)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba de Formação -->
                            <div class="secao-perfil" id="formacao-tab-content">
                                <div class="mb-3">
                                    <label for="formacao" class="form-label">Formação Acadêmica</label>
                                    <textarea class="form-control" id="formacao" name="formacao" rows="10"><?php echo htmlspecialchars($talento['formacao'] ?? ''); ?></textarea>
                                    <div class="form-text">Descreva sua formação acadêmica, incluindo instituição, curso, período e outras informações relevantes.</div>
                                </div>
                            </div>
                            
                            <!-- Aba de Experiência -->
                            <div class="secao-perfil" id="experiencia-tab-content">
                                <div class="mb-3">
                                    <label for="experiencia" class="form-label">Experiência Profissional (Em anos)</label>
                                    <select class="form-select" id="experiencia" name="experiencia">
                                        <option value="">Selecione</option>
                                        <option value="0" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '0') ? 'selected' : ''; ?>>Menos de 1 ano</option>
                                        <option value="1" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '1') ? 'selected' : ''; ?>>1 ano</option>
                                        <option value="2" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '2') ? 'selected' : ''; ?>>2 anos</option>
                                        <option value="3" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '3') ? 'selected' : ''; ?>>3 anos</option>
                                        <option value="4" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '4') ? 'selected' : ''; ?>>4 anos</option>
                                        <option value="5" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '5') ? 'selected' : ''; ?>>5 anos</option>
                                        <option value="6" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '6') ? 'selected' : ''; ?>>6 anos</option>
                                        <option value="7" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '7') ? 'selected' : ''; ?>>7 anos</option>
                                        <option value="8" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '8') ? 'selected' : ''; ?>>8 anos</option>
                                        <option value="9" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '9') ? 'selected' : ''; ?>>9 anos</option>
                                        <option value="10" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '10') ? 'selected' : ''; ?>>10 anos</option>
                                        <option value="15" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '15') ? 'selected' : ''; ?>>Mais de 10 anos</option>
                                        <option value="20" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '20') ? 'selected' : ''; ?>>Mais de 15 anos</option>
                                        <option value="25" <?php echo (isset($talento['experiencia']) && $talento['experiencia'] == '25') ? 'selected' : ''; ?>>Mais de 20 anos</option>
                                    </select>
                                    <div class="form-text">Selecione o tempo total de experiência profissional que você possui.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="detalhes_experiencia" class="form-label">Detalhes da Experiência Profissional</label>
                                    <textarea class="form-control" id="detalhes_experiencia" name="detalhes_experiencia" rows="10"><?php echo htmlspecialchars($talento['detalhes_experiencia'] ?? ''); ?></textarea>
                                    <div class="form-text">Descreva suas experiências profissionais, incluindo empresa, cargo, período e responsabilidades.</div>
                                </div>
                            </div>
                            
                            <!-- Aba de Habilidades -->
                            <div class="secao-perfil" id="habilidades-tab-content">
                                <div class="mb-3">
                                    <label for="habilidades" class="form-label">Habilidades Técnicas</label>
                                    <input type="text" class="form-control" id="habilidades" name="habilidades" value="<?php echo htmlspecialchars($talento['habilidades'] ?? ''); ?>">
                                    <div class="form-text">Separe as habilidades por vírgula (ex: PHP, JavaScript, MySQL, React, Node.js)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="areas_interesse" class="form-label">Áreas de Interesse</label>
                                    <input type="text" class="form-control" id="areas_interesse" name="areas_interesse" value="<?php echo htmlspecialchars($talento['areas_interesse'] ?? ''); ?>">
                                    <div class="form-text">Separe as áreas por vírgula (ex: Desenvolvimento Web, Mobile, IA, Cloud Computing)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="idiomas" class="form-label">Idiomas</label>
                                    <input type="text" class="form-control" id="idiomas" name="idiomas" value="<?php echo htmlspecialchars($talento['idiomas'] ?? ''); ?>">
                                    <div class="form-text">Exemplo: Inglês (Fluente), Espanhol (Intermediário), Francês (Básico)</div>
                                </div>
                            </div>
                            
                            <!-- Aba de Redes Sociais -->
                            <div class="secao-perfil" id="redes">
                                <div class="mb-3">
                                    <label for="linkedin" class="form-label">LinkedIn</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($talento['linkedin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="github" class="form-label">GitHub</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-github"></i></span>
                                        <input type="url" class="form-control" id="github" name="github" value="<?php echo htmlspecialchars($talento['github'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="portfolio" class="form-label">Portfólio</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                        <input type="url" class="form-control" id="portfolio" name="portfolio" value="<?php echo htmlspecialchars($talento['portfolio'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website Pessoal</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                        <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($talento['website'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/?route=perfil_talento" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar ao Perfil
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para controlar o comportamento do formulário
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar opções de visibilidade do OpenToWork
    const openToWorkCheckbox = document.getElementById('opentowork');
    const visibilidadeContainer = document.getElementById('opentowork_visibilidade_container');
    
    if (openToWorkCheckbox && visibilidadeContainer) {
        // Definir estado inicial
        if (openToWorkCheckbox.checked) {
            visibilidadeContainer.style.display = 'block';
        } else {
            visibilidadeContainer.style.display = 'none';
        }
        
        // Adicionar evento de mudança
        openToWorkCheckbox.addEventListener('change', function() {
            if (this.checked) {
                visibilidadeContainer.style.display = 'block';
            } else {
                visibilidadeContainer.style.display = 'none';
            }
        });
    }
});
</script>
