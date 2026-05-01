<?php
/**
OE源码网
 */
header('Content-Type: application/json; charset=utf-8');
// 关闭错误显示，避免破坏JSON格式
error_reporting(E_ALL);
ini_set('display_errors', 0);
// 设置超时和内存
set_time_limit(600);
ini_set('memory_limit', '512M');

require_once 'DouyinZYParser.php';

// ================= 配置区域 =================

// 请在此处填入抖音Cookie
$cookie = '__ac_signature=_02B4Z6wo00f01MpCWnwAAIDA8lFq-PgJ9NjKYl7AAFq-7f; UIFID_TEMP=c8c20d54553eadab8c678961c2b0df95555df87bbc6b890988ad105aec15abc066d8498631e0ea6eb6bcbc10e7dcad615c5749b45cc7e55bc8ae206d6625f1599eb986829fd3386c1849262d5d29bb6bcd9007b134717338b09f6ba7df9938e5cad13422cc37a7c0181782761056a374; my_rd=2; fpk1=U2FsdGVkX19EdvFOxISPrUVuETXkQPNKfzArgZBu6ULezSgBC8l3D3V6G5qKws3oDsUFn2gdFhxyfdUwsBR/2A==; fpk2=8369da3c75ccd12bc017791df73a85c8; UIFID=c8c20d54553eadab8c678961c2b0df95555df87bbc6b890988ad105aec15abc066d8498631e0ea6eb6bcbc10e7dcad615c5749b45cc7e55bc8ae206d6625f1593e8438433ef262bb32fe7224d22c38dc54966c7722bbaf8c4f77508f4bf9ecca2c8ae64edea72e9be0c95d1363a47013ee54b0a4e2b51ddfcefcc682bd5c25a88d453efc4fdec2a29930c2db5acae90effc950c7bb16151dee0e342944c4a289eee3295736e89ec42d8681dca78f57de00561b4eef000df5d17e2af86436d460; enter_pc_once=1; s_v_web_id=verify_mol69tiv_qpjPzhwo_UbFQ_4LuS_AtwZ_fknfyVpWh8SC; douyin.com; device_web_cpu_core=12; device_web_memory_size=16; architecture=amd64; is_support_rtm_web_ts=1; is_dash_user=1; strategyABtestKey=%221777534715.838%22; passport_csrf_token=a763172e29e59e243a7a29069f849b00; passport_csrf_token_default=a763172e29e59e243a7a29069f849b00; ttwid=1%7Ceb3JYfWQua_IVKXxHcUYC4EMqFovVYx5OZo4xgPkF_w%7C1777534717%7Cb16a414a30bb5fc715dac0d0aa1c42e5ef8843a16fabfeee9757fccc73c43726; bd_ticket_guard_client_web_domain=2; passport_mfa_token=CjVa79oNdUWgmpOqGau%2FL9X%2FAkyzalHuFn0Ye1OG9UPLAeTjDcJPK7JMh8x4T44dj4JGAYaaRBpKCjwAAAAAAAAAAAAAUF3ka2guomKxRX0NewNC122cvt%2F%2FXqRRv46k5CGybJlBfFNhC2TjtELd33DKZr%2F%2FRT4Q1JmQDhj2sdFsIAIiAQPVh7WF; d_ticket=79cab952728bad4f79f6dbe686949a43873f2; passport_assist_user=Cj2xbpNwbV_px4X7oKqhlWHItk7Ewj3G62vYqmU29fSWZrC77NTkvhc7RudpVcZKP4FcazMkgKlrH-G1t_8KGkoKPAAAAAAAAAAAAABQXR-1xp06L5TDKvz0G6N39J65OtlgDq2IPqo6vIiht-2aIDzp-QUtI3xIlCKylTP36xDemZAOGImv1lQgASIBAzaGWv8%3D; n_mh=WTZ6KlZlxTORNSwOetVwaN9NFNcg8WGvLkk1W8TMGtk; sid_guard=0fb42b16b232987a221b555a2f68b7ce%7C1777534767%7C5184000%7CMon%2C+29-Jun-2026+07%3A39%3A27+GMT; uid_tt=bfc65b50a9a98827cf9b993d4d7acf06; uid_tt_ss=bfc65b50a9a98827cf9b993d4d7acf06; sid_tt=0fb42b16b232987a221b555a2f68b7ce; sessionid=0fb42b16b232987a221b555a2f68b7ce; sessionid_ss=0fb42b16b232987a221b555a2f68b7ce; session_tlb_tag=sttt%7C13%7CD7QrFrIymHoiG1VaL2i3zv_________VDA81E_gxo92JmoHUO-BmhbRZcda5hzHTBb6mycHLgZo%3D; is_staff_user=false; has_biz_token=false; sid_ucp_v1=1.0.0-KGZmN2E3N2Y1ZDFmNTY3MjdjMTUzNGYzODlkZjJmM2I2ZTQxOTBjZWYKHwj6rPKknQMQr47MzwYY7zEgDDChuMHiBTgHQPQHSAQaAmxmIiAwZmI0MmIxNmIyMzI5ODdhMjIxYjU1NWEyZjY4YjdjZQ; ssid_ucp_v1=1.0.0-KGZmN2E3N2Y1ZDFmNTY3MjdjMTUzNGYzODlkZjJmM2I2ZTQxOTBjZWYKHwj6rPKknQMQr47MzwYY7zEgDDChuMHiBTgHQPQHSAQaAmxmIiAwZmI0MmIxNmIyMzI5ODdhMjIxYjU1NWEyZjY4YjdjZQ; _bd_ticket_crypt_cookie=875b4b889b22cdce62ec400f619f08d8; __security_mc_1_s_sdk_sign_data_key_web_protect=ac5a5265-4d20-873a; __security_mc_1_s_sdk_cert_key=576c97f3-4970-a922; __security_mc_1_s_sdk_crypt_sdk=9238f7b3-40fb-a796; __security_server_data_status=1; login_time=1777534767985; DiscoverFeedExposedAd=%7B%7D; SelfTabRedDotControl=%5B%5D; FOLLOW_NUMBER_YELLOW_POINT_INFO=%22MS4wLjABAAAAxfTt7BtA6vLRmfhGKS2ZeIQN8DCiOgYFS8qZ1FLz0FI%2F1777564800000%2F0%2F1777534771373%2F0%22; publish_badge_show_info=%220%2C0%2C0%2C1777534772223%22; bd_ticket_guard_client_data=eyJiZC10aWNrZXQtZ3VhcmQtdmVyc2lvbiI6MiwiYmQtdGlja2V0LWd1YXJkLWl0ZXJhdGlvbi12ZXJzaW9uIjoxLCJiZC10aWNrZXQtZ3VhcmQtcmVlLXB1YmxpYy1rZXkiOiJCR0M1SmZGSXNXMWtnYTIzaVZuUTUrV0JOTWxsUGw1S0lUL1NGVmZQSzZuNUY1UTJpOFBvbjR5V1hiUkRYWUtDWldLZTJPK0gyTDluOXNSYkhtbEVOQUE9IiwiYmQtdGlja2V0LWd1YXJkLXdlYi12ZXJzaW9uIjoyfQ%3D%3D; biz_trace_id=88d84257; bd_ticket_guard_client_data_v2=eyJyZWVfcHVibGljX2tleSI6IkJHQzVKZkZJc1cxa2dhMjNpVm5RNStXQk5NbGxQbDVLSVQvU0ZWZlBLNm41RjVRMmk4UG9uNHlXWGJSRFhZS0NaV0tlMk8rSDJMOW45c1JiSG1sRU5BQT0iLCJ0c19zaWduIjoidHMuMi4wZGE5NTE4ZjRmM2Y5YTUyZWZlNzBiMmM4MjBmZDIyMDU2Mjg1MDc0NDBhYmM2NzBlMjYyZTZlZTNkODk4YmViYzRmYmU4N2QyMzE5Y2YwNTMxODYyNGNlZGExNDkxMWNhNDA2ZGVkYmViZWRkYjJlMzBmY2U4ZDRmYTAyNTc1ZCIsInJlcV9jb250ZW50Ijoic2VjX3RzIiwicmVxX3NpZ24iOiIxV3RhbmdtOElXVUFrRERZYW0wYm95UEJUd1o3Rkcra1BnT3VMTVk1R3BrPSIsInNlY190cyI6IiNxMXo2Nlpya09MQ2tEU09MRHhVTGJHdFJNdk9sbWdIc1hPeVlzTS9leDB3VHhxRDJtT05wQnNIaFRsUksifQ%3D%3D; IsDouyinActive=true; home_can_add_dy_2_desktop=%220%22; dy_swidth=400; dy_sheight=653; stream_recommend_feed_params=%22%7B%5C%22cookie_enabled%5C%22%3Atrue%2C%5C%22screen_width%5C%22%3A400%2C%5C%22screen_height%5C%22%3A653%2C%5C%22browser_online%5C%22%3Atrue%2C%5C%22cpu_core_num%5C%22%3A12%2C%5C%22device_memory%5C%22%3A16%2C%5C%22downlink%5C%22%3A9.6%2C%5C%22effective_type%5C%22%3A%5C%224g%5C%22%2C%5C%22round_trip_time%5C%22%3A0%7D%22; odin_tt=d4d30e2f52f356a97f5cf8890286358bb4826474c8f6fee1df9480eb5cab08b4fbe351ccaaeba2fda96de7301cc38eed98d9610767341305ea67b7ba5cf1952d';
//cookie比较严格 feed接口请求头的cookie比较全
// ===========================================


// 获取参数
$url = $_GET['url'] ?? ''; // 分享链接
$id = $_GET['id'] ?? '';   // 用户ID (sec_uid)
$count = isset($_GET['count']) ? (int)$_GET['count'] : 18; // 获取数量，默认18

try {
    // 实例化解析器
    $parser = new DouyinParser($cookie);

    // 获取数据
    $result = $parser->getData($url, $id, $count);

    // 输出结果
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'msg' => 'error',
        'data' => [],
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
