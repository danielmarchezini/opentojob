<?php
// Verificar se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);

// Obter instância do banco de dados
$db = Database::getInstance();

// Parâmetros de busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$nivel = isset($_GET['nivel']) ? trim($_GET['nivel']) : '';
$habilidade = isset($_GET['habilidade']) ? trim($_GET['habilidade']) : '';
$localizacao = isset($_GET['localizacao']) ? trim($_GET['localizacao']) : '';

// Construir consulta SQL
$sql = "
    SELECT u.id, u.nome, u.foto_perfil, u.data_cadastro, 
           t.profissao, t.nivel, t.carta_apresentacao, t.habilidades
    FROM usuarios u
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.tipo = 'talento' AND u.status = 'ativo'
";

$params = [];

// Adicionar filtros à consulta
if (!empty($busca)) {
    $sql .= " AND (u.nome LIKE :busca OR t.profissao LIKE :busca OR t.carta_apresentacao LIKE :busca)";
    $params['busca'] = "%{$busca}%";
}

if (!empty($nivel)) {
    $sql .= " AND t.nivel = :nivel";
    $params['nivel'] = $nivel;
}

if (!empty($habilidade)) {
    $sql .= " AND t.habilidades LIKE :habilidade";
    $params['habilidade'] = "%{$habilidade}%";
}

if (!empty($localizacao)) {
    $sql .= " AND (u.cidade LIKE :localizacao OR u.estado LIKE :localizacao)";
    $params['localizacao'] = "%{$localizacao}%";
}

// Ordenar por data de cadastro (mais recentes primeiro)
$sql .= " ORDER BY u.data_cadastro DESC";

// Executar consulta
$talentos = $db->fetchAll($sql, $params);

// Obter níveis disponíveis para o filtro
$niveis_disponiveis = [
    'Estágio' => 'Estágio',
    'Júnior' => 'Júnior',
    'Pleno' => 'Pleno',
    'Sênior' => 'Sênior',
    'Especialista' => 'Especialista',
    'Gerente' => 'Gerente',
    'Diretor' => 'Diretor'
];

// Obter habilidades mais comuns para o filtro
try {
    $habilidades_populares = $db->fetchAll("
        SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(t.habilidades, ',', n.n), ',', -1) as habilidade
        FROM talentos t
        CROSS JOIN (
            SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        ) n
        WHERE LENGTH(t.habilidades) - LENGTH(REPLACE(t.habilidades, ',', '')) >= n.n - 1
        GROUP BY habilidade
        ORDER BY COUNT(*) DESC
        LIMIT 20
    ");
    
    $habilidades_lista = [];
    foreach ($habilidades_populares as $h) {
        $habilidade_trim = trim($h['habilidade']);
        if (!empty($habilidade_trim)) {
            $habilidades_lista[$habilidade_trim] = $habilidade_trim;
        }
    }
} catch (Exception $e) {
    // Em caso de erro, usar lista padrão
    $habilidades_lista = [
        'PHP' => 'PHP',
        'JavaScript' => 'JavaScript',
        'HTML/CSS' => 'HTML/CSS',
        'Python' => 'Python',
        'Java' => 'Java',
        'Marketing Digital' => 'Marketing Digital',
        'Design Gráfico' => 'Design Gráfico',
        'Gestão de Projetos' => 'Gestão de Projetos',
        'Vendas' => 'Vendas',
        'Atendimento ao Cliente' => 'Atendimento ao Cliente'
    ];
}

