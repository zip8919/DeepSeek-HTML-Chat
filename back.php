<?php
while (ob_get_level() > 0) {
    if (!ob_end_clean()) break;
}
header_remove();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

const CONFIG = [
    'api_token'    => '待填写',
    'account_id'   => '待填写',
    'model'        => '@cf/deepseek-ai/deepseek-r1-distill-qwen-32b',
    'timeout'      => 50,
    'max_length'   => 10000,
    'max_history'  => 6,
    'max_context'  => 4096
];

try {
    $input = @file_get_contents('php://input');
    if ($input === false || strlen($input) === 0) {
        throw new Exception("请求内容不可读");
    }

    $data = json_decode($input, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
    $message = trim($data['message'] ?? '');
    $history = $data['history'] ?? [];

    if (!is_array($history)) {
        throw new Exception("无效的历史数据格式");
    }

    if (empty($message)) {
        throw new Exception("消息内容不能为空");
    }
    if (strlen($message) > CONFIG['max_length']) {
        throw new Exception("输入内容过长");
    }

    $messagesChain = [
        ["role" => "system", "content" => "回答内容与思考过程不要重复"]
    ];

    foreach ($history as $record) {
        if (isset($record['role'], $record['content']) &&
            in_array($record['role'], ['user', 'assistant'])) 
        {
            $cleanContent = htmlspecialchars(
                mb_substr($record['content'], 0, CONFIG['max_length'], 'UTF-8'),
                ENT_QUOTES,
                'UTF-8'
            );
            $messagesChain[] = [
                'role' => $record['role'],
                'content' => $cleanContent
            ];
        }
    }

    $currentMessage = mb_substr($message, 0, CONFIG['max_length'], 'UTF-8');
    $messagesChain[] = ["role" => "user", "content" => $currentMessage];

    $initialCount = count($messagesChain);
    $totalToken = 0;
    foreach ($messagesChain as $msg) {
        $totalToken += ceil(mb_strlen($msg['content'], 'UTF-8') * 0.75);
    }

    $cutStart = 1;
    while ($totalToken > CONFIG['max_context'] && count($messagesChain) > $cutStart) {
        $removedPair = array_splice($messagesChain, $cutStart, 2);
        foreach ($removedPair as $msg) {
            $totalToken -= ceil(mb_strlen($msg['content'], 'UTF-8') * 0.75);
        }
    }

    $ch = curl_init();
    $endpoint = sprintf(
        "https://api.cloudflare.com/client/v4/accounts/%s/ai/run/%s",
        CONFIG['account_id'],
        CONFIG['model']
    );

    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . CONFIG['api_token'],
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messages" => $messagesChain,
            "temperature" => 0.7,
            "max_tokens"  => 1000
        ], JSON_THROW_ON_ERROR),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => CONFIG['timeout'],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => 'gzip'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($httpCode !== 200) {
        throw new Exception(sprintf(
            "API请求失败 [HTTP %d] %s",
            $httpCode,
            substr($response, 0, 200)
        ));
    }

    if (stripos($contentType, 'application/json') === false) {
        throw new Exception("非JSON响应: " . substr($response, 0, 200));
    }

    $responseData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    
    if (!isset($responseData['result']['response'])) {
        error_log("异常响应结构: " . print_r($responseData, true));
        throw new Exception("API响应结构异常");
    }

    $filteredResponse = htmlspecialchars($responseData['result']['response'], ENT_QUOTES);

    $output = [
        'success' => true,
        'reply'   => $filteredResponse,
        'meta'    => [
            'model'     => CONFIG['model'],
            'timestamp' => time(),
            'context'   => [
                'used' => count($messagesChain) - 1,
                'cut'  => $initialCount - count($messagesChain)
            ]
        ]
    ];

    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (JsonException $e) {
    error_log("JSON处理失败: " . $e->getMessage());
    deliver_error("数据格式错误", 400);
} catch (Exception $e) {
    error_log("系统错误: " . $e->getMessage());
    deliver_error($e->getMessage(), 500);
} finally {
    if (isset($ch) && is_resource($ch)) {
        curl_close($ch);
    }
    exit;
}

function deliver_error(string $message, int $code = 500): void {
    http_response_code($code);
    $output = [
        'success' => false,
        'error'   => $message,
        'code'    => $code
    ];
    
    try {
        echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        echo '{"success":false,"error":"系统内部错误"}';
    }
    exit;
}

