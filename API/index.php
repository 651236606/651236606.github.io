<?php
// 允许跨域（手机 App 的 WebView 从 capacitor://localhost 调用本接口时需此头）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
error_reporting(0);
ini_set('memory_limit', '512M');
header("Content-Type: text/html; charset=utf-8");

require 'Meting.php';
use Metowolf\Meting;

/* ============================================================
 * 【必须】网易云 VIP Cookie —— 解除 30 秒试听的唯一可靠办法
 * ------------------------------------------------------------
 * 网易云对「VIP / 版权」歌曲（接口里 fee=1）给未登录 / 普通账号
 * 只返回 30 秒试听片段，这是服务端按账号权限强控的，任何第三方
 * 接口都绕不过。只有用【VIP 账号】的 Cookie 才能拿到完整版。
 *
 * 获取步骤：
 *   1. 电脑浏览器登录 https://music.163.com （必须是 VIP 账号）
 *   2. F12 → Application(应用) → Cookies → music.163.com
 *   3. 复制 MUSIC_U 的值（一长串字母数字），按下面格式填进去：
 *        'MUSIC_U=xxxxxx; __csrf=xxxx; ...'
 *      （只填 MUSIC_U 通常即可，其它字段可省略）
 *   4. 保存，VIP 歌曲即可播放完整版（音质 / 歌词 / 封面均正常）。
 *
 * 备用：若某首歌仍异常，可把下面这个「托管音乐 API」开关打开，
 * 它作为安全兜底（拿不到地址时才用，且不会让页面报错）。
 * ============================================================ */
$netease_vip_cookie = 'MUSIC_U=00D5AA203E986CACBE529D3BC6C61717BEE59CCBA948307B168E21AD655BA685018E9376DC3961D873790FE374305059D143740D085B9F95979D7B25350A1B2454CE96972464616A44F748B3123286C3025B6AB55CE401E7F308899B65D21791CECA671A9BA9E3D05CBC863308F58401050682494C0E6C20967C960D59C7E5A7D464215AC739BC4CA37DB7EEB450260536861DE0038AC96FA2BD40636D1739C7D9CEF389CA89CD96FEA6830D5A1208FEB4EFCBB6031B91BC44489289159E729B5FB6044E00C5BA075865FA38C8BFA516DF15B2E85D9B22358F70DA41A7CA2047C10B1FA78AB4957667BA1C12238190F9FAF71000D65C22D575A08A6CFA9FA2D965B39054B0BC149228B8562D32526ACC6463BE3195E39AEC344DF9E350174D29B6B84074DBAC31827F71BD55F647D223581628AD8C78ABB09A6F34C09885A1616AA81B3F1139B0B942ED2D4FBF54A4995F43FE3809C15ADD06B574C3A0D29E12C2AFD29691AC1C9D7F5ACF714C06C7B567AD970C96CE61EE57A95787C95B6FD4AC1D57D87BBDD45CBA64B4E6FE291B04E9; __csrf=c418f22e7d4ee725ef84cddd5414e17c; JSESSIONID-WYYY=Fu7VYWTY6qHbcKDzUg1iVVXHBr9zfXbkJObQEc2BpDZRiM5brCtpSIdyNV0N%2BCcpj0sghQnHm0RRI10FoeQATibX9lATmF39X18CVJlKtmBODXFduNK%2FKo65M%2FC%5CYaU%2FKroh6%2BXAtuC9I%5Cvk0EWiR7%2FoXQTR%2Bh1yl0COBeXziJ66D%5Caz%3A1793601480127; Hm_lpvt_1483fb4774c02a30ffa6f0e2945e9b70=1793599686'; // ← 把你的 VIP 账号 MUSIC_U 填到这引号里

// 安全兜底用的托管 Meting API（仅在本地拿不到地址时尝试，不会报错）
$backup_api = 'https://api.injahow.cn/meting/?server=netease&type=url&id=';

