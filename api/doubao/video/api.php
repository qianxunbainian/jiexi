<?php
/**
 *@Author: OE源码网
 *@CreateTime: 2026/4/24 17:17
 *@email: 44697742@qq.com
 *@blog: www.2oe.cn
 *@Api: wzapi.com
 *@tip: 豆包ai视频无水印解析（非对话生成的视频）-入口文件
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/DoubaoParser.php';

$url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? file_get_contents('php://input');
    if (is_string($url) && json_decode($url)) {
        $json = json_decode($url, true);
        $url = $json['url'] ?? '';
    }
} else {
    // 处理GET请求时的长链参数
    $url = '';
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    
    // 查找url参数的位置
    $urlPos = strpos($queryString, 'url=');
    if ($urlPos !== false) {
        // 提取从url=开始的部分
        $urlPart = substr($queryString, $urlPos);
        // 移除前面的url=前缀
        $urlEncoded = substr($urlPart, 4);
        // 解码URL
        $url = urldecode($urlEncoded);
    } else {
        $url = $_GET['url'] ?? '';
    }
}

if (empty($url)) {
    echo json_encode([
        'code' => 400,
        'msg' => '请提供url参数',
        'data' => []
    ], 480);
    exit;
}

$parser = new DoubaoParser();
$result = $parser->parse($url);

echo $result;