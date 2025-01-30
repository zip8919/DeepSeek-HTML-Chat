<?php
// 第一阶段：安全缓冲区控制
while (ob_get_level() > 0) {
    if (!ob_end_clean()) break;
}
header_remove();
header('Content-Type: application/json; charset=utf-8');

// 第二阶段：错误报告配置
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// 第三阶段：配置中心
const CONFIG = [
    'api_token'    => '填写此处',
    'account_id'   => '填写此处',
    'model'        => '@cf/deepseek-ai/deepseek-r1-distill-qwen-32b', //使用的模型
    'timeout'      => 25,  // 总超时时间(秒)
    'max_length'   => 10000 // 用户输入最大长度
];

// 第四阶段：主逻辑
try {
    // 输入验证三重检查
    $input = @file_get_contents('php://input');
    if ($input === false || strlen($input) === 0) {
        throw new Exception("请求内容不可读");
    }

    $data = json_decode($input, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
    $message = trim($data['message'] ?? '');

    // 内容安全检查
    if (empty($message)) {
        throw new Exception("消息内容不能为空");
    }
    if (strlen($message) > CONFIG['max_length']) {
        throw new Exception("输入内容过长");
    }

    // API请求配置
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
            "messages" => [
                ["role" => "system", "content" => ""],
                ["role" => "user", "content" => $message]
            ],
            "temperature" => 0.7,
            "max_tokens"  => 1000
        ], JSON_THROW_ON_ERROR),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => CONFIG['timeout'],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => 'gzip' // 自动解压响应
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // HTTP状态码验证
    if ($httpCode !== 200) {
        throw new Exception(sprintf(
            "API请求失败 [HTTP %d] %s",
            $httpCode,
            substr($response, 0, 200)
        ));
    }

    // 内容类型验证
    if (stripos($contentType, 'application/json') === false) {
        throw new Exception("非JSON响应: " . substr($response, 0, 200));
    }

    // 响应解析
    $responseData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    
    if (!isset($responseData['result']['response'])) {
        error_log("异常响应结构: " . print_r($responseData, true));
        throw new Exception("API响应结构异常");
    }

    // 敏感内容过滤示例
    $filteredResponse = htmlspecialchars($responseData['result']['response'], ENT_QUOTES);

    // 构建最终输出
    $output = [
        'success' => true,
        'reply'   => $filteredResponse,
        'meta'    => [
            'model'     => CONFIG['model'],
            'timestamp' => time()
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

// 第五阶段：错误处理函数
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
        // 终极fallback
        echo '{"success":false,"error":"系统内部错误"}';
    }
    exit;
}