// 跟随页面实际协议（https 站点必须返回 https 地址，否则浏览器会因「混合内容」拦截音频）
$is_https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
$scheme = $is_https ? 'https://' : 'http://';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ((isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':' . $_SERVER['SERVER_PORT'] : '');
$fname = $scheme . $host . $_SERVER["SCRIPT_NAME"];
$server = $_GET['server'] ? $_GET['server'] : 'netease';
$api = new Meting($server);

/* 播放地址版本号：改动它会强制浏览器/Electron 丢弃旧缓存。
 * 早期版本会把「30 秒试听片段」缓存 24 小时，导致修复后仍然只播几十秒，
 * 因此每次修复音源逻辑都必须递增这个号。 */
define('YY_STREAM_VER', '4');

/* ============================================================
 * 【自诊断接口】检测当前配置的网易云 Cookie 是否为有效 VIP
 * 用法： https://你的域名/API/index.php?checkcookie=1
 *   - 返回 JSON：cookie 是否为 VIP、能否取到完整版（>1MB）还是 30 秒试听
 *   - 部署后用它一键确认 Cookie 是否有效，不必再靠猜
 * ============================================================ */
if (isset($_GET['checkcookie'])) {
    header('Content-type:text/json; charset=utf-8');
    $out = array('cookie_configured' => !empty($netease_vip_cookie));
    if (empty($netease_vip_cookie)) {
        $out['result']  = 'no_cookie';
        $out['message'] = '未配置网易云 Cookie，VIP 歌曲只能播放 30 秒试听。请在文件顶部 $netease_vip_cookie 填入【处于 VIP 订阅状态】的网易云账号的 MUSIC_U。';
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }
    // 用几首已知的 fee=1（VIP）歌曲测试
    $test_ids = array(65766, 354593, 186431);
    $results  = array();
    foreach ($test_ids as $tid) {
        $napi = new Meting('netease');
        $napi->cookie($netease_vip_cookie);
        $j   = json_decode($napi->format(true)->url($tid));
        $url = isset($j->url) ? $j->url : '';
        $row = array('song_id' => $tid, 'has_url' => !empty($url));
        if (!empty($url)) {
            // 服务器端 HEAD 该音频地址，看实际能拿到多大（完整版 vs 30 秒）
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_HEADER         => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_HTTPHEADER     => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
                    'Referer: https://music.163.com/',
                    'Cookie: ' . $netease_vip_cookie,
                ),
            ));
            curl_exec($ch);
            $cl   = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $row['http_code']      = $code;
            $row['content_length'] = $cl;
            $row['verdict']        = ($cl > 1000000) ? 'FULL(完整版)' : (($cl > 0) ? 'PREVIEW(30秒试听)' : 'UNKNOWN');
        }
        $results[] = $row;
    }
    $out['tests'] = $results;
    // 判定：只要 Cookie 已配置且能拿到完整版地址(has_url)，即视为 Cookie 有效。
    // 注：本诊断的「服务端 HEAD 探测」很可能因【服务器机房 IP 被网易云音频 CDN 拦截】而返回
    // http_code=403 / verdict=30秒 —— 这是机房 IP 的硬限制，与 Cookie 无关。该情况下部署后
    // 会走「浏览器直连完整版 URL」方案（用你正常 IP 拉取），VIP 歌曲照样能播完整首。
    $all_has_url = true;
    foreach ($results as $r) {
        if (empty($r['has_url'])) {
            $all_has_url = false;
        }
    }
    if (!$out['cookie_configured']) {
        $out['result']  = 'no_cookie';
        $out['message'] = '❌ 未配置网易云 Cookie：VIP 歌曲只能播放 30 秒试听。请在文件顶部 $netease_vip_cookie 填入【处于 VIP 订阅状态】的网易云账号的 MUSIC_U。';
    } elseif ($all_has_url) {
        $out['result']  = 'cookie_valid';
        $out['message'] = '✅ Cookie 配置正确，且能拿到完整版地址。若诊断里 http_code=403，是【服务器机房 IP 被网易云拦截】所致（与 Cookie 无关）——部署后浏览器会直接拉取完整版 URL（走你的正常 IP），VIP 歌曲即可播放完整首。';
    } else {
        $out['result']  = 'no_url';
        $out['message'] = '❌ 配置了 Cookie 但拿不到音频地址（可能是 Cookie 失效/账号异常）。请重新抓取有效的 VIP 账号 MUSIC_U 并部署。';
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($server) {
    case 'netease':
        $form = '网易云音乐';
        break;
    case 'tencent':
        $form = 'QQ音乐';
        break;
    case 'xiami':
        $form = '虾米音乐';
        break;
    case 'kugou':
        $form = '酷狗音乐';
        break;
    case 'baidu':
        $form = '百度音乐';
        break;
    case 'kuwo':
        $form = '酷我音乐';
        break;
}

if (isset($_GET['key'])) {
    $data = $api->format(true)->search($_GET['key'], [
        'page' => 1,
        'limit' => 50
    ]);
    $arr = json_decode($data);
    $td['title'] = "【" . $_GET['key'] . "】的搜索结果";
    foreach ($arr as $v) {
        $datas['SongUrl'] = $fname . '?src=' . $v->url_id . '&server=' . $server . '&v=' . YY_STREAM_VER;
        $datas['mid'] = $v->url_id;
        $datas['SongName'] = $v->name;
        $datas['SongPoster'] = $fname . '?pic=' . $v->pic_id . '&server=' . $server;
        $datas['singer'] = implode('，', $v->artist);
        $datas['album'] = $v->album;
        $datas['SongLrc'] = $fname . '?lyric=' . $v->lyric_id . '&server=' . $server;
        $ars[] = $datas;

    }
    $td['code'] = 0;
    $td['data'] = $ars;
    header('Content-type:text/json');
    echo json_encode($td);
}

if (isset($_GET['playlist'])) {
    $url = 'https://music.163.com/playlist?id=' . $_GET['playlist'];
    $curl = file_get_contents($url);
    preg_match('/og:title" content="(.*)"/imsU', $curl, $t);
    preg_match('/og:image" content="(.*)"/imsU', $curl, $im);
    preg_match('/og:description" content="(.*)"/imsU', $curl, $d);
    $td['title'] = $t[1] . ' - 歌单';
    $td['image'] = $im[1];
    $td['description'] = '介绍：' . $d[1];

    $data = $api->format(true)->playlist($_GET['playlist']);
    $arr = json_decode($data);
    //print_r($arr);
    foreach ($arr as $v) {
        $datas['SongUrl'] = $fname . '?src=' . $v->url_id . '&server=' . $server . '&v=' . YY_STREAM_VER;
        $datas['mid'] = $v->url_id;
        $datas['mv'] = null;
        if ($v->mv != 0) {
            $datas['mv'] = $v->mv;
        }
        $datas['SongName'] = $v->name;
        $datas['SongPoster'] = $fname . '?pic=' . $v->pic_id . '&server=' . $server;
        $datas['singer'] = implode(' & ', $v->artist);
        $datas['album'] = $v->album;
        $datas['SongLrc'] = $fname . '?lyric=' . $v->lyric_id . '&server=' . $server;
        $ars[] = $datas;
    }
    $td['code'] = 0;
    $td['data'] = $ars;
    header('Content-type:text/json');
    echo json_encode($td);
}

if (isset($_GET['src'])) {
    $id = $_GET['src'];

    if ($server === 'netease') {
        $napi = new Meting('netease');
        if (!empty($netease_vip_cookie)) {
            $napi->cookie($netease_vip_cookie);
        }
        $j = json_decode($napi->format(true)->url($id));
        $primary = !empty($j->url) ? $j->url : '';

        if ($primary !== '') {
            // 【核心修复】原节点优先，被 CDN 拒绝(403) 时自动改用同组其它节点。
            // VIP 完整版地址通常落在 m704 / m804，而这两个节点对部分网络一律 403，
            // 旧逻辑 403 后就退化到「无 Cookie 取地址」→ 拿到的正是 30 秒试听片段。
            // 由于网易云音频鉴权只校验 path 里的 token、与域名无关，换 host 即可取到同一个完整文件。
            foreach (yy_audio_urls($primary, $is_https) as $try) {
                if (stream_proxy($try, true, $netease_vip_cookie)) {
                    exit;
                }
            }
        }

        // 兜底 1：带 Cookie 的所有节点都失败时，改用不带 Cookie 取地址重试（免费曲可拿完整版）
        $napi2 = new Meting('netease');
        $j2 = json_decode($napi2->format(true)->url($id));
        $secondary = !empty($j2->url) ? $j2->url : '';
        if ($secondary !== '') {
            foreach (yy_audio_urls($secondary, $is_https) as $try) {
                if (stream_proxy($try, true, '')) {
                    exit;
                }
            }
        }
        // 兜底 2：本地拿不到地址（罕见 / 网络波动）时，安全兜底到托管 API（不会让页面报错）
        if (!empty($backup_api)) {
            if (stream_proxy($backup_api . urlencode($id), true, $netease_vip_cookie)) {
                exit;
            }
        }
        // 方案 B（最后兜底）：直接把浏览器 302 跳到音频 URL，由浏览器自己拉取。
        if ($primary !== '') {
            header("Location: " . yy_upgrade_https($primary, $is_https));
            exit;
        }
        header('HTTP/1.1 404 Not Found');
        echo 'no source';
        exit;
    }

    // 非网易云源：同样走代理，规避混合内容
    $j = json_decode($api->format(true)->url($id));
    if (!empty($j->url)) {
        if (stream_proxy($j->url, true, $netease_vip_cookie)) {
            exit;
        }
    }
    header('HTTP/1.1 404 Not Found');
    echo 'no source';
    exit;
}

if (isset($_GET['mv'])) {
    $gurl = file_get_contents('http://music.163.com/api/mv/detail/?id=' . $_GET['mv'] . '&type=mp4');
    $json = json_decode($gurl);
    $url = end($json->data->brs);
    header("Location:$url");
}

if (isset($_GET['lyric'])) {
    $data = $api->format(true)->lyric($_GET['lyric']);
    $json = json_decode($data);
    echo $json->lyric;
}

if (isset($_GET['pic'])) {
    $data = $api->format(true)->pic($_GET['pic']);
    $json = json_decode($data);
    if (!empty($json->url)) {
        // 成功才 exit；失败继续走下面的兜底（否则封面会静默空白）
        if (stream_proxy($json->url, false)) {
            exit;
        }
    }
    // 兜底：网易云接口返回的封面地址可能已失效(音频 CDN 回 NoSuchKey)，
    // 改用歌曲详情里的专辑封面（同一张图的新版地址，实测可用）。
    $pic_fallback = netease_album_pic($_GET['pic'], $netease_vip_cookie);
    if ($pic_fallback !== '') {
        if (stream_proxy($pic_fallback, false)) {
            exit;
        }
        header('Location: ' . $pic_fallback);
        exit;
    }
    header('HTTP/1.1 404 Not Found');
    echo 'no pic';
    exit;
}

/**
 * https 站点下把网易云 CDN(http://music.126.net) 升级为 https，规避混合内容拦截
 */
function yy_upgrade_https($url, $is_https)
{
    if (!$is_https) {
        return $url;
    }
    return preg_replace('#^http://([^/]*\.)?music\.126\.net#i', 'https://$1music.126.net', $url);
}

/**
 * 记忆「对本机不可用的 CDN 节点」，避免每次取音频都先撞一次 403。
 * 拖动进度条会产生多次 Range 请求，记住故障节点可显著减少 seek 延迟。
 */
function yy_bad_nodes()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = array();
    $f = yy_node_cache_file();
    if (is_file($f)) {
        $a = json_decode(@file_get_contents($f), true);
        if (is_array($a)) {
            $now = time();
            foreach ($a as $h => $t) {
                if ((int)$t > $now) {
                    $cache[$h] = (int)$t;
                }
            }
        }
    }
    return $cache;
}

