<?php
$db = Database::getInstance();

// Estatísticas gerais
$total_usuarios = $db->fetchColumn("SELECT COUNT(*) FROM usuarios");
$total_talentos = $db->fetchColumn("SELECT COUNT(*) FROM talentos");
$total_empresas = $db->fetchColumn("SELECT COUNT(*) FROM empresas");
$total_vagas = $db->fetchColumn("SELECT COUNT(*) FROM vagas");
$total_candidaturas = $db->fetchColumn("SELECT COUNT(*) FROM candidaturas");

// Estatísticas por mês (últimos 6 meses)
$meses = [];
$usuarios_por_mes = [];
$vagas_por_mes = [];
$candidaturas_por_mes = [];

for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $mes_nome = date('M/Y', strtotime("-$i months"));
    $meses[] = $mes_nome;
    
    $inicio_mes = $mes . '-01';
    $fim_mes = date('Y-m-t', strtotime($inicio_mes));
    
    $usuarios_mes = $db->fetchColumn("
        SELECT COUNT(*) FROM usuarios 
        WHERE data_cadastro BETWEEN '$inicio_mes' AND '$fim_mes 23:59:59'
    ");
    $usuarios_por_mes[] = $usuarios_mes;
    
    $vagas_mes = $db->fetchColumn("
        SELECT COUNT(*) FROM vagas 
        WHERE data_publicacao BETWEEN '$inicio_mes' AND '$fim_mes 23:59:59'
    ");
    $vagas_por_mes[] = $vagas_mes;
    
    $candidaturas_mes = $db->fetchColumn("
        SELECT COUNT(*) FROM candidaturas 
        WHERE data_candidatura BETWEEN '$inicio_mes' AND '$fim_mes 23:59:59'
    ");
    $candidaturas_por_mes[] = $candidaturas_mes;
}

// Converter arrays para formato JSON para uso nos gráficos
$meses_json = json_encode($meses);
$usuarios_json = json_encode($usuarios_por_mes);
$vagas_json = json_encode($vagas_por_mes);
$candidaturas_json = json_encode($candidaturas_por_mes);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Relatórios</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?route=painel_admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Relatórios</li>
    </ol>

    <!-- Estatísticas Gerais -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($total_usuarios, 0, ',', '.'); ?></h3>
                        <p>Usuários Cadastrados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_usuarios" class="small-box-footer">
                        Mais informações <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($total_talentos, 0, ',', '.'); ?></h3>
                        <p>Talentos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Mais informações <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($total_empresas, 0, ',', '.'); ?></h3>
                        <p>Empresas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        Mais informações <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($total_vagas, 0, ',', '.'); ?></h3>
                        <p>Vagas Publicadas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin" class="small-box-footer">
                        Mais informações <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-users me-1"></i>
                        Usuários (Últimos 6 meses)
                    </div>
                    <div class="card-body">
                        <canvas id="usuariosChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-briefcase me-1"></i>
                        Vagas (Últimos 6 meses)
                    </div>
                    <div class="card-body">
                        <canvas id="vagasChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-alt me-1"></i>
                        Candidaturas (Últimos 6 meses)
                    </div>
                    <div class="card-body">
                        <canvas id="candidaturasChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-1"></i>
                        Distribuição de Usuários
                    </div>
                    <div class="card-body">
                        <canvas id="usuariosTipoChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Usuários
    var usuariosCtx = document.getElementById('usuariosChart').getContext('2d');
    var usuariosChart = new Chart(usuariosCtx, {
        type: 'line',
        data: {
            labels: <?php echo $meses_json; ?>,
            datasets: [{
                label: 'Novos Usuários',
                data: <?php echo $usuarios_json; ?>,
                backgroundColor: 'rgba(60,141,188,0.2)',
                borderColor: 'rgba(60,141,188,1)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de Vagas
    var vagasCtx = document.getElementById('vagasChart').getContext('2d');
    var vagasChart = new Chart(vagasCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $meses_json; ?>,
            datasets: [{
                label: 'Vagas Publicadas',
                data: <?php echo $vagas_json; ?>,
                backgroundColor: 'rgba(40,167,69,0.2)',
                borderColor: 'rgba(40,167,69,1)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de Candidaturas
    var candidaturasCtx = document.getElementById('candidaturasChart').getContext('2d');
    var candidaturasChart = new Chart(candidaturasCtx, {
        type: 'line',
        data: {
            labels: <?php echo $meses_json; ?>,
            datasets: [{
                label: 'Candidaturas',
                data: <?php echo $candidaturas_json; ?>,
                backgroundColor: 'rgba(255,193,7,0.2)',
                borderColor: 'rgba(255,193,7,1)',
                pointRadius: 3,
                pointColor: '#ffc107',
                pointStrokeColor: 'rgba(255,193,7,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(255,193,7,1)',
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de Distribuição de Usuários
    var usuariosTipoCtx = document.getElementById('usuariosTipoChart').getContext('2d');
    var usuariosTipoChart = new Chart(usuariosTipoCtx, {
        type: 'doughnut',
        data: {
            labels: ['Talentos', 'Empresas', 'Administradores'],
            datasets: [{
                data: [<?php echo $total_talentos; ?>, <?php echo $total_empresas; ?>, <?php echo ($total_usuarios - $total_talentos - $total_empresas); ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true
        }
    });
});
</script>
