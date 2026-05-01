<?php
/**
*@Author: OE源码网
*@CreateTime: 2025/8/6 上午12:56
*@email: 44697742@qq.com
*@blog: www.2oe.cn
*@Api: wzapi.com
*@tip: 短视频聚合解析
*/
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

function getProjectBasePath()
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(dirname($scriptDir), '/');
    return ($basePath === '.' || $basePath === '/') ? '' : $basePath;
}

function getCurrentBaseUrl()
{
    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = (!empty($https) && strtolower((string)$https) !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function buildLocalApiUrl($relativePath, $url)
{
    $basePath = getProjectBasePath();
    return getCurrentBaseUrl() . $basePath . $relativePath . urlencode($url);
}

function resolveRedirectUrl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return !empty($effectiveUrl) ? $effectiveUrl : $url;
}

// 输入验证与过滤
$url = $_REQUEST['url'] ?? null;
$url = trim($url ?? '');

// 检查URL是否为空或无效
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'code' => 400,
        'msg' => '请输入有效的链接'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 平台配置：统一管理匹配关键词和API地址
$platforms = [
    'douyin' => [
        'keywords' => ['douyin'],
        'api_url' => '/api/douyin/douyin.php?url='
    ],
    'kuaishou' => [
        'keywords' => ['kuaishou'],
        'api_url' => '/api/kuaishou/ksjx.php?url='
    ],
    'bilibili' => [
        'keywords' => ['bilibili', 'b23.tv'],
        'api_url' => '/api/bilibili/index.php?url='
    ],
    'pipixia' => [
        'keywords' => ['pipix'],
        'api_url' => '/api/ppxia.php?url='
    ],
    'pipigx' => [
        'keywords' => ['ippzone', 'pipigx'],
        'api_url' => '/api/pipigx.php?url='
    ],
    'weibo' => [
        'keywords' => ['weibo'],
        'api_url' => '/api/weibo.php?url='
    ],
    'xhs' => [
        'keywords' => ['xhs', 'xiaohongshu'],
        'api_url' => '/api/xiaohongshu/xhsjx.php?url='
    ]
];

// 查找匹配的平台
$matchedPlatform = null;
$normalizedUrl = resolveRedirectUrl($url);
$lowerUrl = strtolower($normalizedUrl);

foreach ($platforms as $platform => $config) {
    foreach ($config['keywords'] as $keyword) {
        if (strpos($lowerUrl, $keyword) !== false) {
            $matchedPlatform = $config;
            break 2; // 找到匹配项，跳出双层循环
        }
    }
}

// 处理请求
if ($matchedPlatform) {
    $apiUrl = buildLocalApiUrl($matchedPlatform['api_url'], $normalizedUrl);
    $response = requestUrl($apiUrl);

    if ($response !== false) {
        // 确保返回的是JSON格式
        if (isValidJson($response)) {
            echo $response;
        } else {
            echo json_encode([
                'code' => 500,
                'msg' => '接口返回格式不正确',
                'data' => $response
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'code' => 500,
            'msg' => '请求接口失败'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'code' => 201,
        'msg' => '不支持您输入的链接平台'
    ], JSON_UNESCAPED_UNICODE);
}
function requestUrl($url, $method = 'GET', $data = [])
{
    // 初始化cURL
    $ch = curl_init();

    // 设置URL
    curl_setopt($ch, CURLOPT_URL, $url);

    // 设置请求方法
    if (strtoupper($method) === 'POST' && !empty($data)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    // 设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // 不验证SSL证书（生产环境建议开启验证）
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // 返回响应内容而不直接输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // 执行请求并获取响应
    $response = curl_exec($ch);

    // 检查是否有错误
    if (curl_errno($ch)) {
        error_log('请求错误: ' . curl_error($ch));
        $response = false;
    }

    // 关闭cURL资源
    curl_close($ch);

    return $response;
}

/**
 * 验证字符串是否为有效的JSON
 * @param string $string 待验证的字符串
 * @return bool 是否为有效JSON
 */
function isValidJson($string)
{
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
?>