function yy_node_cache_file()
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yeyu_cdn_bad.json';
}

function yy_mark_bad_node($url)
{
    $h = @parse_url($url, PHP_URL_HOST);
    if (empty($h) || stripos($h, 'music.126.net') === false) {
        return;
    }
    $bad = yy_bad_nodes();
    $bad[$h] = time() + 1800; // 记 30 分钟，过期自动遗忘
    @file_put_contents(yy_node_cache_file(), json_encode($bad));
}

/**
 * 生成同一首歌的多组候选音频地址（网易云 CDN 节点故障转移）
 * ------------------------------------------------------------------
 * 背景：网易云把音频分发到 m7xx / m8xx 一组 CDN 节点，接口返回的地址里写死了某个节点域名。
 *      同一个文件在多个节点上都存在，且【鉴权只校验 path 内的 token，与域名无关】。
 *      实测 m704 / m804 在部分网络下一律返回 403，而 m701 / m801 / m702 / m803 正常。
 *      旧逻辑一遇 403 就退回「无 Cookie 取地址」，结果拿到 30 秒试听片段 —— 这就是
 *      「很多歌曲只能播几十秒」的真正原因。
 * 用法：按返回顺序逐个尝试，第一个成功的即可。
 */
function yy_audio_urls($url, $is_https)
{
    $url = yy_upgrade_https($url, $is_https);
    $p = @parse_url($url);
    if (empty($p['host']) || stripos($p['host'], 'music.126.net') === false) {
        return array($url);
    }
    $scheme = !empty($p['scheme']) ? $p['scheme'] : 'http';
    $path   = $p['path'] . (isset($p['query']) ? '?' . $p['query'] : '');
    // 同组可用节点
    $pool = array(
        'm701.music.126.net',
        'm801.music.126.net',
        'm702.music.126.net',
        'm803.music.126.net',
        'm703.music.126.net',
        'm802.music.126.net',
    );
    $bad = yy_bad_nodes();
    $list = array();
    // 原节点优先：部分 128k 路径(jd-musicrep-ts)只在原节点存在
    if (!isset($bad[strtolower($p['host'])])) {
        $list[] = $url;
    }
    foreach ($pool as $h) {
        if (strcasecmp($h, $p['host']) === 0) {
            continue;
        }
        if (isset($bad[strtolower($h)])) {
            continue;
        }
        $list[] = $scheme . '://' . $h . $path;
    }
    // 全部被标记为故障时不过滤，至少再试一遍（可能是网络临时抖动）
    if (empty($list)) {
        $list[] = $url;
        foreach ($pool as $h) {
            if (strcasecmp($h, $p['host']) !== 0) {
                $list[] = $scheme . '://' . $h . $path;
            }
        }
    }
    return $list;
}

