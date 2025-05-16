<?php
// Obter instância do banco de dados
$db = Database::getInstance();

// Definir período de análise
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';

// Definir datas com base no período
$data_atual = date('Y-m-d');
switch ($periodo) {
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        $titulo_periodo = 'Últimos 7 dias';
        break;
    case 'mes':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        $titulo_periodo = 'Últimos 30 dias';
        break;
    case 'trimestre':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        $titulo_periodo = 'Últimos 90 dias';
        break;
    case 'ano':
        $data_inicio = date('Y-m-d', strtotime('-365 days'));
        $titulo_periodo = 'Último ano';
        break;
    default:
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        $titulo_periodo = 'Últimos 30 dias';
        break;
}

// Obter estatísticas gerais
$total_interacoes = $db->fetchColumn("
    SELECT COUNT(*) FROM estatisticas_interacoes
    WHERE data_interacao BETWEEN :data_inicio AND :data_atual
", [
    'data_inicio' => $data_inicio . ' 00:00:00',
    'data_atual' => $data_atual . ' 23:59:59'
]);

// Obter interações por tipo
$interacoes_por_tipo = $db->fetchAll("
    SELECT tipo_interacao, COUNT(*) as total
    FROM estatisticas_interacoes
    WHERE data_interacao BETWEEN :data_inicio AND :data_atual
    GROUP BY tipo_interacao
    ORDER BY total DESC
", [
    'data_inicio' => $data_inicio . ' 00:00:00',
    'data_atual' => $data_atual . ' 23:59:59'
]);

// Obter interações por dia
$interacoes_por_dia = $db->fetchAll("
    SELECT DATE(data_interacao) as data, COUNT(*) as total
    FROM estatisticas_interacoes
    WHERE data_interacao BETWEEN :data_inicio AND :data_atual
    GROUP BY DATE(data_interacao)
    ORDER BY data ASC
", [
    'data_inicio' => $data_inicio . ' 00:00:00',
    'data_atual' => $data_atual . ' 23:59:59'
]);

// Obter empresas mais ativas
$empresas_mais_ativas = $db->fetchAll("
    SELECT e.nome_empresa, u.nome, COUNT(*) as total_interacoes
    FROM estatisticas_interacoes ei
    JOIN usuarios u ON ei.usuario_origem_id = u.id
    JOIN empresas e ON u.id = e.usuario_id
    WHERE u.tipo = 'empresa' AND data_interacao BETWEEN :data_inicio AND :data_atual
    GROUP BY ei.usuario_origem_id
    ORDER BY total_interacoes DESC
    LIMIT 10
", [
    'data_inicio' => $data_inicio . ' 00:00:00',
    'data_atual' => $data_atual . ' 23:59:59'
]);

// Obter talentos mais procurados
$talentos_mais_procurados = $db->fetchAll("
    SELECT u.nome, t.profissao, COUNT(*) as total_interacoes
    FROM estatisticas_interacoes ei
    JOIN usuarios u ON ei.usuario_destino_id = u.id
    JOIN talentos t ON u.id = t.usuario_id
    WHERE u.tipo = 'talento' AND data_interacao BETWEEN :data_inicio AND :data_atual
    GROUP BY ei.usuario_destino_id
    ORDER BY total_interacoes DESC
    LIMIT 10
", [
    'data_inicio' => $data_inicio . ' 00:00:00',
    'data_atual' => $data_atual . ' 23:59:59'
]);

// Preparar dados para gráficos
$labels_por_dia = [];
$dados_por_dia = [];

foreach ($interacoes_por_dia as $interacao) {
    $labels_por_dia[] = date('d/m', strtotime($interacao['data']));
    $dados_por_dia[] = $interacao['total'];
}

$labels_por_tipo = [];
$dados_por_tipo = [];

