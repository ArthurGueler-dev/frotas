<?php
/**
 * API REST para otimização assíncrona de rotas
 * Faz ponte entre frontend e Python API
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Diretório para armazenar jobs temporários
$jobsDir = '/tmp/optimization-jobs';
if (!is_dir($jobsDir)) {
    mkdir($jobsDir, 0777, true);
}

// URL da Python API (VPS)
$pythonApiUrl = 'http://31.97.169.36:8000';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Iniciar novo job de otimização
        iniciarJob($pythonApiUrl, $jobsDir);
    } elseif ($method === 'GET' && isset($_GET['job_id'])) {
        // Verificar status de um job
        verificarStatus($_GET['job_id'], $jobsDir);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor',
        'message' => $e->getMessage()
    ]);
}

/**
 * Iniciar novo job de otimização
 */
function iniciarJob($pythonApiUrl, $jobsDir) {
    // Ler payload do frontend
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['locais'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }

    // Gerar ID único para o job
    $jobId = uniqid('job_', true);
    $jobFile = "$jobsDir/$jobId.json";

    // Salvar job como "processing"
    $jobData = [
        'job_id' => $jobId,
        'status' => 'processing',
        'created_at' => date('Y-m-d H:i:s'),
        'input' => $data
    ];
    file_put_contents($jobFile, json_encode($jobData));

    // Chamar Python API de forma assíncrona (usando curl não-bloqueante)
    $payload = json_encode($data);

    // Executar em background usando exec
    $cmd = sprintf(
        'curl -X POST "%s/optimize" -H "Content-Type: application/json" -d %s > %s 2>&1 &',
        $pythonApiUrl,
        escapeshellarg($payload),
        escapeshellarg("$jobsDir/$jobId.result")
    );

    exec($cmd);

    // Retornar job_id para o frontend
    echo json_encode([
        'success' => true,
        'job_id' => $jobId,
        'message' => 'Job iniciado com sucesso'
    ]);
}

/**
 * Verificar status de um job
 */
function verificarStatus($jobId, $jobsDir) {
    // Validar job_id para evitar path traversal
    if (!preg_match('/^job_[a-f0-9.]+$/', $jobId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Job ID inválido']);
        return;
    }

    $jobFile = "$jobsDir/$jobId.json";
    $resultFile = "$jobsDir/$jobId.result";

    if (!file_exists($jobFile)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Job não encontrado']);
        return;
    }

    // Verificar se há resultado disponível
    if (file_exists($resultFile)) {
        $result = file_get_contents($resultFile);
        $resultData = json_decode($result, true);

        if ($resultData && isset($resultData['blocos'])) {
            // Atualizar status do job
            $jobData = json_decode(file_get_contents($jobFile), true);
            $jobData['status'] = 'completed';
            $jobData['completed_at'] = date('Y-m-d H:i:s');
            file_put_contents($jobFile, json_encode($jobData));

            // Limpar arquivos antigos (mais de 1 hora)
            cleanOldJobs($jobsDir);

            echo json_encode([
                'success' => true,
                'status' => 'completed',
                'result' => $resultData
            ]);
        } else {
            // Resultado com erro
            echo json_encode([
                'success' => false,
                'status' => 'failed',
                'error' => 'Erro na otimização: ' . ($result ?: 'Resposta vazia')
            ]);
        }
    } else {
        // Ainda processando
        echo json_encode([
            'success' => true,
            'status' => 'processing',
            'message' => 'Otimização em andamento...'
        ]);
    }
}

/**
 * Limpar jobs antigos (mais de 1 hora)
 */
function cleanOldJobs($jobsDir) {
    $files = glob("$jobsDir/job_*");
    $now = time();

    foreach ($files as $file) {
        if ($now - filemtime($file) > 3600) { // 1 hora
            unlink($file);
        }
    }
}
