<?php
/**
 * Classe para gerenciar estatísticas de interações entre talentos e empresas
 */
class Estatisticas {
    /**
     * Registra uma interação entre usuários
     * 
     * @param int $usuario_origem_id ID do usuário que iniciou a interação
     * @param int $usuario_destino_id ID do usuário que recebeu a interação
     * @param string $tipo_interacao Tipo da interação (visualizacao_perfil, contato, convite_entrevista, candidatura)
     * @param string $detalhes Detalhes adicionais sobre a interação
     * @return bool Retorna true se a interação foi registrada com sucesso
     */
    public static function registrarInteracao($usuario_origem_id, $usuario_destino_id, $tipo_interacao, $detalhes = '') {
        // Validar parâmetros
        if (!$usuario_origem_id || !$usuario_destino_id || !$tipo_interacao) {
            return false;
        }
        
        // Verificar se o tipo de interação é válido
        $tipos_validos = ['visualizacao_perfil', 'contato', 'convite_entrevista', 'candidatura'];
        if (!in_array($tipo_interacao, $tipos_validos)) {
            return false;
        }
        
        // Obter instância do banco de dados
        $db = Database::getInstance();
        
        // Inserir registro na tabela de estatísticas
        try {
            $db->query("
                INSERT INTO estatisticas_interacoes 
                (usuario_origem_id, usuario_destino_id, tipo_interacao, data_interacao, detalhes) 
                VALUES (:origem, :destino, :tipo, NOW(), :detalhes)
            ", [
                'origem' => $usuario_origem_id,
                'destino' => $usuario_destino_id,
                'tipo' => $tipo_interacao,
                'detalhes' => $detalhes
            ]);
            $result = true;
        } catch (Exception $e) {
            error_log("Erro ao registrar interação: " . $e->getMessage());
            $result = false;
        }
        