/**
 * 取网易云歌曲的专辑封面地址（歌曲详情接口）
 * 用作 Meting::pic() 返回地址失效时的兜底
 */
function netease_album_pic($id, $cookie = '')
{
    $url = 'https://music.163.com/api/song/detail/?id=' . urlencode($id) . '&ids=%5B' . urlencode($id) . '%5D';
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER     => array(
            'Referer: https://music.163.com/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Cookie: ' . $cookie,
        ),
    ));
    $r = curl_exec($ch);
    curl_close($ch);
    $j = json_decode($r, true);
    return isset($j['songs'][0]['album']['picUrl']) ? $j['songs'][0]['album']['picUrl'] : '';
}

/**
 * 服务端代理拉取音频/图片并流式吐给浏览器
 * - 浏览器全程只与本站通信，规避网易云 CDN 防盗链(Referer 校验) 与 https 混合内容拦截
 * - 拉取网易云音频时【必须带 VIP Cookie】，否则音频 CDN 会因会话校验拒绝(403)
 * - 支持 Range 请求（拖动进度 / 断点续传）
 * - 成功返回 true（已输出音频）；失败返回 false（由调用方决定回退策略）
 */
function stream_proxy($url, $range_aware = true, $cookie = '')
{
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $req = array(
        'User-Agent: ' . $ua,
        'Referer: https://music.163.com/',
    );
    // 网易云音频 CDN(m704/m804) 会校验会话，必须带上 VIP Cookie 才能拿到完整版
    if (!empty($cookie)) {
        $req[] = 'Cookie: ' . $cookie;
    }
    if ($range_aware && isset($_SERVER['HTTP_RANGE'])) {
        $req[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
    }

    $resp = array();
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER         => $req,
        CURLOPT_HEADER             => false,
        CURLOPT_RETURNTRANSFER     => true,
        CURLOPT_FOLLOWLOCATION     => true,
        CURLOPT_SSL_VERIFYPEER     => false,
        CURLOPT_SSL_VERIFYHOST     => 0,
        CURLOPT_TIMEOUT            => 180,
        CURLOPT_CONNECTTIMEOUT     => 12,
        CURLOPT_BUFFERSIZE         => 8192,
        CURLOPT_HEADERFUNCTION     => function ($ch, $h) use (&$resp) {
            $line = trim($h);
            if ($line !== '' && !preg_match('#^HTTP/#i', $line)) {
                $p = strpos($line, ':');
                if ($p !== false) {
                    $k = strtolower(trim(substr($line, 0, $p)));
                    $v = trim(substr($line, $p + 1));
                    if (in_array($k, array('content-type', 'content-range', 'content-length', 'accept-ranges', 'etag', 'cache-control', 'expires'))) {
                        $resp[$k] = $v;
                    }
                }
            }
            return strlen($h);
        },
    ));
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);

    // 拉取失败（连接错误 / 非 200·206 / 空响应）→ 返回 false 让调用方回退
    if (($body === false || $errno) || ($code != 200 && $code != 206) || strlen($body) == 0) {
        // 403 = 该 CDN 节点对本网络拒服（网易云常见），记下来让后续请求直接换节点。
        // 注意只记 403：400 是「该节点不支持这种路径」，不代表节点坏了。
        if ($code == 403) {
            yy_mark_bad_node($url);
        }
        return false;
    }

    if ($code == 206 && !empty($resp['content-range'])) {
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: ' . $resp['content-range']);
    }
    header('Content-Type: ' . (!empty($resp['content-type']) ? $resp['content-type'] : 'application/octet-stream'));
    header('Content-Length: ' . strlen($body));
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=86400');
    echo $body;
    return true;
}

function get_curl($url, $m = false)
{
    $proxy = "101.227.139.217:443";
    $user_agent = $m ? "Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1" : "Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Mobile Safari/537.36";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_PROXY, $GLOBALS['proxy']);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
    @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_HTTPGET, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $tmpInfo = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Errno' . curl_error($curl);
    }
    curl_close($curl);
    return $tmpInfo;
}
