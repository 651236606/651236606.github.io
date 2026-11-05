<?php
/**
 * TOP 排行榜（桌面版 / 手机版共用）
 *
 * 数据源：网易云歌单详情接口 api/v6/playlist/detail，默认取「热歌榜」(3778678)。
 * 旧实现抓 discover/toplist 网页再用正则取 textarea，页面结构变更或风控时直接返回空，
 * 且返回字段（url/title/poster/id）与前端约定的 mid/SongName/SongUrl/SongPoster/SongLrc 不一致，
 * 点击榜单歌曲无法播放。此处统一成与 index.php 相同的字段结构。
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// 与 index.php 保持一致：歌曲地址用绝对 URL。
// 手机版 WebView 的 origin 是 http://localhost（Capacitor），相对路径 ./API/ 会指向本地壳，
// 只有绝对地址才能回源到真实服务器。
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$fname  = $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/index.php';

$id     = (isset($_GET['id']) && intval($_GET['id']) > 0) ? intval($_GET['id']) : 3778678;
$page   = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$limit  = 100;
$server = (isset($_GET['server']) && $_GET['server'] !== '') ? $_GET['server'] : 'netease';

$api = 'https://music.163.com/api/v6/playlist/detail?id=' . $id . '&n=1000&s=0';
$ua  = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
$hdr = array('Referer: https://music.163.com/', 'Accept: application/json, text/plain, */*');

$raw = '';
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
    'code'  => 0,
    'title' => 'TOP排行榜',
    'page'  => $page,
    'data'  => array(),
);

$data = json_decode($raw, true);
$tracks = array();
if (is_array($data) && isset($data['playlist']['tracks']) && is_array($data['playlist']['tracks'])) {
    $tracks = $data['playlist']['tracks'];
    if (!empty($data['playlist']['name'])) {
        $out['title'] = $data['playlist']['name'];
    }
    if (!empty($data['playlist']['coverImgUrl'])) {
        $out['cover'] = $data['playlist']['coverImgUrl'];
    }
}

$start = ($page - 1) * $limit;
$slice = array_slice($tracks, $start, $limit);

foreach ($slice as $t) {
    $mid = isset($t['id']) ? $t['id'] : 0;
    if (!$mid) { continue; }

    $singers = array();
    if (!empty($t['artists']) && is_array($t['artists'])) {
        foreach ($t['artists'] as $a) {
            if (!empty($a['name'])) { $singers[] = $a['name']; }
        }
    } elseif (!empty($t['ar']) && is_array($t['ar'])) {
        foreach ($t['ar'] as $a) {
            if (!empty($a['name'])) { $singers[] = $a['name']; }
        }
    }

    // 封面：优先 pic_str（带扩展名），否则用 pic（纯数字 id）
    $pic = '';
    if (!empty($t['al']['pic_str'])) {
        $pic = $t['al']['pic_str'];
    } elseif (!empty($t['al']['pic'])) {
        $pic = $t['al']['pic'];
    } elseif (!empty($t['album']['pic_str'])) {
        $pic = $t['album']['pic_str'];
    } elseif (!empty($t['album']['pic'])) {
        $pic = $t['album']['pic'];
    }

    $out['data'][] = array(
        'mid'        => $mid,
        'SongName'   => isset($t['name']) ? $t['name'] : '',
        'SongUrl'    => $fname . '?src=' . $mid . '&server=' . $server . '&v=4',
        'SongPoster' => $pic !== '' ? ($fname . '?pic=' . $pic . '&server=' . $server) : '',
        'SongLrc'    => $fname . '?lyric=' . $mid . '&server=' . $server,
        'singer'     => implode(' & ', $singers),
    );
}

if (empty($out['data'])) {
    $out['code'] = 1;
    $out['msg']  = '榜单接口未返回数据（服务器到 music.163.com 的网络或风控限制）';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
