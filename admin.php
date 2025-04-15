<?php
/**
 * Painel Administrativo
 * 
 * Interface para gerenciar o sistema de consulta de IMEI
 */

// Incluir arquivos necessários
require_once 'config.php';
require_once 'api.php';

// Verificar autenticação
session_start();
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // Recomendado: alterar para uma senha segura

$autenticado = false;
$mensagem = null;
$tipo_mensagem = null;

// Processar login
if (isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    
    if ($usuario === ADMIN_USER && $senha === ADMIN_PASS) {
        $_SESSION['admin_autenticado'] = true;
        $autenticado = true;
    } else {
        $mensagem = 'Usuário ou senha incorretos!';
        $tipo_mensagem = 'danger';
    }
}

// Verificar se está autenticado
if (isset($_SESSION['admin_autenticado']) && $_SESSION['admin_autenticado'] === true) {
    $autenticado = true;
}

// Processar ações administrativas
if ($autenticado && isset($_POST['acao'])) {
    switch ($_POST['acao']) {
        case 'limpar_cache':
            $total = limparTodoCache();
            $mensagem = "Cache limpo com sucesso! $total arquivo(s) removido(s).";
            $tipo_mensagem = 'success';
            break;
            
        case 'limpar_cache_imei':
            $imei = isset($_POST['imei']) ? trim($_POST['imei']) : '';
            $service = isset($_POST['service']) ? $_POST['service'] : '1';
            
            if (empty($imei)) {
                $mensagem = "Por favor, informe o IMEI para limpar o cache.";
                $tipo_mensagem = 'warning';
            } elseif (!validarIMEI($imei)) {
                $mensagem = "IMEI inválido. O IMEI deve conter exatamente 15 dígitos numéricos.";
                $tipo_mensagem = 'warning';
            } else {
                $resultado = limparCacheIMEI($service, $imei);
                if ($resultado) {
                    $mensagem = "Cache para IMEI $imei (Serviço $service) limpo com sucesso!";
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = "Não foi encontrado cache para IMEI $imei (Serviço $service).";
                    $tipo_mensagem = 'info';
                }
            }
            break;
            
        case 'limpar_logs':
            if (file_exists(LOG_FILE)) {
                file_put_contents(LOG_FILE, '');
                $mensagem = "Logs limpos com sucesso!";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "Arquivo de logs não encontrado.";
                $tipo_mensagem = 'info';
            }
            break;
            
        case 'logout':
            session_destroy();
            $autenticado = false;
            $mensagem = "Sessão encerrada com sucesso!";
            $tipo_mensagem = 'success';
            break;
    }
}

// Obter informações do sistema
$info = [
    'arquivos_cache' => 0,
    'tamanho_cache' => 0,
    'tamanho_logs' => 0,
    'ultima_consulta' => 'Nunca'
];

if ($autenticado) {
    // Contar arquivos de cache
    if (file_exists(CACHE_FOLDER)) {
        $arquivos = glob(CACHE_FOLDER . "/*.json");
        $info['arquivos_cache'] = count($arquivos);
        
        // Calcular tamanho do cache
        $tamanho = 0;
        foreach ($arquivos as $arquivo) {
            $tamanho += filesize($arquivo);
        }
        $info['tamanho_cache'] = formatarTamanho($tamanho);
    }
    
    // Tamanho do arquivo de logs
    if (file_exists(LOG_FILE)) {
        $info['tamanho_logs'] = formatarTamanho(filesize(LOG_FILE));
        
        // Verificar última consulta
        $conteudo = file_get_contents(LOG_FILE);
        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}).*Request/m', $conteudo, $matches)) {
            $info['ultima_consulta'] = $matches[1];
        }
    }
}

// Função para formatar tamanho de arquivo
function formatarTamanho($bytes) {
    $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($unidades) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $unidades[$i];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?php echo SITE_TITLE; ?></title>
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
        pre.logs {
            max-height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white text-center">
                        <h3>Painel Administrativo - <?php echo SITE_TITLE; ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($mensagem): ?>
                        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($mensagem); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$autenticado): ?>
                        <!-- Formulário de Login -->
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white text-center">
                                        <h5>Login</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="mb-3">
                                                <label for="usuario" class="form-label">Usuário:</label>
                                                <input type="text" name="usuario" id="usuario" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="senha" class="form-label">Senha:</label>
                                                <input type="password" name="senha" id="senha" class="form-control" required>
                                            </div>
                                            <div class="d-grid">
                                                <input type="hidden" name="acao" value="login">
                                                <button type="submit" class="btn btn-primary">Entrar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Painel Administrativo -->
                        <div class="row mb-4">
                            <div class="col-md-12 text-end">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="acao" value="logout">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-box-arrow-right"></i> Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $info['arquivos_cache']; ?></h5>
                                        <p class="card-text">Arquivos em Cache</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $info['tamanho_cache']; ?></h5>
                                        <p class="card-text">Tamanho do Cache</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $info['tamanho_logs']; ?></h5>
                                        <p class="card-text">Tamanho dos Logs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $info['ultima_consulta']; ?></h5>
                                        <p class="card-text">Última Consulta</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ferramentas administrativas -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5>Gerenciamento de Cache</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" class="mb-3">
                                            <input type="hidden" name="acao" value="limpar_cache">
                                            <button type="submit" class="btn btn-danger">Limpar Todo o Cache</button>
                                        </form>
                                        
                                        <hr>
                                        
                                        <h6>Limpar Cache de IMEI Específico</h6>
                                        <form method="post">
                                            <div class="mb-3">
                                                <label for="service_cache" class="form-label">Serviço:</label>
                                                <select name="service" id="service_cache" class="form-select">
                                                    <?php foreach ($SERVICES as $id => $nome): ?>
                                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nome); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="imei_cache" class="form-label">IMEI:</label>
                                                <input type="text" name="imei" id="imei_cache" class="form-control" 
                                                       placeholder="Digite o IMEI (15 dígitos)" maxlength="15" required>
                                            </div>
                                            <input type="hidden" name="acao" value="limpar_cache_imei">
                                            <button type="submit" class="btn btn-warning">Limpar Cache do IMEI</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5>Logs do Sistema</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" class="mb-3">
                                            <input type="hidden" name="acao" value="limpar_logs">
                                            <button type="submit" class="btn btn-danger">Limpar Logs</button>
                                        </form>
                                        
                                        <hr>
                                        
                                        <h6>Visualizar Logs</h6>
                                        <pre class="logs"><?php 
                                        if (file_exists(LOG_FILE)) {
                                            $logs = file_get_contents(LOG_FILE);
                                            echo htmlspecialchars($logs);
                                        } else {
                                            echo "Nenhum log encontrado.";
                                        }
                                        ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">Voltar para a Página Principal</a>
                        </div>
                        <?php endif; ?>
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
        document.getElementById('imei_cache')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 15);
        });
    </script>
</body>
</html> 