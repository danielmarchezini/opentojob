<?php
// Verificar se o usuário está logado e é uma empresa
if (!Auth::checkUserType('empresa')) {
    header('Location: ' . SITE_URL . '/?route=entrar');
    exit;
}

// Obter ID do usuário logado
$usuario_id = $_SESSION['user_id'];

// Incluir classe de estatísticas
require_once 'includes/Estatisticas.php';

// Definir período de análise
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';

// Obter estatísticas do usuário
$estatisticas = Estatisticas::obterEstatisticasUsuario($usuario_id, 'ambos', $periodo);

// Obter últimas interações
$ultimas_interacoes = Estatisticas::obterUltimasInteracoes($usuario_id, 'ambos', 10);

// Obter instância do banco de dados
$db = Database::getInstance();

// Obter detalhes da empresa
$empresa = $db->fetchRow("
    SELECT u.nome, u.email, e.*
    FROM usuarios u
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.id = :id
", [
    'id' => $usuario_id
]);

// Definir título da página
$page_title = "Estatísticas de Interações";
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-0">Estatísticas de Interações</h1>
            <p class="text-muted">Acompanhe suas interações com talentos na plataforma</p>
        </div>
        <div class="col-md-4 text-md-end">
            <form action="<?php echo SITE_URL; ?>/?route=estatisticas_empresa" method="GET" class="d-inline-block">
                <input type="hidden" name="route" value="estatisticas_empresa">
                <div class="input-group">
                    <select name="periodo" class="form-select" onchange="this.form.submit()">
                        <option value="semana" <?php echo ($periodo === 'semana') ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="mes" <?php echo ($periodo === 'mes') ? 'selected' : ''; ?>>Últimos 30 dias</option>
                        <option value="ano" <?php echo ($periodo === 'ano') ? 'selected' : ''; ?>>Último ano</option>
                        <option value="todos" <?php echo ($periodo === 'todos') ? 'selected' : ''; ?>>Todo o período</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-primary mb-2">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h5 class="card-title">Visualizações</h5>
                    <?php
                    $total_visualizacoes = 0;
                    foreach ($estatisticas['origem'] as $interacao) {
                        if ($interacao['tipo_interacao'] === 'visualizacao_perfil') {
                            $total_visualizacoes = $interacao['total'];
                            break;
                        }
                    }
                    ?>
                    <h2 class="display-5 fw-bold"><?php echo $total_visualizacoes; ?></h2>
                    <p class="text-muted small">Perfis de talentos visualizados</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-success mb-2">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5 class="card-title">Contatos</h5>
                    <?php
                    $total_contatos = 0;
                    foreach ($estatisticas['origem'] as $interacao) {
                        if ($interacao['tipo_interacao'] === 'contato') {
                            $total_contatos = $interacao['total'];
                            break;
                        }
                    }
                    ?>
                    <h2 class="display-5 fw-bold"><?php echo $total_contatos; ?></h2>
                    <p class="text-muted small">Contatos iniciados com talentos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-warning mb-2">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h5 class="card-title">Convites</h5>
                    <?php
                    $total_convites = 0;
                    foreach ($estatisticas['origem'] as $interacao) {
                        if ($interacao['tipo_interacao'] === 'convite_entrevista') {
                            $total_convites = $interacao['total'];
                            break;
                        }
                    }
                    ?>
                    <h2 class="display-5 fw-bold"><?php echo $total_convites; ?></h2>
                    <p class="text-muted small">Convites para entrevistas enviados</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-danger mb-2">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h5 class="card-title">Candidaturas</h5>
                    <?php
                    $total_candidaturas = 0;
                    foreach ($estatisticas['destino'] as $interacao) {
                        if ($interacao['tipo_interacao'] === 'candidatura') {
                            $total_candidaturas = $interacao['total'];
                            break;
                        }
                    }
                    ?>
                    <h2 class="display-5 fw-bold"><?php echo $total_candidaturas; ?></h2>
                    <p class="text-muted small">Candidaturas recebidas</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico de interações -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Histórico de Interações</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoInteracoes" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimas interações -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Últimas Interações</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Talento</th>
                                    <th>Direção</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimas_interacoes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Nenhuma interação registrada no período selecionado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ultimas_interacoes as $interacao): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($interacao['data_interacao'])); ?></td>
                                            <td>
                                                <?php 
                                                switch ($interacao['tipo_interacao']) {
                                                    case 'visualizacao_perfil':
                                                        echo '<span class="badge bg-primary">Visualização</span>';
                                                        break;
                                                    case 'contato':
                                                        echo '<span class="badge bg-success">Contato</span>';
                                                        break;
                                                    case 'convite_entrevista':
                                                        echo '<span class="badge bg-warning text-dark">Convite</span>';
                                                        break;
                                                    case 'candidatura':
                                                        echo '<span class="badge bg-danger">Candidatura</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Outro</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($interacao['direcao'] === 'origem') {
                                                    echo htmlspecialchars($interacao['outro_usuario_nome']);
                                                    if (!empty($interacao['outro_usuario_info'])) {
                                                        echo ' <small class="text-muted">(' . htmlspecialchars($interacao['outro_usuario_info']) . ')</small>';
                                                    }
                                                } else {
                                                    echo htmlspecialchars($interacao['outro_usuario_nome']);
                                                    if (!empty($interacao['outro_usuario_info'])) {
                                                        echo ' <small class="text-muted">(' . htmlspecialchars($interacao['outro_usuario_info']) . ')</small>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($interacao['direcao'] === 'origem'): ?>
                                                    <span class="text-primary"><i class="fas fa-arrow-right"></i> Enviada</span>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="fas fa-arrow-left"></i> Recebida</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($interacao['detalhes']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para o gráfico
    var ctx = document.getElementById('graficoInteracoes').getContext('2d');
    var graficoInteracoes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Visualizações', 'Contatos', 'Convites', 'Candidaturas'],
            datasets: [
                {
                    label: 'Enviadas',
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    data: [
                        <?php
                        $visualizacoes = 0;
                        $contatos = 0;
                        $convites = 0;
                        $candidaturas_enviadas = 0;
                        
                        foreach ($estatisticas['origem'] as $interacao) {
                            if ($interacao['tipo_interacao'] === 'visualizacao_perfil') {
                                $visualizacoes = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'contato') {
                                $contatos = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'convite_entrevista') {
                                $convites = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'candidatura') {
                                $candidaturas_enviadas = $interacao['total'];
                            }
                        }
                        
                        echo $visualizacoes . ', ' . $contatos . ', ' . $convites . ', ' . $candidaturas_enviadas;
                        ?>
                    ]
                },
                {
                    label: 'Recebidas',
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    data: [
                        <?php
                        $visualizacoes_recebidas = 0;
                        $contatos_recebidos = 0;
                        $convites_recebidos = 0;
                        $candidaturas = 0;
                        
                        foreach ($estatisticas['destino'] as $interacao) {
                            if ($interacao['tipo_interacao'] === 'visualizacao_perfil') {
                                $visualizacoes_recebidas = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'contato') {
                                $contatos_recebidos = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'convite_entrevista') {
                                $convites_recebidos = $interacao['total'];
                            } elseif ($interacao['tipo_interacao'] === 'candidatura') {
                                $candidaturas = $interacao['total'];
                            }
                        }
                        
                        echo $visualizacoes_recebidas . ', ' . $contatos_recebidos . ', ' . $convites_recebidos . ', ' . $candidaturas;
                        ?>
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>
