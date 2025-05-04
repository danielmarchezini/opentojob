<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa') && !Auth::checkUserType('admin')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado (empresa)
$empresa_id = $_SESSION['user_id'];

// Instância do banco de dados
$db = Database::getInstance();

// Parâmetros de busca
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$area = isset($_GET['area']) ? trim($_GET['area']) : '';
$nivel = isset($_GET['nivel']) ? trim($_GET['nivel']) : '';
$cidade = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$disponibilidade = isset($_GET['disponibilidade']) ? trim($_GET['disponibilidade']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Buscar áreas de interesse para o filtro
try {
    $areas_interesse = $db->fetchAll("
        SELECT DISTINCT areas_interesse 
        FROM talentos 
        WHERE areas_interesse IS NOT NULL AND areas_interesse != ''
    ");
    
    $areas_disponiveis = [];
    foreach ($areas_interesse as $area_item) {
        if (!empty($area_item['areas_interesse'])) {
            $areas_array = explode(',', $area_item['areas_interesse']);
            foreach ($areas_array as $area_individual) {
                $area_individual = trim($area_individual);
                if (!empty($area_individual) && !in_array($area_individual, $areas_disponiveis)) {
                    $areas_disponiveis[] = $area_individual;
                }
            }
        }
    }
    sort($areas_disponiveis);
} catch (PDOException $e) {
    error_log("Erro ao buscar áreas de interesse: " . $e->getMessage());
    $areas_disponiveis = [];
}

// Buscar cidades para o filtro
try {
    $cidades = $db->fetchAll("
        SELECT DISTINCT cidade 
        FROM talentos 
        WHERE cidade IS NOT NULL AND cidade != ''
        ORDER BY cidade ASC
    ");
} catch (PDOException $e) {
    error_log("Erro ao buscar cidades: " . $e->getMessage());
    $cidades = [];
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

// Construir a query de busca
$query = "
    SELECT t.*, u.nome, u.email, u.data_cadastro, u.ultimo_acesso
    FROM talentos t
    JOIN usuarios u ON t.usuario_id = u.id
    WHERE u.tipo = 'talento' AND u.status = 'ativo'
";

$params = [];

// Adicionar filtros à query
if (!empty($termo)) {
    $query .= " AND (
        u.nome LIKE :termo OR 
        t.profissao LIKE :termo OR 
        t.resumo LIKE :termo OR 
        t.experiencia LIKE :termo OR 
        t.formacao LIKE :termo OR 
        t.habilidades LIKE :termo OR
        t.areas_interesse LIKE :termo
    )";
    $params['termo'] = '%' . $termo . '%';
}

if (!empty($area)) {
    $query .= " AND t.areas_interesse LIKE :area";
    $params['area'] = '%' . $area . '%';
}

if (!empty($nivel)) {
    $query .= " AND t.nivel_experiencia = :nivel";
    $params['nivel'] = $nivel;
}

if (!empty($cidade)) {
    $query .= " AND t.cidade = :cidade";
    $params['cidade'] = $cidade;
}

if (!empty($estado)) {
    $query .= " AND t.estado = :estado";
    $params['estado'] = $estado;
}

if (!empty($disponibilidade)) {
    $query .= " AND t.disponibilidade = :disponibilidade";
    $params['disponibilidade'] = $disponibilidade;
}

// Contar total de resultados
try {
    $count_query = str_replace("SELECT t.*, u.nome, u.email, u.data_cadastro, u.ultimo_acesso", "SELECT COUNT(*) as total", $query);
    $total_results = $db->fetch($count_query, $params);
    $total_talentos = $total_results['total'] ?? 0;
    $total_paginas = ceil($total_talentos / $por_pagina);
} catch (PDOException $e) {
    error_log("Erro ao contar talentos: " . $e->getMessage());
    $total_talentos = 0;
    $total_paginas = 0;
}

// Adicionar paginação à query
$query .= " ORDER BY u.ultimo_acesso DESC LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $por_pagina;

// Buscar talentos
try {
    $talentos = $db->fetchAll($query, $params);
} catch (PDOException $e) {
    error_log("Erro ao buscar talentos: " . $e->getMessage());
    $talentos = [];
}

// Verificar se a empresa tem plano que permite visualizar contatos
$pode_ver_contatos = true; // Por padrão, todas as empresas podem ver contatos

// Verificar se existe tabela de planos e assinaturas
try {
    $planos_existe = $db->fetch("SHOW TABLES LIKE 'planos'");
    $assinaturas_existe = $db->fetch("SHOW TABLES LIKE 'assinaturas'");
    
    if ($planos_existe && $assinaturas_existe) {
        // Verificar assinatura da empresa
        $assinatura = $db->fetch("
            SELECT p.permite_ver_contatos
            FROM assinaturas a
            JOIN planos p ON a.plano_id = p.id
            WHERE a.empresa_id = :empresa_id AND a.status = 'ativa' AND a.data_expiracao > NOW()
            ORDER BY a.data_expiracao DESC
            LIMIT 1
        ", ['empresa_id' => $empresa_id]);
        
        if ($assinatura) {
            $pode_ver_contatos = (bool)$assinatura['permite_ver_contatos'];
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar plano da empresa: " . $e->getMessage());
}

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return 'N/A';
    return date('d/m/Y', strtotime($data));
}

// Função para formatar último acesso
function formatarUltimoAcesso($data) {
    if (empty($data)) return 'Nunca acessou';
    
    $ultimo_acesso = strtotime($data);
    $agora = time();
    $diferenca = $agora - $ultimo_acesso;
    
    if ($diferenca < 60) {
        return 'Agora mesmo';
    } elseif ($diferenca < 3600) {
        $minutos = floor($diferenca / 60);
        return "Há {$minutos} " . ($minutos == 1 ? 'minuto' : 'minutos');
    } elseif ($diferenca < 86400) {
        $horas = floor($diferenca / 3600);
        return "Há {$horas} " . ($horas == 1 ? 'hora' : 'horas');
    } elseif ($diferenca < 604800) {
        $dias = floor($diferenca / 86400);
        return "Há {$dias} " . ($dias == 1 ? 'dia' : 'dias');
    } else {
        return date('d/m/Y', $ultimo_acesso);
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Buscar Talentos</h1>
        <a href="<?php echo SITE_URL; ?>/?route=empresa/painel" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar ao Painel
        </a>
    </div>
    
    <!-- Filtros de Busca -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Filtros de Busca</h5>
            <form method="get" action="<?php echo SITE_URL; ?>/" class="row g-3">
                <input type="hidden" name="route" value="buscar_talentos">
                
                <div class="col-md-4">
                    <label for="termo" class="form-label">Termo de Busca</label>
                    <input type="text" class="form-control" id="termo" name="termo" value="<?php echo htmlspecialchars($termo); ?>" placeholder="Nome, habilidades, experiência...">
                </div>
                
                <div class="col-md-4">
                    <label for="area" class="form-label">Área de Interesse</label>
                    <select class="form-select" id="area" name="area">
                        <option value="">Todas as áreas</option>
                        <?php foreach ($areas_disponiveis as $area_disponivel): ?>
                        <option value="<?php echo htmlspecialchars($area_disponivel); ?>" <?php echo $area === $area_disponivel ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area_disponivel); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="nivel" class="form-label">Nível de Experiência</label>
                    <select class="form-select" id="nivel" name="nivel">
                        <option value="">Todos os níveis</option>
                        <option value="estagiario" <?php echo $nivel === 'estagiario' ? 'selected' : ''; ?>>Estagiário</option>
                        <option value="junior" <?php echo $nivel === 'junior' ? 'selected' : ''; ?>>Júnior</option>
                        <option value="pleno" <?php echo $nivel === 'pleno' ? 'selected' : ''; ?>>Pleno</option>
                        <option value="senior" <?php echo $nivel === 'senior' ? 'selected' : ''; ?>>Sênior</option>
                        <option value="especialista" <?php echo $nivel === 'especialista' ? 'selected' : ''; ?>>Especialista</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="cidade" class="form-label">Cidade</label>
                    <select class="form-select" id="cidade" name="cidade">
                        <option value="">Todas as cidades</option>
                        <?php foreach ($cidades as $cidade_item): ?>
                        <option value="<?php echo htmlspecialchars($cidade_item['cidade']); ?>" <?php echo $cidade === $cidade_item['cidade'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cidade_item['cidade']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos os estados</option>
                        <?php foreach ($estados as $sigla => $nome): ?>
                        <option value="<?php echo $sigla; ?>" <?php echo $estado === $sigla ? 'selected' : ''; ?>>
                            <?php echo $nome; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="disponibilidade" class="form-label">Disponibilidade</label>
                    <select class="form-select" id="disponibilidade" name="disponibilidade">
                        <option value="">Todas</option>
                        <option value="imediata" <?php echo $disponibilidade === 'imediata' ? 'selected' : ''; ?>>Imediata</option>
                        <option value="15_dias" <?php echo $disponibilidade === '15_dias' ? 'selected' : ''; ?>>15 dias</option>
                        <option value="30_dias" <?php echo $disponibilidade === '30_dias' ? 'selected' : ''; ?>>30 dias</option>
                        <option value="combinada" <?php echo $disponibilidade === 'combinada' ? 'selected' : ''; ?>>A combinar</option>
                    </select>
                </div>
                
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i> Buscar
                    </button>
                    <a href="<?php echo SITE_URL; ?>/?route=buscar_talentos" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resultados da Busca -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Resultados da Busca</h5>
                <span class="text-muted"><?php echo $total_talentos; ?> talentos encontrados</span>
            </div>
            
            <?php if (empty($talentos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Nenhum talento encontrado com os filtros selecionados.
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($talentos as $talento): ?>
                <?php 
                    $foto = !empty($talento['foto']) 
                        ? SITE_URL . '/uploads/perfil/' . $talento['foto'] 
                        : SITE_URL . '/assets/img/placeholder-user.png';
                    
                    // Verificar se tem áreas de interesse
                    $areas = [];
                    if (!empty($talento['areas_interesse'])) {
                        $areas = explode(',', $talento['areas_interesse']);
                        $areas = array_map('trim', $areas);
                        $areas = array_filter($areas);
                    }
                ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $foto; ?>" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;" alt="<?php echo htmlspecialchars($talento['nome']); ?>">
                                <div>
                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                                    <p class="card-subtitle text-muted mb-0"><?php echo htmlspecialchars($talento['profissao'] ?: 'Profissão não informada'); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1"><i class="fas fa-map-marker-alt me-2 text-secondary"></i><?php echo htmlspecialchars(($talento['cidade'] ?: 'Cidade não informada') . ($talento['estado'] ? ', ' . $talento['estado'] : '')); ?></p>
                                <?php if ($pode_ver_contatos): ?>
                                <p class="mb-1"><i class="fas fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($talento['email']); ?></p>
                                <?php if (!empty($talento['telefone'])): ?>
                                <p class="mb-1"><i class="fas fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($talento['telefone']); ?></p>
                                <?php endif; ?>
                                <?php endif; ?>
                                <p class="mb-1"><i class="fas fa-clock me-2 text-secondary"></i>Último acesso: <?php echo formatarUltimoAcesso($talento['ultimo_acesso']); ?></p>
                            </div>
                            
                            <?php if (!empty($talento['resumo'])): ?>
                            <div class="mb-3">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($talento['resumo'], 0, 150) . (strlen($talento['resumo']) > 150 ? '...' : ''))); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($areas)): ?>
                            <div class="mb-3">
                                <?php foreach (array_slice($areas, 0, 3) as $area): ?>
                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($area); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($areas) > 3): ?>
                                <span class="badge bg-light text-dark">+<?php echo count($areas) - 3; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <a href="<?php echo SITE_URL; ?>/?route=talento/perfil&id=<?php echo $talento['usuario_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-user me-1"></i> Ver Perfil
                                </a>
                                <?php if ($pode_ver_contatos): ?>
                                <a href="<?php echo SITE_URL; ?>/?route=mensagens&para=<?php echo $talento['usuario_id']; ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-envelope me-1"></i> Mensagem
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPlano">
                                    <i class="fas fa-lock me-1"></i> Contato Bloqueado
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação de página" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=buscar_talentos&pagina=<?php echo $pagina - 1; ?>&termo=<?php echo urlencode($termo); ?>&area=<?php echo urlencode($area); ?>&nivel=<?php echo urlencode($nivel); ?>&cidade=<?php echo urlencode($cidade); ?>&estado=<?php echo urlencode($estado); ?>&disponibilidade=<?php echo urlencode($disponibilidade); ?>">Anterior</a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $pagina - 2);
                    $end_page = min($total_paginas, $pagina + 2);
                    
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=buscar_talentos&pagina=1&termo=' . urlencode($termo) . '&area=' . urlencode($area) . '&nivel=' . urlencode($nivel) . '&cidade=' . urlencode($cidade) . '&estado=' . urlencode($estado) . '&disponibilidade=' . urlencode($disponibilidade) . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        echo '<li class="page-item ' . ($pagina == $i ? 'active' : '') . '"><a class="page-link" href="' . SITE_URL . '/?route=buscar_talentos&pagina=' . $i . '&termo=' . urlencode($termo) . '&area=' . urlencode($area) . '&nivel=' . urlencode($nivel) . '&cidade=' . urlencode($cidade) . '&estado=' . urlencode($estado) . '&disponibilidade=' . urlencode($disponibilidade) . '">' . $i . '</a></li>';
                    }
                    
                    if ($end_page < $total_paginas) {
                        if ($end_page < $total_paginas - 1) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . SITE_URL . '/?route=buscar_talentos&pagina=' . $total_paginas . '&termo=' . urlencode($termo) . '&area=' . urlencode($area) . '&nivel=' . urlencode($nivel) . '&cidade=' . urlencode($cidade) . '&estado=' . urlencode($estado) . '&disponibilidade=' . urlencode($disponibilidade) . '">' . $total_paginas . '</a></li>';
                    }
                    ?>
                    
                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo SITE_URL; ?>/?route=buscar_talentos&pagina=<?php echo $pagina + 1; ?>&termo=<?php echo urlencode($termo); ?>&area=<?php echo urlencode($area); ?>&nivel=<?php echo urlencode($nivel); ?>&cidade=<?php echo urlencode($cidade); ?>&estado=<?php echo urlencode($estado); ?>&disponibilidade=<?php echo urlencode($disponibilidade); ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Plano -->
<?php if (!$pode_ver_contatos): ?>
<div class="modal fade" id="modalPlano" tabindex="-1" aria-labelledby="modalPlanoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPlanoLabel">Acesso Restrito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                    <h5>Contatos Bloqueados</h5>
                    <p>Seu plano atual não permite visualizar informações de contato dos talentos.</p>
                </div>
                <div class="alert alert-info">
                    <p class="mb-0">Para visualizar informações de contato e enviar mensagens diretamente aos talentos, faça upgrade para um plano premium.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="<?php echo SITE_URL; ?>/?route=planos" class="btn btn-primary">Ver Planos</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
