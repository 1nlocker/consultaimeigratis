<?php
// Sistema de Consulta de Dispositivos - ifreeicloud API
// Autor: Claude - Data: 15/04/2025

// Inicialização das variáveis
$api_url = "https://api.ifreeicloud.co.uk";
$api_key = "AKS-TSG-DDA-TWH-2MY-7BW-W9E-SQ5";
$service_id = "0"; // Valor padrão do service ID
$step = 1; // Controle de etapas
$imei = "";
$result = null;
$error = null;
$download = false;

// Processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['step']) && $_POST['step'] == "1" && isset($_POST['imei'])) {
        // Etapa 1 - Captura do IMEI
        $imei = trim($_POST['imei']);
        $step = 2;
    } 
    elseif (isset($_POST['step']) && $_POST['step'] == "2" && isset($_POST['imei'])) {
        // Etapa 2 - Processamento da consulta
        $imei = trim($_POST['imei']);
        
        // Preparar dados para a API
        $myCheck = [
            "service" => $service_id,
            "imei" => $imei,
            "key" => $api_key
        ];
        
        // Inicializar cURL
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Executar requisição
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Processar resultado
        if ($httpcode != 200) {
            $error = "Erro na conexão: HTTP Code $httpcode";
            $step = 2; // Mantém na etapa 2 para tentar novamente
        } else {
            $myResult = json_decode($response);
            if (!$myResult || (isset($myResult->success) && $myResult->success !== true)) {
                $error = isset($myResult->error) ? "Erro: " . $myResult->error : "Erro desconhecido na consulta";
                $step = 2; // Mantém na etapa 2 para tentar novamente
            } else {
                $result = $myResult;
                $step = 3; // Avança para a etapa de resultados
            }
        }
    }
    elseif (isset($_POST['step']) && $_POST['step'] == "3" && isset($_POST['download']) && isset($_POST['data'])) {
        // Etapa 3 - Download dos dados
        $download = true;
        $data = $_POST['data'];
        $filename = "device_info_" . (isset($_POST['imei']) ? $_POST['imei'] : "unknown") . ".txt";
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $data;
        exit;
    }
}

// Se não estiver fazendo download, continua com o HTML
if (!$download):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Dispositivos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .step {
            width: 30px;
            height: 30px;
            background-color: #e0e0e0;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            float: left;
        }
        .step.active {
            background-color: #2196F3;
        }
        .step-line {
            height: 2px;
            width: 50px;
            background-color: #e0e0e0;
            margin-top: 15px;
            float: left;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            background-color: #f9f9f9;
        }
        .btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-secondary {
            background-color: #999;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .code-block {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .result-info {
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            margin: 15px 0;
        }
        .error {
            color: red;
            font-weight: bold;
            padding: 10px;
            background-color: #ffeeee;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            color: green;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="steps clearfix">
                <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?>">1</div>
                <div class="step-line"></div>
                <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?>">2</div>
                <div class="step-line"></div>
                <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?>">3</div>
            </div>
            
            <?php if ($step == 1): ?>
                <h1>My Check</h1>
                <p>Let's first prepare your IMEI Check! We've added the Service ID and your API Key already. Just enter the IMEI or Serial Number to check, then click Next...</p>
            <?php elseif ($step == 2): ?>
                <h1>Process Check</h1>
                <p>Now that you have chosen a service, selected a device and provided your API Key, we can now process the IMEI Check!</p>
            <?php else: ?>
                <h1>My Result</h1>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <!-- Etapa 1: Entrada do IMEI -->
            <form method="post" action="">
                <input type="hidden" name="step" value="1">
                
                <div class="form-group">
                    <label for="imei">IMEI/Serial:</label>
                    <input type="text" id="imei" name="imei" class="form-control" value="<?php echo htmlspecialchars($imei); ?>" required>
                </div>
                
                <div class="code-block">
                    $myCheck["service"] = "<?php echo $service_id; ?>";<br>
                    $myCheck["imei"] = "<?php echo $imei ? htmlspecialchars($imei) : ''; ?>";<br>
                    $myCheck["key"] = "<?php echo $api_key; ?>";
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn">Next »</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Cancel</button>
                </div>
            </form>
            
        <?php elseif ($step == 2): ?>
            <!-- Etapa 2: Processamento -->
            <div class="code-block">
                $ch = curl_init("<?php echo $api_url; ?>");<br>
                curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck);<br>
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);<br>
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);<br>
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);<br>
                $myResult = json_decode(curl_exec($ch));<br>
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);<br>
                curl_close($ch);
            </div>
            
            <p>The code above will be executed, and you will be charged whatever the price of the service, if successful.</p>
            
            <form method="post" action="">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="imei" value="<?php echo htmlspecialchars($imei); ?>">
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn">Process »</button>
                </div>
            </form>
            
        <?php elseif ($step == 3 && $result): ?>
            <!-- Etapa 3: Resultados -->
            <?php if (isset($result->object)): ?>
                <div class="result-info">
                    <?php if (isset($result->object->model)): ?>
                        <p>Model: <?php echo htmlspecialchars($result->object->model); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result->object->modelName)): ?>
                        <p>Model Name: <?php echo htmlspecialchars($result->object->modelName); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result->object->brand)): ?>
                        <p>Brand: <?php echo htmlspecialchars($result->object->brand); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result->object->manufacturer)): ?>
                        <p>Manufacturer: <?php echo htmlspecialchars($result->object->manufacturer); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($result->object->imei)): ?>
                        <p>IMEI Number: <?php echo htmlspecialchars($result->object->imei); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="code-block">
                    <?php 
                    // Formatar o JSON para exibição
                    $json_formatted = json_encode($result->object, JSON_PRETTY_PRINT);
                    echo htmlspecialchars($json_formatted);
                    ?>
                </div>
                
                <div class="code-block">
                    if($httpcode != 200) {<br>
                    &nbsp;&nbsp;echo "<span style='color:red'>Error: HTTP Code $httpcode</span>";<br>
                    } elseif($myResult->success !== true) {<br>
                    &nbsp;&nbsp;echo "<span style='color:red'>Error: $myResult->error</span>";<br>
                    } else {<br>
                    &nbsp;&nbsp;echo $myResult->response;<br>
                    &nbsp;&nbsp;echo "&lt;hr&gt;&lt;pre&gt;".print_r($myResult->object, true)."&lt;/pre&gt;&lt;hr&gt;";<br>
                    &nbsp;&nbsp;// Here you can access specific info!<br>
                    &nbsp;&nbsp;// echo $myResult->object->model;<br>
                    }
                </div>
                
                <p>It's as simple as that! Copy our sample code to your own server when you think you're ready!</p>
                
                <!-- Formulário para download das informações -->
                <form method="post" action="">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="imei" value="<?php echo htmlspecialchars($imei); ?>">
                    <input type="hidden" name="download" value="1">
                    <input type="hidden" name="data" value="<?php echo htmlspecialchars($json_formatted); ?>">
                    
                    <div class="form-group" style="text-align: center; margin-top: 20px;">
                        <button type="button" class="btn" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Nova Consulta</button>
                        <button type="submit" class="btn">Baixar Informações</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="error">
                    Não foi possível obter detalhes do dispositivo.
                </div>
                <div class="form-group" style="text-align: center; margin-top: 20px;">
                    <button type="button" class="btn" onclick="window.location.href='<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'">Tentar Novamente</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php endif; ?>