// Obter localizações mais comuns para o filtro
try {
    $localizacoes = $db->fetchAll("
        SELECT DISTINCT cidade, estado,
               CONCAT(cidade, ', ', estado) as local_completo
        FROM usuarios
        WHERE tipo = 'talento' AND status = 'ativo'
              AND cidade IS NOT NULL AND cidade != ''
              AND estado IS NOT NULL AND estado != ''
        ORDER BY estado, cidade
        LIMIT 30
    ");
    
    $localizacoes_lista = [];
    foreach ($localizacoes as $loc) {
        $localizacoes_lista[$loc['local_completo']] = $loc['local_completo'];
    }
} catch (Exception $e) {
    // Em caso de erro, usar lista vazia
    $localizacoes_lista = [];
}
?>

<div class="container my-5">
    <div class="row">
        <!-- Filtros de busca -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo SITE_URL; ?>/?route=buscar_talentos" method="GET">
                        <input type="hidden" name="route" value="buscar_talentos">
                        
                        <div class="mb-3">
                            <label for="busca" class="form-label">Busca por palavra-chave</label>
                            <input type="text" class="form-control" id="busca" name="busca" 
                                   value="<?php echo htmlspecialchars($busca); ?>" 
                                   placeholder="Nome, profissão, habilidades...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nivel" class="form-label">Nível Profissional</label>
                            <select class="form-select" id="nivel" name="nivel">
                                <option value="">Todos os níveis</option>
                                <?php foreach ($niveis_disponiveis as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $nivel === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="habilidade" class="form-label">Habilidade</label>
                            <select class="form-select" id="habilidade" name="habilidade">
                                <option value="">Todas as habilidades</option>
                                <?php foreach ($habilidades_lista as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $habilidade === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="localizacao" class="form-label">Localização</label>
                            <select class="form-select" id="localizacao" name="localizacao">
                                <option value="">Todas as localizações</option>
                                <?php foreach ($localizacoes_lista as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $localizacao === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Buscar Talentos</button>
                        
                        <?php if (!empty($busca) || !empty($nivel) || !empty($habilidade) || !empty($localizacao)): ?>
                        <a href="<?php echo SITE_URL; ?>/?route=buscar_talentos" class="btn btn-outline-secondary w-100 mt-2">
                            Limpar Filtros
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-info text-dark">
                    <h5 class="mb-0">Dicas de Busca</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Use filtros combinados para resultados mais precisos</li>
                        <li>Busque por nível profissional para encontrar talentos com a experiência adequada</li>
                        <li>Todos os talentos estão disponíveis para iniciar imediatamente</li>
                        <li>Apenas empresas cadastradas podem entrar em contato com talentos</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Resultados da busca -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Buscar Talentos</h1>
                <div>
                    <span class="badge bg-secondary"><?php echo count($talentos); ?> talentos encontrados</span>
                </div>
            </div>
            
            <?php if (!$is_logged_in): ?>
            <div class="alert alert-info mb-4">
                <h5>Acesso limitado</h5>
                <p>Para ver informações completas dos talentos e entrar em contato, <a href="<?php echo SITE_URL; ?>/?route=entrar">faça login</a> ou <a href="<?php echo SITE_URL; ?>/?route=cadastro_empresa">cadastre sua empresa</a>.</p>
            </div>
            <?php endif; ?>
            
            <?php if (empty($talentos)): ?>
            <div class="alert alert-warning">
                <h5>Nenhum talento encontrado</h5>
                <p>Tente ajustar seus filtros de busca para encontrar mais resultados.</p>
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($talentos as $talento): ?>
                <?php 
                // Processar habilidades
                $habilidades_array = [];
                if (!empty($talento['habilidades'])) {
                    $habilidades_array = explode(',', $talento['habilidades']);
                }
                
                // Limitar a carta de apresentação
                $carta_apresentacao = '';
                if (!empty($talento['carta_apresentacao'])) {
                    $carta_apresentacao = $talento['carta_apresentacao'];
                    if (strlen($carta_apresentacao) > 150) {
                        $carta_apresentacao = substr($carta_apresentacao, 0, 150) . '...';
                    }
                }
                
                // Definir foto de perfil
                $foto_perfil = !empty($talento['foto_perfil']) 
                    ? SITE_URL . '/' . $talento['foto_perfil'] 
                    : SITE_URL . '/assets/img/default-profile.jpg';
                
                // Formatar data de cadastro
                $data_cadastro = new DateTime($talento['data_cadastro']);
                $data_formatada = $data_cadastro->format('d/m/Y');
                ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="talent-photo">
                                    <img src="<?php echo $foto_perfil; ?>" class="img-fluid rounded-start" alt="Foto de <?php echo htmlspecialchars($talento['nome']); ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($talento['nome']); ?></h5>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="profession text-primary"><?php echo htmlspecialchars($talento['profissao'] ?? 'Profissional'); ?></span>
                                        <?php if (!empty($talento['nivel'])): ?>
                                        <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($talento['nivel']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($carta_apresentacao)): ?>
                                    <p class="card-text small"><?php echo nl2br(htmlspecialchars($carta_apresentacao)); ?></p>
                                    <?php else: ?>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars($talento['nome']); ?> é um(a) 
                                        <?php echo htmlspecialchars($talento['nivel'] ?? 'profissional'); ?> 
                                        na área de <?php echo htmlspecialchars($talento['profissao'] ?? 'tecnologia'); ?>.
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($habilidades_array)): ?>
                                    <div class="skills my-2">
                                        <?php 
                                        // Mostrar apenas as 3 primeiras habilidades
                                        $count = 0;
                                        foreach ($habilidades_array as $hab): 
                                            if ($count >= 3) break;
                                            $count++;
                                        ?>
                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($hab)); ?></span>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($habilidades_array) > 3): ?>
                                        <span class="badge bg-light text-dark">+<?php echo count($habilidades_array) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">Cadastrado em <?php echo $data_formatada; ?></small>
                                        <a href="<?php echo SITE_URL; ?>/?route=perfil_talento&id=<?php echo $talento['id']; ?>" class="btn btn-sm btn-outline-primary">Ver Perfil</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.talent-photo {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #f8f9fa;
    border-radius: 0.375rem 0 0 0.375rem;
}

.talent-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.profession {
    font-weight: 500;
    color: var(--primary-color);
}

@media (max-width: 767.98px) {
    .talent-photo {
        height: 200px;
        border-radius: 0.375rem 0.375rem 0 0;
    }
}
</style>
