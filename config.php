<?php
/**
 * Arquivo de configuração do sistema de consulta de IMEI
 * 
 * Este arquivo contém todas as constantes e configurações usadas no sistema.
 * Edite este arquivo para alterar as configurações conforme necessário.
 */

// Configurações da API
define('API_URL', 'https://api.ifreeicloud.co.uk');
define('API_KEY', 'AKS-TSG-DDA-TWH-2MY-7BW-W9E-SQ5');

// Configurações do sistema
define('CACHE_FOLDER', 'cache');
define('LOG_FILE', 'api_logs.txt');
define('CACHE_DURATION', 3600); // Duração do cache em segundos (1 hora)

// Tempo limite para requisições (em segundos)
define('CONNECT_TIMEOUT', 60);
define('REQUEST_TIMEOUT', 60);

// Descrições dos serviços disponíveis
$SERVICES = [
    '1' => 'Check Service 1',
    '2' => 'Check Service 2',
    '3' => 'Check Service 3'
];

// Mensagens de erro
$ERRORS = [
    'EMPTY_IMEI' => 'Por favor, informe o IMEI do dispositivo.',
    'INVALID_IMEI' => 'IMEI inválido. O IMEI deve conter exatamente 15 dígitos numéricos.',
    'API_ERROR' => 'Erro na API: ',
    'HTTP_ERROR' => 'Erro HTTP: ',
    'CURL_ERROR' => 'Erro cURL: ',
    'UNKNOWN_ERROR' => 'Erro desconhecido'
];

// Título e descrição do site
define('SITE_TITLE', 'Consulta de IMEI Grátis');
define('SITE_DESCRIPTION', 'Consulte informações sobre seu dispositivo móvel através do IMEI gratuitamente.'); 