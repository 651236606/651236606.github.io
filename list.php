<?php

header("Content-type: text/html; charset=utf-8");

$api = 'https://www.qqmp3.vip/js/script.js?5.2.6';
$json = file_Data($api);
print_r($json);






function file_Data($url) {
	$header = array("cookie:tvfe_boss_uuid=69bb0cc4232cae3e; pgv_pvid=2342846108; pgv_pvi=2018757632; RK=7AO+AKaLPC; ptcz=69c4d7d6993373f7fcfbfc6f463d302fbe3d10ad0eafa2e329fed9822cf88fe4; pt2gguin=o00; pgv_si=s933901312; ts_last=y.qq.com/portal/playlist.html; ts_uid=3974656419; yqq_stat=0",);
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false); 
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false); 
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_REFERER,'https://y.qq.com/portal/playlist.html');
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36');
	curl_setopt($ch,CURLOPT_HEADER,0);
	if($header){
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	}
	@ $file=curl_exec($ch);
	curl_close($ch);
	return $file;
}
