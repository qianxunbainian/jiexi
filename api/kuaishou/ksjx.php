<?php
/**
 * @Author: OE源码网
 * @CreateTime: 2026/1/18 下午4:53
 * @email: 44697742@qq.com
 * @blog: www.2oe.cn
 * @Api: wzapi.com
 * @tip: 快手链接图片/视频信息提取工具
 */

require_once "KuaishouSpider.php";

// 跨域与响应头设置
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// 配置
define('USER_AGENT', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1 Edg/122.0.0.0');
define('JSON_OPTIONS', 480);

// 主程序逻辑
$url = $_GET['url'] ?? '';

if (empty($url)) {
    echo json_encode(['code' => 201, 'msg' => 'url为空', 'data' => []], JSON_OPTIONS);
} else {
    //务必填cookie
    $spider = new KuaishouSpider('', USER_AGENT);
    $result = $spider->analyze($url);
    // 统一适配 README.md 响应格式与字段
    if (!is_array($result)) {
        echo json_encode(['code' => 500, 'msg' => '解析失败', 'data' => []], JSON_OPTIONS);
        exit;
    }

    $code = (int)($result['code'] ?? 500);
    $msg = (string)($result['msg'] ?? '解析失败');
    $raw = (is_array($result['data'] ?? null)) ? $result['data'] : [];

    if ($code === 200) {
        $type = (string)($raw['type'] ?? 'unknown');
        if ($type !== 'video' && $type !== 'image' && $type !== 'live') {
            $type = 'unknown';
        }

        $normalized = [
            'type' => $type,
            'title' => (string)($raw['title'] ?? ''),
            'desc' => (string)($raw['desc'] ?? ($raw['title'] ?? '')),
            'author' => [
                'name' => (string)($raw['author'] ?? ''),
                'id' => (string)($raw['author_id'] ?? ''),
                'avatar' => (string)($raw['avatar'] ?? ''),
            ],
            'cover' => (string)($raw['cover'] ?? ''),
            'url' => $type === 'video' ? ((string)($raw['url'] ?? '')) : null,
            'duration' => isset($raw['duration']) ? (int)$raw['duration'] : null,
            'video_backup' => is_array($raw['video_backup'] ?? null) ? $raw['video_backup'] : [],
            'images' => is_array($raw['images'] ?? null) ? $raw['images'] : [],
            'live_photo' => is_array($raw['live_photo'] ?? null) ? $raw['live_photo'] : [],
            'music' => [
                'title' => (string)($raw['music']['name'] ?? ($raw['music']['title'] ?? '')),
                'author' => (string)($raw['music']['artist'] ?? ($raw['music']['author'] ?? '')),
                'url' => $raw['music']['url'] ?? null,
                'cover' => (string)($raw['music']['cover'] ?? ''),
            ],
            'video_id' => (string)($raw['video_id'] ?? ''),
            '_raw' => $raw,
        ];

        $result = ['code' => 200, 'msg' => $msg ?: '解析成功', 'data' => $normalized];
    } else {
        $result = ['code' => $code, 'msg' => $msg, 'data' => []];
    }

    echo json_encode($result, JSON_OPTIONS);
}
