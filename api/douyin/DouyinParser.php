<?php
/**
 * @Author: JH-Ahua
 * @CreateTime: 2026/2/12 下午9:47
 * @email: admin@bugpk.com
 * @blog: www.jiuhunwl.cn
 * @Api: api.bugpk.com
 * @tip: 整合视频、图文、图集、实况解析
 */

class DouyinParser
{
    private $headers;
    private $cookie;
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';
    private $ttwid = null;

    public function __construct()
    {
        $this->headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        ];
        // 默认 Cookie，可通过 setCookie 方法覆盖
        $this->cookie = "";
    }

    /**
     * 设置Cookie
     */
    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * 统一输出函数
     */
    private function output($code, $msg, $data = [])
    {
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], 480);
    }

    /**
     * 发送HTTP请求
     */
    private function request($url, $customHeaders = [], $returnHeader = false, $method = 'GET', $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $headers = array_merge($this->headers, $customHeaders);
        if ($this->cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($returnHeader) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }
        return $response;
    }

    /**
     * 获取重定向后的真实链接
     */
    private function getRealUrl($url)
    {
        // 方案一：优先使用 get_headers
        stream_context_set_default([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: " . $this->userAgent
            ]
        ]);

        $headers = @get_headers($url, 1);

        if (isset($headers['Location'])) {
            $location = $headers['Location'];
            if (is_array($location)) {
                // 优先寻找包含 video/note/modal_id 等特征的链接
                foreach ($location as $loc) {
                    if ($this->extractId($loc)) {
                        return $loc;
                    }
                }
                return $location[0];
            }
            return $location;
        }

        // 方案二：cURL 备选
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_exec($ch);
        $realUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $realUrl ?: $url;
    }

    /**
     * 提取ID
     */
    private function extractId($url)
    {
        $parsed = @parse_url($url);
        if (is_array($parsed) && !empty($parsed['query'])) {
            parse_str($parsed['query'], $qs);
            foreach (['vid', 'id', 'modal_id', 'v', 's', 'pid'] as $key) {
                if (!empty($qs[$key])) {
                    return (string)$qs[$key];
                }
            }
        }

        // 匹配 URL 中的数字 ID (通常是 video/xxx 或 modal_id=xxx)
        if (preg_match('/\/video\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/modal_id=(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/note\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        // 尝试匹配纯数字 (防止某些短链解开后直接是ID)
        if (preg_match('/^(\d+)$/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/note\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        // 匹配 share/slides/xxx (新增)
        if (preg_match('/\/share\/slides\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        // 匹配 share/video/xxx (新增)
        if (preg_match('/\/share\/video\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        // 兼容 .html 结尾或最后一段为 ID（包含非数字的情况）
        if (isset($parsed['path'])) {
            $parts = array_values(array_filter(explode('/', $parsed['path'])));
            $last = end($parts);
            if (is_string($last) && $last !== '') {
                if (substr($last, -5) === '.html') {
                    $last = substr($last, 0, -5);
                }
                if ($last !== '') {
                    return $last;
                }
            }
        }

        return null;
    }

    /**
     * 获取 ttwid Cookie（用于 web detail 接口）
     */
    private function getTtwid()
    {
        if ($this->ttwid) {
            return $this->ttwid;
        }

        $payload = json_encode([
            'region' => 'cn',
            'aid' => 6383,
            'need_t' => 1,
            'service' => 'www.douyin.com',
            'migrate_priority' => 0,
            'cb_url_protocol' => 'https',
            'domain' => '.douyin.com',
        ], JSON_UNESCAPED_UNICODE);

        $resp = $this->request(
            'https://ttwid.bytedance.com/ttwid/union/register/',
            [
                'Content-Type: application/json',
                'Accept: application/json, text/plain, */*',
            ],
            true,
            'POST',
            $payload
        );

        if (!$resp) {
            return null;
        }

        if (preg_match('/\bttwid=([^;\s]+)/i', $resp, $m)) {
            $this->ttwid = urldecode($m[1]);
            return $this->ttwid;
        }

        return null;
    }

    private function randomMsToken($length = 107)
    {
        $base = 'ABCDEFGHIGKLMNOPQRSTUVWXYZabcdefghigklmnopqrstuvwxyz0123456789=';
        $out = '';
        $max = strlen($base) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= $base[random_int(0, $max)];
        }
        return $out;
    }

    /**
     * 调用 web detail 接口（workers.js 同款：msToken + a_bogus + ttwid）
     */
    private function fetchAwemeDetail($awemeId)
    {
        $refererBase = 'https://www.douyin.com/video/' . $awemeId;
        // 预热一次 referer（失败也无所谓）
        $this->request($refererBase . '?previous_page=web_code_link', [
            'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $refererBase . '?previous_page=web_code_link',
        ]);

        $ttwid = $this->getTtwid();
        if (!$ttwid) {
            // workers.js 里的兜底值（不一定可用，但总比没有好）
            $ttwid = '1%7CvDWCB8tYdKPbdOlqwNTkDPhizBaV9i91KjYLKJbqurg%7C1723536402%7C314e63000decb79f46b8ff255560b29f4d8c57352dad465b41977db4830b4c7e';
        }

        $msToken = $this->randomMsToken(107);
        $params = [
            'device_platform' => 'webapp',
            'aid' => '6383',
            'channel' => 'channel_pc_web',
            'aweme_id' => (string)$awemeId,
            'msToken' => $msToken,
        ];

        $query = http_build_query($params);
        $aBogus = $this->generateABogus($query, $this->userAgent);
        if (!$aBogus) {
            return [false, 'a_bogus 签名失败', null];
        }

        $finalUrl = 'https://www.douyin.com/aweme/v1/web/aweme/detail/?' . $query . '&a_bogus=' . rawurlencode($aBogus);

        $resp = $this->request($finalUrl, [
            'Accept: application/json, text/plain, */*',
            'Referer: ' . $refererBase . '?previous_page=web_code_link',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'Cookie: ttwid=' . $ttwid,
        ]);
        if (!$resp) {
            return [false, '请求失败', null];
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            return [false, '详情接口返回非 JSON（可能被 WAF 返回 HTML 或编码异常）', null];
        }
        if (empty($json['aweme_detail'])) {
            $msg = $json['status_msg'] ?? ($json['statusMsg'] ?? '');
            if (!$msg && isset($json['status_code'])) {
                $msg = 'status_code=' . $json['status_code'] . '，无 aweme_detail';
            }
            return [false, $msg ?: '接口未返回 aweme_detail', null];
        }

        return [true, null, $json['aweme_detail']];
    }

    /**
     * 主解析方法
     */
    public function parse($url)
    {
        if (empty($url)) {
            return $this->output(400, '请输入抖音链接');
        }

        // 预处理域名
        $domain = parse_url($url, PHP_URL_HOST);
        // 如果是短链接域名或不包含 video/modal_id 等特征，尝试获取重定向链接
        if ($domain == 'v.douyin.com' || strpos($url, 'douyin.com') === false || !$this->extractId($url)) {
            $url = $this->getRealUrl($url);
        }

        $id = $this->extractId($url);
        if (!$id) {
            return $this->output(400, '链接格式错误，无法提取ID。处理后的链接: ' . $url);
        }

        // 优先走 web detail 接口（更稳定，支持视频/图文/实况）
        [$ok, $reason, $detail] = $this->fetchAwemeDetail($id);
        if ($ok && $detail) {
            return $this->formatData($detail);
        }

        // 兜底：旧的页面解析（可能需要 Cookie）
        $apiUrl = 'https://www.douyin.com/user/self?modal_id=' . $id . '&showTab=like';
        $response = $this->request($apiUrl);
        if ($response) {
            $data = $this->extractJson($response);
            if ($data) {
                return $this->formatData($data);
            }
        }

        if ($reason) {
            return $this->output(500, '请求失败（' . $reason . '）');
        }
        return $this->output(404, '解析失败，未找到有效内容');
    }

    /**
     * 提取并解析 JSON 数据
     */
    private function extractJson($html)
    {
        $startStr = '<script id="RENDER_DATA" type="application/json">';
        $endStr = '</script>';

        $posStart = strpos($html, $startStr);
        if ($posStart === false) {
            // 尝试另一种模式 (douyin.php 中的模式)
            $pattern = '/window\._ROUTER_DATA\s*=\s*(.*?)\<\/script>/s';
            if (preg_match($pattern, $html, $matches)) {
                $json = json_decode($matches[1], true);
                if (isset($json['loaderData'])) {
                    // 需要根据 loaderData 结构提取 videoDetail
                    // 这里的 key 可能是动态的，如 video_(id)/page
                    foreach ($json['loaderData'] as $key => $value) {
                        if (strpos($key, 'video_') === 0 && isset($value['videoInfoRes']['item_list'][0])) {
                            return $value['videoInfoRes']['item_list'][0];
                        }
                    }
                }
            }
            return null;
        }

        $jsonStr = substr($html, $posStart + strlen($startStr));
        $posEnd = strpos($jsonStr, $endStr);
        if ($posEnd === false) {
            return null;
        }

        $jsonStr = substr($jsonStr, 0, $posEnd);
        $jsonStr = urldecode($jsonStr); // 抖音 RENDER_DATA 通常经过 URL 编码
        $data = json_decode($jsonStr, true);

        if (isset($data['app']['videoDetail'])) {
            return $data['app']['videoDetail'];
        }

        return null;
    }

    /**
     * 格式化数据 (统一为小红书格式)
     */
    private function formatData($detail)
    {
        $pickFirstUrl = function ($node, array $paths) {
            foreach ($paths as $path) {
                $cur = $node;
                $ok = true;
                foreach (explode('.', $path) as $seg) {
                    if (is_array($cur) && array_key_exists($seg, $cur)) {
                        $cur = $cur[$seg];
                    } else {
                        $ok = false;
                        break;
                    }
                }
                if (!$ok) continue;

                // 支持 urlList/url_list 这种数组结构
                if (is_array($cur)) {
                    $v = $cur[0] ?? null;
                    if (is_string($v) && $v !== '') return $v;
                } elseif (is_string($cur) && $cur !== '') {
                    return $cur;
                }
            }
            return '';
        };

        $musicUrl = $pickFirstUrl($detail, [
            'music.playUrl.urlList',
            'music.play_url.url_list',
            'music.play_url.urlList',
            'music.playUrl.url_list',
        ]);
        if ($musicUrl === '') {
            // 某些结构只给 uri（非直链），为了保持字段语义为“可访问 URL”，这里置空。
            $musicUrl = null;
        }

        $result = [
            'type' => 'unknown',
            'title' => $detail['desc'] ?? '',
            'desc' => $detail['desc'] ?? '',
            'author' => [
                'name' => $detail['authorInfo']['nickname'] ?? ($detail['author']['nickname'] ?? ''),
                'id' => $detail['authorInfo']['uid'] ?? ($detail['author']['uid'] ?? ''),
                'avatar' => $pickFirstUrl($detail, [
                    'authorInfo.avatarThumb.urlList',
                    'author.avatar_thumb.url_list',
                    'authorInfo.avatar_thumb.url_list',
                    'authorInfo.avatarUri',
                ]),
            ],
            'cover' => '',
            'url' => null, // 视频链接
            'duration' => $detail['video']['duration'] ?? null,
            'video_backup' => [],
            'images' => [],
            'live_photo' => [],
            'music' => [
                'title' => $detail['music']['musicName'] ?? ($detail['music']['title'] ?? ''),
                'author' => $detail['music']['ownerNickname'] ?? ($detail['music']['author'] ?? ''),
                'url' => $musicUrl,
                'cover' => $pickFirstUrl($detail, [
                    'music.coverThumb.urlList',
                    'music.cover_thumb.url_list',
                    'music.coverThumb.url_list',
                    'music.cover_thumb.urlList',
                ]),
            ],
            // README 约定字段：保持始终存在，非视频时留空字符串
            'video_id' => '',
            // 扩展字段：点赞/评论/收藏/分享/播放等
            'statistics' => [
                'digg_count' => 0,
                'comment_count' => 0,
                'collect_count' => 0,
                'share_count' => 0,
            ],
            // 扩展字段：作者粉丝/关注/作品等（能拿到就填，拿不到为 0）
            'author_stats' => [
                'follower_count' => 0,
                'total_favorited' => 0,
            ],
        ];

        // 统计数据（aweme_detail 通常有 statistics）
        if (isset($detail['statistics']) && is_array($detail['statistics'])) {
            $s = $detail['statistics'];
            $result['statistics'] = [
                'digg_count' => (int)($s['digg_count'] ?? 0),
                'comment_count' => (int)($s['comment_count'] ?? 0),
                'collect_count' => (int)($s['collect_count'] ?? 0),
                'share_count' => (int)($s['share_count'] ?? 0),
            ];
        }

        // 作者统计（字段名在不同返回结构里可能不同，尽量兼容）
        $authorNode = null;
        if (isset($detail['author']) && is_array($detail['author'])) {
            $authorNode = $detail['author'];
        } elseif (isset($detail['authorInfo']) && is_array($detail['authorInfo'])) {
            $authorNode = $detail['authorInfo'];
        }
        if ($authorNode) {
            $result['author_stats'] = [
                'follower_count' => (int)($authorNode['follower_count'] ?? ($authorNode['followers_count'] ?? 0)),
                'total_favorited' => (int)($authorNode['total_favorited'] ?? ($authorNode['favorited_count'] ?? 0)),
            ];
        }

        // 提取封面 (尝试多种字段)
        $cover = '';
        // 1. 尝试 originCover (原图封面)
        if (isset($detail['video']['originCover']['urlList'][0])) {
            $cover = $detail['video']['originCover']['urlList'][0];
        } elseif (isset($detail['video']['origin_cover']['url_list'][0])) {
            $cover = $detail['video']['origin_cover']['url_list'][0];
        } elseif (isset($detail['video']['originCover'])) {
            $cover = $detail['video']['originCover'];
        } elseif (isset($detail['video']['originCoverUrlList'][0])) {
            $cover = $detail['video']['originCoverUrlList'][0];
        }

        // 2. 尝试 cover (普通封面)
        if (!$cover) {
            // 某些情况下结构可能是 cover.url_list，也可能是 cover.urlList
            $cover = $detail['video']['cover']['urlList'][0] ?? ($detail['video']['cover']['url_list'][0] ?? '');

            // 如果 cover 是字符串 (直接是 URL)
            if (!$cover && isset($detail['video']['cover']) && is_string($detail['video']['cover'])) {
                $cover = $detail['video']['cover'];
            }
        }

        // 补充：检查是否直接在 detail.cover 字段 (某些图文类型)
        if (!$cover && isset($detail['cover']['url_list'][0])) {
            $cover = $detail['cover']['url_list'][0];
        }

        // 3. 尝试 dynamicCover (动态封面)
        if (!$cover) {
            $cover = $detail['video']['dynamicCover']['urlList'][0] ?? ($detail['video']['dynamic_cover']['url_list'][0] ?? '');
        }

        // 4. 尝试 douyin.php 中的路径逻辑 (针对 loaderData/videoInfoRes 结构)
        if (!$cover && isset($detail['videoInfoRes']['item_list'][0]['video']['cover']['url_list'][0])) {
            $cover = $detail['videoInfoRes']['item_list'][0]['video']['cover']['url_list'][0];
        }

        $result['cover'] = $cover;

        // 判断类型和提取资源
        $images = $detail['images'] ?? [];
        if (!empty($images)) {
            // 图文/图集/实况
            $result['type'] = 'image';

            foreach ($images as $img) {
                // 提取图片 URL
                $imgUrl = $img['urlList'][0] ?? ($img['url_list'][0] ?? '');
                if ($imgUrl) {
                    $result['images'][] = $imgUrl;
                }

                // 提取实况视频 (Live Photo)
                // 抖音实况通常在 images 列表的 item 中包含 video 字段 (与普通图文不同)
                $liveVideoUrl = null;
                $videoInfo = $img['video'] ?? [];

                // 1. 尝试 playAddr (对象数组结构，如 dylive.json)
                if (isset($videoInfo['playAddr']) && is_array($videoInfo['playAddr'])) {
                    $liveVideoUrl = null;
                    $v26Candidate = null;
                    // 优先匹配包含 v3-web 的链接
                    foreach ($videoInfo['playAddr'] as $addr) {
                        if (isset($addr['src'])) {
                            if (strpos($addr['src'], 'v3-web') !== false) {
                                $liveVideoUrl = $addr['src'];
                                break;
                            }
                            if (strpos($addr['src'], 'v26-web') !== false) {
                                $v26Candidate = $addr['src'];
                            }
                        }
                    }

                    if (!$liveVideoUrl && $v26Candidate) {
                        $liveVideoUrl = preg_replace('/:\/\/([^\/]+)/', '://v26-luna.douyinvod.com', $v26Candidate);
                    }

                    // 没找到 v3-web，则回退到备用逻辑 (优先取第二个，没有则第一个)
                    if (!$liveVideoUrl) {
                        $liveVideoUrl = $videoInfo['playAddr'][1]['src'] ?? ($videoInfo['playAddr'][0]['src'] ?? null);
                    }
                }

                // 2. 尝试 play_addr.url_list (字符串数组结构)
                if (!$liveVideoUrl && isset($videoInfo['play_addr']['url_list'])) {
                    $urlList = $videoInfo['play_addr']['url_list'];
                    $v26Candidate = null;
                    // 优先匹配包含 v3-web 的链接
                    foreach ($urlList as $url) {
                        if (strpos($url, 'v3-web') !== false) {
                            $liveVideoUrl = $url;
                            break;
                        }
                        if (strpos($url, 'v26-web') !== false) {
                            $v26Candidate = $url;
                        }
                    }

                    if (!$liveVideoUrl && $v26Candidate) {
                        $liveVideoUrl = preg_replace('/:\/\/([^\/]+)/', '://v26-luna.douyinvod.com', $v26Candidate);
                    }

                    // 没找到 v3-web，则回退到备用逻辑
                    if (!$liveVideoUrl) {
                        $liveVideoUrl = $urlList[1] ?? ($urlList[0] ?? null);
                    }
                }

                // 3. 尝试 playApi
                if (!$liveVideoUrl) {
                    $liveVideoUrl = $videoInfo['playApi'] ?? null;
                }

                if ($liveVideoUrl) {
                    $liveVideoUrl = str_replace('playwm', 'play', $liveVideoUrl);
                    $result['live_photo'][] = [
                        'image' => $imgUrl,
                        'video' => $liveVideoUrl
                    ];
                }
            }

            // 如果提取到了实况视频，修正类型为实况
            if (!empty($result['live_photo'])) {
                $result['type'] = 'live';
            }
        } else {
            // 视频
            $result['type'] = 'video';

            // 使用新逻辑提取最高画质视频
            $videoInfo = $this->extractHighestQualityVideo($detail);
            $result['url'] = $videoInfo['url'];
            $result['video_backup'] = is_array($videoInfo['backup'] ?? null) ? $videoInfo['backup'] : [];
            $result['video_id'] = (string)($detail['aweme_id'] ?? ($detail['awemeId'] ?? ($detail['awemeID'] ?? ($detail['awemeIdStr'] ?? ($detail['video']['id'] ?? ($detail['video']['uri'] ?? ''))))));
        }

        return $this->output(200, '解析成功', $result);
    }

    /**
     * 提取最高画质视频链接
     */
    private function extractHighestQualityVideo($detail)
    {
        $url = null;
        $backup = [];

        // 尝试从 bitRateList 中提取
        if (isset($detail['video']['bitRateList']) && is_array($detail['video']['bitRateList'])) {
            $bitRateList = $detail['video']['bitRateList'];

            // 按 bitRate 降序排序
            usort($bitRateList, function ($a, $b) {
                return ($b['bitRate'] ?? 0) - ($a['bitRate'] ?? 0);
            });

            // 遍历寻找合适的链接
            foreach ($bitRateList as $rateItem) {
                $playAddr = $rateItem['playAddr'][0]['src'] ?? ($rateItem['play_addr']['url_list'][0] ?? null);
                if ($playAddr) {
                    // 检查是否包含 v3-web 域名 (通常更稳定)
                    // 如果 playAddr 是数组，尝试找到 v3-web 的链接
                    $candidates = [];
                    if (isset($rateItem['playAddr']) && is_array($rateItem['playAddr'])) {
                        foreach ($rateItem['playAddr'] as $pa) {
                            if (isset($pa['src'])) $candidates[] = $pa['src'];
                        }
                    } elseif (isset($rateItem['play_addr']['url_list'])) {
                        $candidates = $rateItem['play_addr']['url_list'];
                    }

                    if (empty($candidates)) continue;

                    // 1. 在当前画质中选择最佳 URL
                    $currentBestUrl = null;
                    $v3Link = null;
                    $v26Link = null;

                    foreach ($candidates as $candidate) {
                        if (strpos($candidate, 'v3-web') !== false) {
                            $v3Link = $candidate;
                            break; // 找到 v3 优先使用
                        }
                        if (strpos($candidate, 'v26-web') !== false) {
                            $v26Link = $candidate;
                        }
                    }

                    if ($v3Link) {
                        $currentBestUrl = $v3Link;
                    } elseif ($v26Link) {
                        $currentBestUrl = preg_replace('/:\/\/([^\/]+)/', '://v26-luna.douyinvod.com', $v26Link);
                    } else {
                        $currentBestUrl = $candidates[0];
                    }

                    // 2. 如果全局 URL 尚未设置，使用当前最佳
                    if (!$url) {
                        $url = $currentBestUrl;
                    }

                    // 3. 将所有非主 URL 的链接加入备用
                    foreach ($candidates as $candidate) {
                        // 如果是 v26 链接，也进行域名替换，保持一致性
                        if (strpos($candidate, 'v26-web') !== false) {
                            $candidate = preg_replace('/:\/\/([^\/]+)/', '://v26-luna.douyinvod.com', $candidate);
                        }

                        // 排除已选用的主 URL
                        if ($candidate !== $url && !in_array($candidate, $backup)) {
                            $backup[] = $candidate;
                        }
                    }
                }

                if ($url && !empty($backup)) break; // 找到主备链接后停止
            }
        }

        // 如果 bitRateList 没找到，尝试旧逻辑
        if (!$url) {
            $uri = $detail['video']['uri'] ?? '';
            $playApi = $detail['video']['playApi'] ?? ($detail['video']['play_addr']['url_list'][0] ?? '');

            if ($playApi) {
                $url = str_replace('playwm', 'play', $playApi);
            } elseif ($uri) {
                $url = 'https://aweme.snssdk.com/aweme/v1/play/?video_id=' . $uri . '&ratio=720p&line=0';
            }

            // 备用
            $urlList = $detail['video']['play_addr']['url_list'] ?? [];
            if (count($urlList) > 1) {
                foreach ($urlList as $index => $link) {
                    if ($index === 0) continue;
                    $backup[] = str_replace('playwm', 'play', $link);
                }
            }
        }

        return ['url' => $url, 'backup' => $backup];
    }

    private function toHttps($url)
    {
        if (!$url) return null;
        if (strpos($url, 'http://') === 0) {
            return 'https://' . substr($url, 7);
        }
        return $url;
    }

    private function rc4Encrypt($plaintext, $key)
    {
        $s = range(0, 255);
        $j = 0;
        $keyLen = strlen($key);
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % $keyLen])) % 256;
            $tmp = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $tmp;
        }

        $i = 0;
        $j = 0;
        $out = '';
        $len = strlen($plaintext);
        for ($k = 0; $k < $len; $k++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $tmp = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $tmp;
            $t = ($s[$i] + $s[$j]) % 256;
            $out .= chr($s[$t] ^ ord($plaintext[$k]));
        }
        return $out;
    }

    private function sm3Hex($input)
    {
        // PHP 自带 sm3 优先，否则使用纯 PHP 实现
        if (in_array('sm3', hash_algos(), true)) {
            return hash('sm3', $input);
        }
        return $this->sm3HexPure($input);
    }

    private function sm3HexPure($input)
    {
        $msg = $this->toBytesUtf8($input);
        $len = count($msg);
        $bitLen = $len * 8;
        $msg[] = 0x80;
        while ((count($msg) % 64) !== 56) {
            $msg[] = 0x00;
        }
        // 64-bit big endian length
        $high = intdiv($bitLen, 0x100000000);
        $low = $bitLen & 0xffffffff;
        foreach ([$high, $low] as $v) {
            $msg[] = ($v >> 24) & 0xff;
            $msg[] = ($v >> 16) & 0xff;
            $msg[] = ($v >> 8) & 0xff;
            $msg[] = ($v) & 0xff;
        }

        $iv = [
            0x7380166f,
            0x4914b2b9,
            0x172442d7,
            0xda8a0600,
            0xa96f30bc,
            0x163138aa,
            0xe38dee4d,
            0xb0fb0e4e,
        ];

        for ($offset = 0; $offset < count($msg); $offset += 64) {
            $b = array_slice($msg, $offset, 64);
            $w = array_fill(0, 68, 0);
            $w1 = array_fill(0, 64, 0);
            for ($i = 0; $i < 16; $i++) {
                $w[$i] = (($b[4 * $i] << 24) | ($b[4 * $i + 1] << 16) | ($b[4 * $i + 2] << 8) | ($b[4 * $i + 3])) & 0xffffffff;
            }
            for ($i = 16; $i < 68; $i++) {
                $x = ($w[$i - 16] ^ $w[$i - 9] ^ $this->rotl($w[$i - 3], 15)) & 0xffffffff;
                $p1 = ($x ^ $this->rotl($x, 15) ^ $this->rotl($x, 23)) & 0xffffffff;
                $w[$i] = ($p1 ^ $this->rotl($w[$i - 13], 7) ^ $w[$i - 6]) & 0xffffffff;
            }
            for ($i = 0; $i < 64; $i++) {
                $w1[$i] = ($w[$i] ^ $w[$i + 4]) & 0xffffffff;
            }

            [$a, $b0, $c, $d, $e, $f, $g, $h] = $iv;
            for ($j = 0; $j < 64; $j++) {
                $tj = ($j < 16) ? 0x79cc4519 : 0x7a879d8a;
                $ss1 = $this->rotl((($this->rotl($a, 12) + $e + $this->rotl($tj, $j)) & 0xffffffff), 7);
                $ss2 = ($ss1 ^ $this->rotl($a, 12)) & 0xffffffff;
                $ff = ($j < 16) ? (($a ^ $b0 ^ $c) & 0xffffffff) : ((($a & $b0) | ($a & $c) | ($b0 & $c)) & 0xffffffff);
                $gg = ($j < 16) ? (($e ^ $f ^ $g) & 0xffffffff) : ((($e & $f) | ((~$e) & $g)) & 0xffffffff);
                $tt1 = ($ff + $d + $ss2 + $w1[$j]) & 0xffffffff;
                $tt2 = ($gg + $h + $ss1 + $w[$j]) & 0xffffffff;
                $d = $c;
                $c = $this->rotl($b0, 9);
                $b0 = $a;
                $a = $tt1;
                $h = $g;
                $g = $this->rotl($f, 19);
                $f = $e;
                $p0 = ($tt2 ^ $this->rotl($tt2, 9) ^ $this->rotl($tt2, 17)) & 0xffffffff;
                $e = $p0;
            }

            $iv = [
                ($iv[0] ^ $a) & 0xffffffff,
                ($iv[1] ^ $b0) & 0xffffffff,
                ($iv[2] ^ $c) & 0xffffffff,
                ($iv[3] ^ $d) & 0xffffffff,
                ($iv[4] ^ $e) & 0xffffffff,
                ($iv[5] ^ $f) & 0xffffffff,
                ($iv[6] ^ $g) & 0xffffffff,
                ($iv[7] ^ $h) & 0xffffffff,
            ];
        }

        $hex = '';
        foreach ($iv as $v) {
            $hex .= str_pad(dechex($v & 0xffffffff), 8, '0', STR_PAD_LEFT);
        }
        return $hex;
    }

    private function toBytesUtf8($str)
    {
        if (function_exists('mb_convert_encoding')) {
            $utf8 = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        } elseif (function_exists('iconv')) {
            $utf8 = iconv('UTF-8', 'UTF-8//IGNORE', $str);
            if ($utf8 === false) $utf8 = (string)$str;
        } else {
            // 退化：假设输入已经是 UTF-8 字节串
            $utf8 = (string)$str;
        }
        $bytes = [];
        $len = strlen($utf8);
        for ($i = 0; $i < $len; $i++) {
            $bytes[] = ord($utf8[$i]);
        }
        return $bytes;
    }

    private function rotl($x, $n)
    {
        $n = $n % 32;
        $x &= 0xffffffff;
        return ((($x << $n) & 0xffffffff) | (($x & 0xffffffff) >> (32 - $n))) & 0xffffffff;
    }

    private function resultEncrypt($longStr, $tableKey)
    {
        $tables = [
            's0' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=',
            's1' => 'Dkdpgh4ZKsQB80/Mfvw36XI1R25+WUAlEi7NLboqYTOPuzmFjJnryx9HVGcaStCe=',
            's2' => 'Dkdpgh4ZKsQB80/Mfvw36XI1R25-WUAlEi7NLboqYTOPuzmFjJnryx9HVGcaStCe=',
            's3' => 'ckdp1h4ZKsUB80/Mfvw36XIgR25+WQAlEi7NLboqYTOPuzmFjJnryx9HVGDaStCe',
            's4' => 'Dkdpgh2ZmsQB80/MfvV36XI1R45-WUAlEixNLwoqYTOPuzKFjJnry79HbGcaStCe',
        ];
        $table = $tables[$tableKey] ?? $tables['s0'];

        $out = '';
        $len = strlen($longStr);
        // 对齐 workers.js 的行为：charCodeAt 越界会得到 NaN，位运算后等价于 0。
        // PHP 里越界会触发 Warning，所以这里显式把缺失字节当 0，并按 ceil(len/3)*4 输出。
        $groups = (int)ceil($len / 3);
        $total = $groups * 4;
        $tableLen = strlen($table);

        for ($i = 0; $i < $total; $i++) {
            $round = intdiv($i, 4);
            $offset = $round * 3;

            $a = ($offset < $len) ? ord($longStr[$offset]) : 0;
            $b = (($offset + 1) < $len) ? ord($longStr[$offset + 1]) : 0;
            $c = (($offset + 2) < $len) ? ord($longStr[$offset + 2]) : 0;
            $longInt = (($a << 16) | ($b << 8) | $c) & 0xffffff;

            $key = $i % 4;
            if ($key === 0) {
                $temp = ($longInt & 16515072) >> 18;
            } elseif ($key === 1) {
                $temp = ($longInt & 258048) >> 12;
            } elseif ($key === 2) {
                $temp = ($longInt & 4032) >> 6;
            } else {
                $temp = $longInt & 63;
            }

            // 理论上 temp 恒为 0..63；这里再兜底一次，避免异常污染 JSON。
            if ($temp < 0 || $temp >= $tableLen) {
                $temp = 0;
            }
            $out .= $table[$temp];
        }

        return $out;
    }

    private function generRandom($random, $option)
    {
        $r = (int)$random;
        return [
            (($r & 255 & 170) | ($option[0] & 85)) & 0xff,
            (($r & 255 & 85) | ($option[0] & 170)) & 0xff,
            (((($r >> 8) & 255) & 170) | ($option[1] & 85)) & 0xff,
            (((($r >> 8) & 255) & 85) | ($option[1] & 170)) & 0xff,
        ];
    }

    private function generateRandomStr()
    {
        $list = [];
        $list = array_merge($list, $this->generRandom(mt_rand() / mt_getrandmax() * 10000, [3, 45]));
        $list = array_merge($list, $this->generRandom(mt_rand() / mt_getrandmax() * 10000, [1, 0]));
        $list = array_merge($list, $this->generRandom(mt_rand() / mt_getrandmax() * 10000, [1, 5]));
        $out = '';
        foreach ($list as $b) {
            $out .= chr($b);
        }
        return $out;
    }

    private function generateRc4BbStr($urlSearchParams, $userAgent, $windowEnvStr, $suffix = 'cus', $arguments = [0, 1, 14])
    {
        $startTime = (int)(microtime(true) * 1000);

        // sm3.sum(sm3.sum(x)) 等价：对 hex 再 hash 一次（workers.js 以字节数组参与索引）
        $urlParamsHash1 = $this->sm3Hex($urlSearchParams . $suffix);
        $urlParamsHash2 = $this->sm3Hex($urlParamsHash1);
        $cusHash1 = $this->sm3Hex($suffix);
        $cusHash2 = $this->sm3Hex($cusHash1);

        $uaRc4 = $this->rc4Encrypt($userAgent, chr(0) . chr(1) . chr(14));
        $uaEnc = $this->resultEncrypt($uaRc4, 's3');
        $uaHash = $this->sm3Hex($uaEnc);

        $endTime = (int)(microtime(true) * 1000);

        $urlParamsBytes = hex2bin($urlParamsHash2);
        $cusBytes = hex2bin($cusHash2);
        $uaBytes = hex2bin($uaHash);

        if ($urlParamsBytes === false || $cusBytes === false || $uaBytes === false) {
            return null;
        }

        // 只取特定下标字节（与 workers.js 对齐）
        $b = [];
        $b[8] = 3;
        $b[10] = $endTime;
        $b[16] = $startTime;
        $b[18] = 44;
        $b[19] = [1, 0, 1, 5];

        $b[20] = ($b[16] >> 24) & 255;
        $b[21] = ($b[16] >> 16) & 255;
        $b[22] = ($b[16] >> 8) & 255;
        $b[23] = $b[16] & 255;
        $b[24] = (int)floor($b[16] / 256 / 256 / 256 / 256);
        $b[25] = (int)floor($b[16] / 256 / 256 / 256 / 256 / 256);

        $b[26] = ($arguments[0] >> 24) & 255;
        $b[27] = ($arguments[0] >> 16) & 255;
        $b[28] = ($arguments[0] >> 8) & 255;
        $b[29] = $arguments[0] & 255;

        $b[30] = ((int)floor($arguments[1] / 256)) & 255;
        $b[31] = $arguments[1] % 256;
        $b[32] = ($arguments[1] >> 24) & 255;
        $b[33] = ($arguments[1] >> 16) & 255;

        $b[34] = ($arguments[2] >> 24) & 255;
        $b[35] = ($arguments[2] >> 16) & 255;
        $b[36] = ($arguments[2] >> 8) & 255;
        $b[37] = $arguments[2] & 255;

        $b[38] = ord($urlParamsBytes[21]);
        $b[39] = ord($urlParamsBytes[22]);
        $b[40] = ord($cusBytes[21]);
        $b[41] = ord($cusBytes[22]);
        $b[42] = ord($uaBytes[23]);
        $b[43] = ord($uaBytes[24]);

        $b[44] = ($b[10] >> 24) & 255;
        $b[45] = ($b[10] >> 16) & 255;
        $b[46] = ($b[10] >> 8) & 255;
        $b[47] = $b[10] & 255;
        $b[48] = $b[8];
        $b[49] = (int)floor($b[10] / 256 / 256 / 256 / 256);
        $b[50] = (int)floor($b[10] / 256 / 256 / 256 / 256 / 256);

        // pageId/aid 固定写死同 workers.js
        $pageId = 6241;
        $aid = 6383;
        $b[51] = $pageId;
        $b[52] = ($pageId >> 24) & 255;
        $b[53] = ($pageId >> 16) & 255;
        $b[54] = ($pageId >> 8) & 255;
        $b[55] = $pageId & 255;

        $b[56] = $aid;
        $b[57] = $aid & 255;
        $b[58] = ($aid >> 8) & 255;
        $b[59] = ($aid >> 16) & 255;
        $b[60] = ($aid >> 24) & 255;

        $windowEnvBytes = $this->toBytesUtf8($windowEnvStr);
        $b[64] = count($windowEnvBytes);
        $b[65] = $b[64] & 255;
        $b[66] = ($b[64] >> 8) & 255;

        $b[69] = 0;
        $b[70] = 0;
        $b[71] = 0;

        $b[72] =
            $b[18] ^ $b[20] ^ $b[26] ^ $b[30] ^ $b[38] ^ $b[40] ^ $b[42] ^ $b[21] ^ $b[27] ^
            $b[31] ^ $b[35] ^ $b[39] ^ $b[41] ^ $b[43] ^ $b[22] ^ $b[28] ^ $b[32] ^ $b[36] ^
            $b[23] ^ $b[29] ^ $b[33] ^ $b[37] ^ $b[44] ^ $b[45] ^ $b[46] ^ $b[47] ^ $b[48] ^
            $b[49] ^ $b[50] ^ $b[24] ^ $b[25] ^ $b[52] ^ $b[53] ^ $b[54] ^ $b[55] ^ $b[57] ^
            $b[58] ^ $b[59] ^ $b[60] ^ $b[65] ^ $b[66] ^ $b[70] ^ $b[71];

        $bb = [
            $b[18], $b[20], $b[52], $b[26], $b[30], $b[34], $b[58], $b[38], $b[40], $b[53],
            $b[42], $b[21], $b[27], $b[54], $b[55], $b[31], $b[35], $b[57], $b[39], $b[41],
            $b[43], $b[22], $b[28], $b[32], $b[60], $b[36], $b[23], $b[29], $b[33], $b[37],
            $b[44], $b[45], $b[59], $b[46], $b[47], $b[48], $b[49], $b[50], $b[24], $b[25],
            $b[65], $b[66], $b[70], $b[71],
        ];
        $bb = array_merge($bb, $windowEnvBytes, [$b[72]]);
        $plain = '';
        foreach ($bb as $v) {
            $plain .= chr($v & 0xff);
        }
        return $this->rc4Encrypt($plain, chr(121));
    }

    private function generateABogus($urlSearchParams, $userAgent)
    {
        $rand = $this->generateRandomStr();
        $bb = $this->generateRc4BbStr(
            $urlSearchParams,
            $userAgent,
            '1536|747|1536|834|0|30|0|0|1536|834|1536|864|1525|747|24|24|Win32'
        );
        if (!$bb) {
            return null;
        }
        $resultStr = $rand . $bb;
        return $this->resultEncrypt($resultStr, 's4') . '=';
    }
}
