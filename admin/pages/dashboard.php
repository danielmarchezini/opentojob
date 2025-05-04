<div class="dashboard-container">
    <div class="row">
        <!-- Estatísticas Rápidas -->
        <div class="col-md-3 mb-4">
            <div class="stat-card">
                <?php
                $db = Database::getInstance();
                $total_talentos = $db->fetchColumn("SELECT COUNT(*) FROM talentos");
                ?>
                <div class="stat-card-body">
                    <div class="stat-card-icon bg-primary">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Talentos</h5>
                        <h2 class="stat-card-value"><?php echo number_format($total_talentos, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_talentos_admin">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card">
                <?php
                $total_empresas = $db->fetchColumn("SELECT COUNT(*) FROM empresas");
                ?>
                <div class="stat-card-body">
                    <div class="stat-card-icon bg-success">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Empresas</h5>
                        <h2 class="stat-card-value"><?php echo number_format($total_empresas, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_empresas_admin">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card">
                <?php
                $total_vagas = $db->fetchColumn("SELECT COUNT(*) FROM vagas");
                ?>
                <div class="stat-card-body">
                    <div class="stat-card-icon bg-warning">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Vagas</h5>
                        <h2 class="stat-card-value"><?php echo number_format($total_vagas, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card">
                <?php
                $total_candidaturas = $db->fetchColumn("SELECT COUNT(*) FROM candidaturas");
                ?>
                <div class="stat-card-body">
                    <div class="stat-card-icon bg-info">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-card-info">
                        <h5 class="stat-card-title">Candidaturas</h5>
                        <h2 class="stat-card-value"><?php echo number_format($total_candidaturas, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin">Ver detalhes <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estatísticas de Interações -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Estatísticas de Interações (Últimos 30 dias)</h5>
                    <a href="<?php echo SITE_URL; ?>/?route=estatisticas_interacoes" class="btn btn-sm btn-primary">
                        <i class="fas fa-chart-bar"></i> Ver Detalhes
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Obter estatísticas de interações dos últimos 30 dias
                        $data_inicio = date('Y-m-d', strtotime('-30 days'));
                        $data_atual = date('Y-m-d');
                        
                        // Total de interações
                        $total_interacoes = $db->fetchColumn("SELECT COUNT(*) FROM estatisticas_interacoes WHERE data_interacao BETWEEN :data_inicio AND :data_atual", [
                            'data_inicio' => $data_inicio . ' 00:00:00',
                            'data_atual' => $data_atual . ' 23:59:59'
                        ]);
                        
                        // Interações por tipo
                        $interacoes_por_tipo = $db->fetchAll("SELECT tipo_interacao, COUNT(*) as total FROM estatisticas_interacoes WHERE data_interacao BETWEEN :data_inicio AND :data_atual GROUP BY tipo_interacao", [
                            'data_inicio' => $data_inicio . ' 00:00:00',
                            'data_atual' => $data_atual . ' 23:59:59'
                        ]);
                        
                        // Preparar dados para exibição
                        $visualizacoes = 0;
                        $contatos = 0;
                        $convites = 0;
                        $candidaturas = 0;
                        
                        foreach ($interacoes_por_tipo as $interacao) {
                            switch ($interacao['tipo_interacao']) {
                                case 'visualizacao_perfil':
                                    $visualizacoes = $interacao['total'];
                                    break;
                                case 'contato':
                                    $contatos = $interacao['total'];
                                    break;
                                case 'convite_entrevista':
                                    $convites = $interacao['total'];
                                    break;
                                case 'candidatura':
                                    $candidaturas = $interacao['total'];
                                    break;
                            }
                        }
                        ?>
                        
                        <div class="col-md-3 mb-4">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-primary">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <h5 class="stat-card-title">Visualizações</h5>
                                        <h2 class="stat-card-value"><?php echo number_format($visualizacoes, 0, ',', '.'); ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-success">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <h5 class="stat-card-title">Contatos</h5>
                                        <h2 class="stat-card-value"><?php echo number_format($contatos, 0, ',', '.'); ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-warning">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <h5 class="stat-card-title">Convites</h5>
                                        <h2 class="stat-card-value"><?php echo number_format($convites, 0, ',', '.'); ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-danger">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <h5 class="stat-card-title">Candidaturas</h5>
                                        <h2 class="stat-card-value"><?php echo number_format($candidaturas, 0, ',', '.'); ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Gráfico de Registros -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Registros nos Últimos 30 Dias</h5>
                </div>
                <div class="card-body">
                    <canvas id="registrosChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Últimos Usuários -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Últimos Usuários Registrados</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="user-list">
                        <?php
                        $ultimos_usuarios = $db->fetchAll("
                            SELECT u.id, u.nome, u.email, u.tipo, u.data_cadastro, u.foto_perfil
                            FROM usuarios u
                            ORDER BY u.data_cadastro DESC
                            LIMIT 5
                        ");
                        
                        foreach ($ultimos_usuarios as $usuario):
                        ?>
                        <li class="user-item">
                            <div class="user-image">
                                <?php if (!empty($usuario['foto_perfil'])): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/perfil/<?php echo $usuario['foto_perfil']; ?>" alt="<?php echo $usuario['nome']; ?>">
                                <?php else: ?>
                                    <div class="user-initial"><?php echo substr($usuario['nome'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <h6 class="user-name"><?php echo $usuario['nome']; ?></h6>
                                <span class="user-email"><?php echo $usuario['email']; ?></span>
                                <div class="user-meta">
                                    <span class="user-type"><?php echo ucfirst($usuario['tipo']); ?></span>
                                    <span class="user-date"><?php echo formatAdminDate($usuario['data_cadastro']); ?></span>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_usuarios_admin" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Vagas Recentes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Vagas Recentes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Empresa</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $vagas_recentes = $db->fetchAll("
                                    SELECT v.id, v.titulo, v.status, v.data_publicacao, e.nome_empresa as empresa_nome
                                    FROM vagas v
                                    LEFT JOIN empresas e ON v.empresa_id = e.usuario_id
                                    ORDER BY v.data_publicacao DESC
                                    LIMIT 5
                                ");
                                
                                foreach ($vagas_recentes as $vaga):
                                ?>
                                <tr>
                                    <td><?php echo $vaga['titulo']; ?></td>
                                    <td><?php echo $vaga['empresa_nome'] ?? 'Externa'; ?></td>
                                    <td><?php echo getStatusBadge($vaga['status'], 'vaga'); ?></td>
                                    <td><?php echo formatAdminDate($vaga['data_publicacao']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_vagas_admin" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                </div>
            </div>
        </div>
        
        <!-- Artigos Recentes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Artigos Recentes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $artigos_recentes = $db->fetchAll("
                                    SELECT a.id, a.titulo, a.status, a.data_publicacao, u.nome as autor_nome
                                    FROM artigos_blog a
                                    LEFT JOIN usuarios u ON a.autor_id = u.id
                                    ORDER BY a.data_publicacao DESC
                                    LIMIT 5
                                ");
                                
                                foreach ($artigos_recentes as $artigo):
                                ?>
                                <tr>
                                    <td><?php echo $artigo['titulo']; ?></td>
                                    <td><?php echo $artigo['autor_nome']; ?></td>
                                    <td><?php echo getStatusBadge($artigo['status'], 'artigo'); ?></td>
                                    <td><?php echo formatAdminDate($artigo['data_publicacao']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo SITE_URL; ?>/?route=gerenciar_blog_admin" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para o gráfico (em uma implementação real, esses dados viriam do banco de dados)
    const registrosData = {
        labels: [
            <?php
            for ($i = 29; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i days");
                echo "'" . $date->format('d/m') . "',";
            }
            ?>
        ],
        datasets: [
            {
                label: 'Talentos',
                data: [0, 1, 2, 0, 1, 3, 2, 1, 0, 2, 3, 4, 2, 1, 0, 1, 2, 3, 1, 0, 2, 1, 3, 2, 1, 0, 1, 2, 0, 1],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#4e73df',
                tension: 0.3
            },
            {
                label: 'Empresas',
                data: [0, 0, 1, 0, 0, 1, 0, 1, 0, 1, 2, 0, 1, 0, 0, 1, 0, 1, 0, 0, 1, 0, 1, 0, 1, 0, 0, 1, 0, 0],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#1cc88a',
                tension: 0.3
            }
        ]
    };

    // Configuração do gráfico
    const ctx = document.getElementById('registrosChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: registrosData,
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
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
});
</script>
