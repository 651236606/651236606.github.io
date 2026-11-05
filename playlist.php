<?php
/**
 * 歌单分类列表（桌面版 / 手机版共用）
 *
 * 数据源：网易云开放 JSON 接口 api/playlist/list。
 * 旧实现抓取 discover/playlist 网页再用正则解析 HTML，页面结构早已变更且常被 403，
 * 导致返回空列表，前端恒显示「暂无歌单」。
 *
 * 必须带 CORS 头：手机 WebView 从 capacitor://localhost 跨域调用本接口，
 * 缺少该头时响应会被浏览器直接丢弃（现象：搜索可用、歌单取不到）。
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$cat    = (isset($_GET['cat']) && $_GET['cat'] !== '') ? urldecode($_GET['cat']) : '全部';
$page   = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$limit  = 35;
$offset = ($page - 1) * $limit;

$api = 'https://music.163.com/api/playlist/list?cat=' . urlencode($cat)
     . '&order=hot&limit=' . $limit . '&offset=' . $offset;

$ua  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
$hdr = array('Referer: https://music.163.com/', 'Accept: application/json, text/plain, */*');

$raw = '';
// 优先 curl（可关 SSL 校验、可设超时），失败再退回 file_get_contents
if (function_exists('curl_init')) {
    $ch = curl_init($api);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_HTTPHEADER     => $hdr,
    ));
    $raw = curl_exec($ch);
    curl_close($ch);
}
if (!$raw) {
    $ctx = stream_context_create(array(
        'http' => array(
            'method'  => 'GET',
            'timeout' => 15,
            'header'  => "User-Agent: {$ua}\r\nReferer: https://music.163.com/\r\n",
        ),
        'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
    ));
    $raw = @file_get_contents($api, false, $ctx);
}

$out = array(
    'code'     => 0,
    'cat'      => $cat,
    'page'     => $page,
    'NextPage' => $page + 1,
    'title'    => $cat,
    'playlist' => array(),
);

$data = json_decode($raw, true);
if (is_array($data) && !empty($data['playlists'])) {
    foreach ($data['playlists'] as $p) {
        $out['playlist'][] = array(
            'id'     => isset($p['id']) ? $p['id'] : 0,
            'title'  => isset($p['name']) ? $p['name'] : '',
            'poster' => isset($p['coverImgUrl']) ? $p['coverImgUrl'] : (isset($p['picUrl']) ? $p['picUrl'] : ''),
            'author' => (isset($p['creator']['nickname']) && $p['creator']['nickname'] !== '')
                        ? $p['creator']['nickname'] : '网易云',
            'num'    => isset($p['trackCount']) ? $p['trackCount'] : (isset($p['playCount']) ? $p['playCount'] : ''),
        );
    }
}

// 明确返回错误码，前端可据此给出具体提示，而不是永远停在「暂无歌单」
if (empty($out['playlist'])) {
    $out['code'] = 1;
    $out['msg']  = '上游歌单接口未返回数据（服务器到 music.163.com 的网络或风控限制）';
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
