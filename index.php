<?php
/**
 * Sistema de Consulta de IMEI
 * 
 * Este é o arquivo principal que exibe a interface de usuário e processa as requisições
 */

// Incluir arquivos necessários
require_once 'config.php';
require_once 'api.php';

// Criar pasta de cache se não existir
if (!file_exists(CACHE_FOLDER)) {
    mkdir(CACHE_FOLDER, 0755, true);
}

// Validação de IMEI
function validarIMEI($imei) {
    return preg_match('/^[0-9]{15}$/', $imei);
}

// Função para consultar a API
function consultarAPI($serviceId, $imei) {
    $myCheck = [
        "service" => $serviceId,
        "imei" => $imei,
        "key" => API_KEY
    ];
    
    // Registrar a requisição no log
    registrarLog("Request: " . print_r($myCheck, true));
    
    // Verificar se existe cache válido
    $cacheFile = CACHE_FOLDER . "/" . md5($myCheck["service"] . $myCheck["imei"]) . ".json";
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Executa a requisição
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new Exception("Erro cURL: " . curl_error($ch));
        }
        
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode != 200) {
            throw new Exception("Erro HTTP: " . $httpcode);
        }
        
        $resultado = json_decode($response);
        
        if (isset($resultado->success) && $resultado->success !== true) {
            throw new Exception("Erro na API: " . (isset($resultado->error) ? $resultado->error : "Erro desconhecido"));
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

function registrarLog($mensagem) {
    file_put_contents(LOG_FILE, date("Y-m-d H:i:s") . " - " . $mensagem . PHP_EOL, FILE_APPEND);
}

// Processamento do formulário
$resultado = null;
$mensagemErro = null;
$imei = '';
$service = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imei = isset($_POST['imei']) ? trim($_POST['imei']) : '';
    $service = isset($_POST['service']) ? $_POST['service'] : '1';
    
    if (empty($imei)) {
        $mensagemErro = $ERRORS['EMPTY_IMEI'];
    } elseif (!validarIMEI($imei)) {
        $mensagemErro = $ERRORS['INVALID_IMEI'];
    } else {
        $resultado = consultarAPI($service, $imei);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .result-box {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .header-img {
            max-height: 150px;
            margin: 20px auto;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h3><?php echo SITE_TITLE; ?></h3>
                    </div>
                    <div class="card-body">
                        <img src="https://via.placeholder.com/500x150?text=Consulta+IMEI" alt="Consulta IMEI" class="header-img img-fluid">
                        
                        <?php if ($mensagemErro): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($mensagemErro); ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" class="mt-4">
                            <div class="mb-3">
                                <label for="service" class="form-label">Serviço:</label>
                                <select name="service" id="service" class="form-select">
                                    <?php foreach ($SERVICES as $id => $nome): ?>
                                    <option value="<?php echo $id; ?>" <?php echo $service == $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($nome); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="imei" class="form-label">IMEI do Dispositivo:</label>
                                <input type="text" name="imei" id="imei" 
                                       class="form-control" 
                                       placeholder="Digite o IMEI (15 dígitos)" 
                                       value="<?php echo htmlspecialchars($imei); ?>"
                                       maxlength="15"
                                       required>
                                <div class="form-text">O IMEI deve conter exatamente 15 dígitos numéricos.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Consultar IMEI</button>
                            </div>
                        </form>
                        
                        <?php if ($resultado && isset($resultado->success) && $resultado->success === true): ?>
                        <div class="result-box mt-4">
                            <h4>Resultado da Consulta:</h4>
                            
                            <?php if (isset($resultado->response)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($resultado->response); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($resultado->object)): ?>
                            <div class="card">
                                <div class="card-header">
                                    Detalhes do Dispositivo
                                </div>
                                <div class="card-body">
                                    <pre><?php echo htmlspecialchars(print_r($resultado->object, true)); ?></pre>
                                    
                                    <?php if (isset($resultado->object->model)): ?>
                                    <p><strong>Modelo:</strong> <?php echo htmlspecialchars($resultado->object->model); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($resultado && isset($resultado->success) && $resultado->success === false): ?>
                        <div class="alert alert-danger mt-4">
                            <h4>Erro na Consulta:</h4>
                            <p><?php echo htmlspecialchars($resultado->error); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4>Como encontrar seu IMEI</h4>
                    </div>
                    <div class="card-body">
                        <p>Existem várias maneiras de encontrar o IMEI do seu dispositivo:</p>
                        <ul>
                            <li>Disque <strong>*#06#</strong> no teclado do seu telefone</li>
                            <li>Em iPhones: Configurações > Geral > Sobre</li>
                            <li>Em Android: Configurações > Sobre o telefone > Status > Informações do IMEI</li>
                            <li>Verifique na caixa original do dispositivo</li>
                            <li>Verifique na parte traseira da bateria (dispositivos com bateria removível)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?>. Todos os direitos reservados.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do campo IMEI no lado do cliente
        document.getElementById('imei').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 15);
        });
    </script>
</body>
</html> 