foreach ($interacoes_por_tipo as $interacao) {
    $tipo_label = '';
    switch ($interacao['tipo_interacao']) {
        case 'visualizacao_perfil':
            $tipo_label = 'Visualização de Perfil';
            break;
        case 'contato':
            $tipo_label = 'Contato';
            break;
        case 'convite_entrevista':
            $tipo_label = 'Convite para Entrevista';
            break;
        case 'candidatura':
            $tipo_label = 'Candidatura';
            break;
        default:
            $tipo_label = $interacao['tipo_interacao'];
            break;
    }
    
    $labels_por_tipo[] = $tipo_label;
    $dados_por_tipo[] = $interacao['total'];
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Estatísticas de Interações</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Estatísticas de Interações</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Filtros de período -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Período de Análise</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/?route=estatisticas_interacoes" method="GET" class="row">
                    <input type="hidden" name="route" value="estatisticas_interacoes">
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Selecione o período:</label>
                            <select name="periodo" class="form-control" onchange="this.form.submit()">
                                <option value="semana" <?php echo ($periodo === 'semana') ? 'selected' : ''; ?>>Últimos 7 dias</option>
                                <option value="mes" <?php echo ($periodo === 'mes') ? 'selected' : ''; ?>>Últimos 30 dias</option>
                                <option value="trimestre" <?php echo ($periodo === 'trimestre') ? 'selected' : ''; ?>>Últimos 90 dias</option>
                                <option value="ano" <?php echo ($periodo === 'ano') ? 'selected' : ''; ?>>Último ano</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end">
                        <p class="mb-0"><strong>Período atual:</strong> <?php echo $titulo_periodo; ?> (<?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_atual)); ?>)</p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Cards de resumo -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $total_interacoes; ?></h3>
                        <p>Total de Interações</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                </div>
            </div>
            
            <?php foreach ($interacoes_por_tipo as $index => $interacao): ?>
                <?php if ($index < 3): ?>
                    <div class="col-lg-3 col-6">
                        <div class="small-box <?php echo $index === 0 ? 'bg-success' : ($index === 1 ? 'bg-warning' : 'bg-danger'); ?>">
                            <div class="inner">
                                <h3><?php echo $interacao['total']; ?></h3>
                                <p>
                                    <?php 
                                    switch ($interacao['tipo_interacao']) {
                                        case 'visualizacao_perfil':
                                            echo 'Visualizações de Perfil';
                                            break;
                                        case 'contato':
                                            echo 'Contatos';
                                            break;
                                        case 'convite_entrevista':
                                            echo 'Convites para Entrevista';
                                            break;
                                        case 'candidatura':
                                            echo 'Candidaturas';
                                            break;
                                        default:
                                            echo $interacao['tipo_interacao'];
                                            break;
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas <?php 
                                    switch ($interacao['tipo_interacao']) {
                                        case 'visualizacao_perfil':
                                            echo 'fa-eye';
                                            break;
                                        case 'contato':
                                            echo 'fa-envelope';
                                            break;
                                        case 'convite_entrevista':
                                            echo 'fa-calendar-check';
                                            break;
                                        case 'candidatura':
                                            echo 'fa-briefcase';
                                            break;
                                        default:
                                            echo 'fa-chart-line';
                                            break;
                                    }
                                ?>"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Interações por Dia</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoInteracoesPorDia" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Interações por Tipo</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoInteracoesPorTipo" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabelas de dados -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Empresas Mais Ativas</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Contato</th>
                                    <th>Interações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($empresas_mais_ativas)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhuma interação de empresas no período selecionado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($empresas_mais_ativas as $empresa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)$empresa['nome_empresa'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars((string)$empresa['nome']); ?></td>
                                            <td><?php echo $empresa['total_interacoes']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Talentos Mais Procurados</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Talento</th>
                                    <th>Profissão</th>
                                    <th>Interações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($talentos_mais_procurados)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhuma interação com talentos no período selecionado.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($talentos_mais_procurados as $talento): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)$talento['nome']); ?></td>
                                            <td><?php echo htmlspecialchars((string)$talento['profissao'] ?: 'N/A'); ?></td>
                                            <td><?php echo $talento['total_interacoes']; ?></td>
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
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de interações por dia
    var ctxDia = document.getElementById('graficoInteracoesPorDia').getContext('2d');
    var graficoInteracoesPorDia = new Chart(ctxDia, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_por_dia); ?>,
            datasets: [{
                label: 'Interações',
                data: <?php echo json_encode($dados_por_dia); ?>,
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(60, 141, 188, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(60, 141, 188, 1)',
                fill: true
            }]
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
    
    // Gráfico de interações por tipo
    var ctxTipo = document.getElementById('graficoInteracoesPorTipo').getContext('2d');
    var graficoInteracoesPorTipo = new Chart(ctxTipo, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels_por_tipo); ?>,
            datasets: [{
                data: <?php echo json_encode($dados_por_tipo); ?>,
                backgroundColor: [
                    'rgba(60, 141, 188, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});
</script>
