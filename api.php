<?php
/**
 * Funções relacionadas à API de consulta de IMEI
 * 
 * Este arquivo contém as funções para interagir com a API externa
 */

// Incluir arquivo de configuração
require_once 'config.php';

/**
 * Função para consultar a API
 * 
 * @param string $serviceId ID do serviço de consulta
 * @param string $imei IMEI do dispositivo
 * @return object Objeto com a resposta da API
 */
function consultarAPI($serviceId, $imei) {
    global $ERRORS;
    
    $myCheck = [
        "service" => $serviceId,
        "imei" => $imei,
        "key" => API_KEY
    ];
    
    // Registrar a requisição no log
    registrarLog("Request: " . print_r($myCheck, true));
    
    // Verificar se existe cache válido
    $cacheFile = CACHE_FOLDER . "/" . md5($myCheck["service"] . $myCheck["imei"]) . ".json";
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < CACHE_DURATION)) {
        // Usar cache se tiver menos de 1 hora
        $resultado = json_decode(file_get_contents($cacheFile));
        registrarLog("Usando cache para IMEI: " . $imei);
        return $resultado;
    }
    
    try {
        // Inicialização do cURL
        $ch = curl_init(API_URL);
        
        // Configurações do cURL
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, REQUEST_TIMEOUT);
        
        // Executa a requisição
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception($ERRORS['CURL_ERROR'] . curl_error($ch));
        }
        
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode != 200) {
            throw new Exception($ERRORS['HTTP_ERROR'] . $httpcode);
        }
        
        $resultado = json_decode($response);
        
        if (isset($resultado->success) && $resultado->success !== true) {
            throw new Exception($ERRORS['API_ERROR'] . (isset($resultado->error) ? $resultado->error : $ERRORS['UNKNOWN_ERROR']));
        }
        
        // Salvar no cache
        file_put_contents($cacheFile, $response);
        
        // Registrar a resposta no log
        registrarLog("Response: " . print_r($resultado, true));
        
        return $resultado;
    } catch (Exception $e) {
        registrarLog("Erro: " . $e->getMessage());
        return (object) [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Função para registrar logs
 * 
 * @param string $mensagem Mensagem a ser registrada no log
 * @return void
 */
function registrarLog($mensagem) {
    file_put_contents(LOG_FILE, date("Y-m-d H:i:s") . " - " . $mensagem . PHP_EOL, FILE_APPEND);
}

/**
 * Função para validar o formato do IMEI
 * 
 * @param string $imei IMEI a ser validado
 * @return bool Retorna true se o IMEI for válido
 */
function validarIMEI($imei) {
    return preg_match('/^[0-9]{15}$/', $imei);
}

/**
 * Função para limpar o cache de um IMEI específico
 * 
 * @param string $serviceId ID do serviço
 * @param string $imei IMEI do dispositivo
 * @return bool Retorna true se o cache foi limpo com sucesso
 */
function limparCacheIMEI($serviceId, $imei) {
    $cacheFile = CACHE_FOLDER . "/" . md5($serviceId . $imei) . ".json";
    
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return false;
}

/**
 * Função para limpar todo o cache
 * 
 * @return int Número de arquivos de cache removidos
 */
function limparTodoCache() {
    $count = 0;
    $files = glob(CACHE_FOLDER . "/*.json");
    
    foreach ($files as $file) {
        if (unlink($file)) {
            $count++;
        }
    }
    
    return $count;
} 