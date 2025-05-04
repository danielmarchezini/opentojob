<?php
/**
 * Classe para gerenciamento de cache
 * OpenToJob - Conectando talentos prontos a oportunidades imediatas
 */
class Cache {
    /**
     * Diretório onde os arquivos de cache serão armazenados
     */
    private static $cacheDir;
    
    /**
     * Inicializa o diretório de cache
     */
    public static function init() {
        self::$cacheDir = dirname(__DIR__) . '/cache';
        
        // Criar diretório de cache se não existir
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    /**
     * Salva um item no cache
     * 
     * @param string $key Chave única para identificar o item
     * @param mixed $data Dados a serem armazenados
     * @param int $ttl Tempo de vida em segundos (0 = sem expiração)
     * @return bool Verdadeiro se o cache foi salvo com sucesso
     */
    public static function set($key, $data, $ttl = 3600) {
        self::init();
        
        $cacheFile = self::getCacheFilePath($key);
        $cacheData = [
            'data' => $data,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time()
        ];
        
        return file_put_contents($cacheFile, serialize($cacheData)) !== false;
    }
    
    /**
     * Recupera um item do cache
     * 
     * @param string $key Chave do item
     * @param mixed $default Valor padrão caso o item não exista ou tenha expirado
     * @return mixed Dados armazenados ou valor padrão
     */
    public static function get($key, $default = null) {
        self::init();
        
        $cacheFile = self::getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $cacheData = unserialize(file_get_contents($cacheFile));
        
        // Verificar se o cache expirou
        if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
            self::delete($key);
            return $default;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * Verifica se um item existe no cache e não expirou
     * 
     * @param string $key Chave do item
     * @return bool Verdadeiro se o item existe e não expirou
     */
    public static function has($key) {
        self::init();
        
        $cacheFile = self::getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheData = unserialize(file_get_contents($cacheFile));
        
        // Verificar se o cache expirou
        if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
            self::delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove um item específico do cache
     * 
     * @param string $key Chave do item
     * @return bool Verdadeiro se o item foi removido ou não existia
     */
    public static function delete($key) {
        self::init();
        
        $cacheFile = self::getCacheFilePath($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Limpa todo o cache ou um grupo específico
     * 
     * @param string $group Grupo de cache a ser limpo (opcional)
     * @return bool Verdadeiro se o cache foi limpo com sucesso
     */
    public static function clear($group = null) {
        self::init();
        
        if ($group) {
            $pattern = self::$cacheDir . '/' . $group . '_*.cache';
            $files = glob($pattern);
        } else {
            $files = glob(self::$cacheDir . '/*.cache');
        }
        
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Obtém informações sobre o cache
     * 
     * @return array Informações sobre o cache (total de arquivos, tamanho, etc.)
     */
    public static function getInfo() {
        self::init();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $cacheInfo = [
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'groups' => [],
            'files' => []
        ];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size = filesize($file);
                $totalSize += $size;
                $cacheInfo['total_files']++;
                
                $key = basename($file, '.cache');
                $parts = explode('_', $key, 2);
                $group = count($parts) > 1 ? $parts[0] : 'general';
                
                if (!isset($cacheInfo['groups'][$group])) {
                    $cacheInfo['groups'][$group] = [
                        'count' => 0,
                        'size' => 0
                    ];
                }
                
                $cacheInfo['groups'][$group]['count']++;
                $cacheInfo['groups'][$group]['size'] += $size;
                
                // Verificar se o arquivo expirou
                $cacheData = unserialize(file_get_contents($file));
                $expired = $cacheData['expires'] > 0 && $cacheData['expires'] < time();
                
                if ($expired) {
                    $cacheInfo['expired_files']++;
                }
                
                $cacheInfo['files'][] = [
                    'key' => $key,
                    'group' => $group,
                    'size' => $size,
                    'created' => $cacheData['created'],
                    'expires' => $cacheData['expires'],
                    'expired' => $expired
                ];
            }
        }
        
        $cacheInfo['total_size'] = $totalSize;
        
        return $cacheInfo;
    }
    
    /**
     * Obtém o caminho do arquivo de cache para uma chave
     * 
     * @param string $key Chave do item
     * @return string Caminho completo do arquivo
     */
    private static function getCacheFilePath($key) {
        // Sanitizar a chave para evitar problemas com caracteres especiais
        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return self::$cacheDir . '/' . $key . '.cache';
    }
}
?>