        return $result;
    }
    
    /**
     * Obtém estatísticas de interações para um usuário específico
     * 
     * @param int $usuario_id ID do usuário
     * @param string $tipo Tipo de estatística (origem, destino, ambos)
     * @param string $periodo Período de tempo (dia, semana, mes, ano, todos)
     * @return array Array com as estatísticas
     */
    public static function obterEstatisticasUsuario($usuario_id, $tipo = 'ambos', $periodo = 'todos') {
        // Validar parâmetros
        if (!$usuario_id) {
            return [];
        }
        
        // Definir condição de período
        $condicao_periodo = '';
        switch ($periodo) {
            case 'dia':
                $condicao_periodo = "AND DATE(data_interacao) = CURDATE()";
                break;
            case 'semana':
                $condicao_periodo = "AND data_interacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'mes':
                $condicao_periodo = "AND data_interacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'ano':
                $condicao_periodo = "AND data_interacao >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default:
                $condicao_periodo = "";
                break;
        }
        
        // Obter instância do banco de dados
        $db = Database::getInstance();
        
        // Definir consulta com base no tipo
        $sql = "";
        $params = [];
        
        if ($tipo == 'origem' || $tipo == 'ambos') {
            // Interações iniciadas pelo usuário
            $sql_origem = "
                SELECT 
                    'origem' as direcao,
                    tipo_interacao, 
                    COUNT(*) as total, 
                    MAX(data_interacao) as ultima_interacao
                FROM estatisticas_interacoes
                WHERE usuario_origem_id = :usuario_id
                $condicao_periodo
                GROUP BY tipo_interacao
            ";
            
            $interacoes_origem = $db->fetchAll($sql_origem, ['usuario_id' => $usuario_id]);
        } else {
            $interacoes_origem = [];
        }
        
        if ($tipo == 'destino' || $tipo == 'ambos') {
            // Interações recebidas pelo usuário
            $sql_destino = "
                SELECT 
                    'destino' as direcao,
                    tipo_interacao, 
                    COUNT(*) as total, 
                    MAX(data_interacao) as ultima_interacao
                FROM estatisticas_interacoes
                WHERE usuario_destino_id = :usuario_id
                $condicao_periodo
                GROUP BY tipo_interacao
            ";
            
            $interacoes_destino = $db->fetchAll($sql_destino, ['usuario_id' => $usuario_id]);
        } else {
            $interacoes_destino = [];
        }
        
        // Combinar resultados
        $resultado = [
            'origem' => $interacoes_origem,
            'destino' => $interacoes_destino,
            'total_origem' => array_sum(array_column($interacoes_origem, 'total')),
            'total_destino' => array_sum(array_column($interacoes_destino, 'total')),
        ];
        
        return $resultado;
    }
    
    /**
     * Obtém as últimas interações para um usuário
     * 
     * @param int $usuario_id ID do usuário
     * @param string $tipo Tipo de estatística (origem, destino, ambos)
     * @param int $limite Número máximo de interações a retornar
     * @return array Array com as últimas interações
     */
    public static function obterUltimasInteracoes($usuario_id, $tipo = 'ambos', $limite = 10) {
        // Validar parâmetros
        if (!$usuario_id) {
            return [];
        }
        
        // Garantir que o limite seja um inteiro
        $limite = (int)$limite;
        
        // Obter instância do banco de dados
        $db = Database::getInstance();
        
        // Definir consulta com base no tipo
        if ($tipo == 'origem') {
            // Interações iniciadas pelo usuário
            $sql = "
                SELECT 
                    ei.*,
                    u.nome as usuario_destino_nome,
                    CASE 
                        WHEN u.tipo = 'talento' THEN t.profissao
                        WHEN u.tipo = 'empresa' THEN e.nome_empresa
                        ELSE NULL
                    END as usuario_destino_info
                FROM estatisticas_interacoes ei
                JOIN usuarios u ON ei.usuario_destino_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id AND u.tipo = 'talento'
                LEFT JOIN empresas e ON u.id = e.usuario_id AND u.tipo = 'empresa'
                WHERE ei.usuario_origem_id = :usuario_id
                ORDER BY ei.data_interacao DESC
                LIMIT {$limite}
            ";
            
            return $db->fetchAll($sql, [
                'usuario_id' => $usuario_id
            ]);
        } elseif ($tipo == 'destino') {
            // Interações recebidas pelo usuário
            $sql = "
                SELECT 
                    ei.*,
                    u.nome as usuario_origem_nome,
                    CASE 
                        WHEN u.tipo = 'talento' THEN t.profissao
                        WHEN u.tipo = 'empresa' THEN e.nome_empresa
                        ELSE NULL
                    END as usuario_origem_info
                FROM estatisticas_interacoes ei
                JOIN usuarios u ON ei.usuario_origem_id = u.id
                LEFT JOIN talentos t ON u.id = t.usuario_id AND u.tipo = 'talento'
                LEFT JOIN empresas e ON u.id = e.usuario_id AND u.tipo = 'empresa'
                WHERE ei.usuario_destino_id = :usuario_id
                ORDER BY ei.data_interacao DESC
                LIMIT {$limite}
            ";
            
            return $db->fetchAll($sql, [
                'usuario_id' => $usuario_id
            ]);
        } else {
            // Ambas as direções
            $sql = "
                SELECT 
                    ei.*,
                    CASE 
                        WHEN ei.usuario_origem_id = :usuario_id THEN 'origem'
                        ELSE 'destino'
                    END as direcao,
                    CASE 
                        WHEN ei.usuario_origem_id = :usuario_id THEN u_destino.nome
                        ELSE u_origem.nome
                    END as outro_usuario_nome,
                    CASE 
                        WHEN ei.usuario_origem_id = :usuario_id AND u_destino.tipo = 'talento' THEN t_destino.profissao
                        WHEN ei.usuario_origem_id = :usuario_id AND u_destino.tipo = 'empresa' THEN e_destino.nome_empresa
                        WHEN ei.usuario_destino_id = :usuario_id AND u_origem.tipo = 'talento' THEN t_origem.profissao
                        WHEN ei.usuario_destino_id = :usuario_id AND u_origem.tipo = 'empresa' THEN e_origem.nome_empresa
                        ELSE NULL
                    END as outro_usuario_info
                FROM estatisticas_interacoes ei
                JOIN usuarios u_origem ON ei.usuario_origem_id = u_origem.id
                JOIN usuarios u_destino ON ei.usuario_destino_id = u_destino.id
                LEFT JOIN talentos t_origem ON u_origem.id = t_origem.usuario_id AND u_origem.tipo = 'talento'
                LEFT JOIN talentos t_destino ON u_destino.id = t_destino.usuario_id AND u_destino.tipo = 'talento'
                LEFT JOIN empresas e_origem ON u_origem.id = e_origem.usuario_id AND u_origem.tipo = 'empresa'
                LEFT JOIN empresas e_destino ON u_destino.id = e_destino.usuario_id AND u_destino.tipo = 'empresa'
                WHERE ei.usuario_origem_id = :usuario_id OR ei.usuario_destino_id = :usuario_id
                ORDER BY ei.data_interacao DESC
                LIMIT {$limite}
            ";
            
            return $db->fetchAll($sql, [
                'usuario_id' => $usuario_id
            ]);
        }
    }
}
