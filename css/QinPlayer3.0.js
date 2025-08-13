
function QinPlayer(view,object,callback){
	
	/*默认配置*/
	this.varDefault = {
		autoplay: false,
		width: 600,
		height: 400,
		initTime: 0,
		speed: 1,
		volume: 1,
		title: '乐随享音乐播放器',
		video: {
			image: '',
			label: ''
		},
		logo: {
			src: '/favicon.ico',
			width: '10%',
			height: 'auto'
		},
		hideControls: false,
		listArray: [],
		openList: false,
		menulist: [
			{name:"刷新",href:"javascript:QinPlayer.load()"},
			{name:"保存",href:"javascript:QinPlayer.down()",hr:true},
			{name:"播放/暂停<i>空格键SPACE KEY</i>",href:"javascript:QinPlayer.paused()"},
			{name:"全屏<i>ESC退出</i>",href:"javascript:QinPlayer.openFull()"},
			{name:"快进5s<i>向右箭头&rarr;</i>",href:"javascript:QinPlayer.FF(5);"},
			{name:"快退5s<i>向左箭头&larr;</i>",href:"javascript:QinPlayer.FB(5);"},
			{name:"音量增大<i>向上箭头&uarr;</i>",href:"javascript:;",disable:true},
			{name:"音量减小<i>向下箭头&darr;</i>",href:"javascript:;",disable:true}
		]

	}

	/*载入播放器UI*/
	var QinUI = '<link rel="stylesheet" href="/css/font_2046790_z61wzrwmsks.css" />';
		QinUI+= '<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">';
		QinUI+= '<div class="qp-wrap">';
		QinUI+= '<video onclick="QinPlayer.toggleControl()" id="player" webkit-playsinline="true" x5-playsinline playsinline x5-video-player-type="h5" x5-video-player-fullscreen="false" x5-video-orientation="portraint" preload="meta"></video>';
		QinUI+= '<div class="qp-logo"></div>';
		QinUI+= '<div class="qp-top">';
		QinUI+= '<div class="qp-top-box">';
		QinUI+= '<div class="qp-title"></div>';
		QinUI+= '<div class="qp-top-tool">';
		QinUI+= '<a class="iconfont icon-more tool-btn tool-more" href="javascript:QinPlayer.about();"></a>';
		QinUI+= '</div>';
		QinUI+= '</div>';
		QinUI+= '</div>';
		QinUI+= '<div class="qp-controls">';
		QinUI+= '<div class="qp-time">';
		QinUI+= '<div class="currentTime" id="currentTime">00:00</div>';
		QinUI+= '<div class="slider"></div>';
		QinUI+= '<div class="duration" id="duration">00:00</div>';
		QinUI+= '</div>';
		QinUI+= '<div class="qp-con-box">';
		QinUI+= '<div class="qp-con-l">';
		QinUI+= '<a class="iconfont icon-pause icon-play" onclick="QinPlayer.paused()"></a>';
		QinUI+= '<a class="iconfont icon-refresh" onclick="QinPlayer.load()"></a>';
		QinUI+= '<a class="iconfont icon-next" onclick="QinPlayer.nextPlay()"></a>';
		QinUI+= '<span class="playTip" id="playTip"></span>';
		QinUI+= '<div class="qp-con-r">';
		QinUI+= '<a class="iconfont icon-list" onclick="QinPlayer.openList();"></a>';
		QinUI+= '<a class="iconfont icon-ofull" onclick="QinPlayer.openFull()"></a>';
		QinUI+= '<a class="iconfont icon-cfull" onclick="QinPlayer.closeFull()"></a>';
		QinUI+= '</div>';
		QinUI+= '</div>';
		QinUI+= '</div>';
		QinUI+= '</div>';
		QinUI+= '<div class="qp-panel-wrap panel-list-wrap qinchen-anim qinchen-anim-left"><div class="panel-list"><div class="qp-list"></div></div><a class="iconfont icon-close-x close-list" onclick="QinPlayer.closeList()"></a></div>';
		QinUI+= '</div>';
	$(view).html(QinUI);
		
	
	
	var player = $("#player")[0],
		vars = {},/*全局变量*/
		that = this;
	
		vars.listObj = [];
		vars.playlist = [];
		vars.playid = 0;
		vars.currentItem = {};
	
	this.init = function(callback){
		Object.assign(this.varDefault, object);
		var data = this.varDefault;
		if(!isNaN(data.width)){
			$(view).width(data.width+"px");
		}else{
			$(view).width(data.width ? data.width : '100%');
		}
		if(!isNaN(data.height)){
			$(view).height(data.height+"px");
		}else{
			$(view).height(data.height ? data.height : '400px');
		}
		if(player && data.video.src){
			player.playbackRate = data.speed>0&&data.speed<=10 ? data.speed : 1;
			if(callback&&typeof(callback)==="function")callback({name:'video',callback:data.video});
		}else{
			if(callback&&typeof(callback)==="function")callback('err');
		}
		$('.qp-title').text(data.video.label);	
		$('.qp-logo').html('<img width="100%" src="'+data.logo.src+'"/>').css({'width':data.logo.width,'height':data.logo.height});
		player.volume = data.volume;
		if(data.listArray.length > 0){
			if(callback&&typeof(callback)==="function")callback({name:'list',callback:data.listArray});
			if(data.openList){
				that.openList()
			}
		}
	}
	
	this.set = function(para, str){
		object[para] = str;
		that.init();
	}
	this.load = function(){
		that.PlayList(vars.playid);
		that.state('load','已刷新');
	}
	
	$(function(){
		that.init(function(e){
			that.Slider(".slider",function(e){
				var seek = player.duration / 100 * e;
				player.currentTime = seek;
			},14);
			if(e!='err'){
				if(e.hideControls==true){
					that.toggleControl('hide')
				}
				if(e.name == 'video'){
					vars.playlist.push(e.callback);
					that.PlayList(vars.playid);
				}
				if(e.name == 'list'){
					$('.icon-list,.icon-next').show();
					that.initList(e.callback);
				}
				//that.PlayList(vars.playid);
				if(that.varDefault.autoplay){
					that.play();
				}
			}
		});
		console.log('乐随享音乐播放器基于QinPlayer\n\n访问主页：%c乐随享-https://yuesuixiang.com/','color: #1fbc28;display:block;font-size: 16px;');
	});

	this.state = function(state,stateName){
		vars.state = state;	
		vars.stateName = stateName;	
		vars.time = new Date().getTime();
		//$('#playTip').html(stateName);
		if(callback&&typeof(callback)==="function")callback(vars);
	}	
	
	/*控制方法开始*/
	this.play = function(){
		if(!player.src){
			that.state('undefined','播放地址未定义');
			return;
		}
		player.play();
		/*微信客户端自动播放方案*/
		document.addEventListener("WeixinJSBridgeReady", function () { 
			player.play();
		}, false);
	}
	this.pause = function(){
		player.pause();
	}
	this.paused = function(){
		if(player.paused){
			this.play();
		}else{
			this.pause();
		}
	}
	this.nextPlay = function (){
		vars.playid = Number(vars.playid)+1;
		if(vars.playid >= vars.playlist.length){
			vars.playid = 0;
		}
		that.PlayList(vars.playid);
		that.play();
	}	
	this.PlayList = function (playid = 0){
		vars.playid = playid;
		$('.icon-item').removeClass('currplay');
		$('#item_'+vars.playid).addClass('currplay');
		vars.currentItem = vars.playlist[playid];
		var src = vars.currentItem.src;
		var label = vars.currentItem.label;
		var image = vars.currentItem.image;
		var type = vars.currentItem.type;
		var lrc = vars.currentItem.lrc;
		var live = vars.currentItem.live;
		if(type=='m3u8' || type=='flv' || type=='live'){
			hls(src,'play');
			if(live==1){
				$('.qp-time').hide();
				$('.icon-refresh').show();
				$('#playTip').html("LIVE");
			}else{
				$('.qp-time').show();
				$('.icon-refresh').hide();
				$('#playTip').html("");
			}
		}else{
			player.src = src;
			$('.qp-time').show();
		}
		that.play();
		player.poster = image?image:'';
		$('.qp-title').text(label ? label : 'QinPlayer音视频播放器');
		that.closeList();	//关闭列表
		that.state('currentItem',vars.currentItem);
	}
	this.showLoading = function(icon=4){
		var loading = '<div class="loading qinchen-anim qinchen-anim-scale"><i class="iconfont icon-loading-'+icon+' qinchen-anim qinchen-anim-rotate qinchen-anim-loop"></i></div>';
		$(view).append(loading);	
	}
	this.hideLoading = function(){
		$('.loading').remove();	
	}
	this.msg = function(text,time=3000){
		if(text){
			var msg = '<div class="msg qinchen-anim qinchen-anim-scale"><i class="iconfont icon-tip" style="color:#3ca257;font-size:18px;"></i> '+text+'</div>';
			$(view).append(msg);
			var w = $('.msg').width();
			var h = $('.msg').height();
			$('.msg').css({
				'margin-left':- w / 2 - 10 +'px',
				'margin-top':- h / 2 - 10 +'px',
				'opacity': 1
			});
			setTimeout(function (){
				$('.msg').remove();
			}, time);
		}
	}

	this.dialog = function(text,title,success,cancel){
		if(typeof title === "function"){
			cancel = arguments[2];
			success = arguments[1];
			title = '提示';
		}
		this.onSuccess = function(){
			that.onCloseDialog();
			if(typeof(success)==="function"){
				success();
			}
		}
		this.onCancel = function(){
			that.onCloseDialog();
			if(typeof(cancel)==="function"){
				cancel();
			}
		}
		this.onCloseDialog = function(){
			$('.dialog').addClass('qinchen-anim-fadeout');
			setTimeout(function (){
				$('.dialog').remove();
			}, 1000);
		}
		var dialog = '<div class="dialog qinchen-anim qinchen-anim-scale">';
			dialog+= '<div class="dialog-title">'+title+'</div>';
			dialog+= '<span class="iconfont icon-close-x dialog-close" onclick="QinPlayer.onCloseDialog()"></span>';
			dialog+= '<div class="dialog-content">'+text+'</div>';
			dialog+= '<div class="dialog-buttons">';
			dialog+= '<button class="dialog-success" onclick="QinPlayer.onSuccess()">确定</button>';
			if(cancel)dialog+= '<button class="dialog-cancel" onclick="QinPlayer.onCancel()">取消</button>';
			dialog+= '</div>';
			dialog+= '</div>';
			$('.qp-wrap').append(dialog);
			var w = $('.dialog').width();
			var h = $('.dialog').height();
			$('.dialog').css({
				'margin-left':- w / 2 +'px',
				'margin-top':- h / 2 +'px',
				'opacity': 1
			}).show();
	}
	this.closeMsg = function(){
		$('.msg').remove();
	}
	this.toggleControl = function(e){
		if(e && e == 'hide'){
			$('.qp-controls,.qp-top').addClass('hide-controls');
			return false;
		}
		if(e && e == 'show'){
			$('.qp-controls,.qp-top').removeClass('hide-controls');
			return false;
		}
		if($('.hide-controls').length > 0 ){
			$('.qp-controls,.qp-top').removeClass('hide-controls');
		}else{
			$('.qp-controls,.qp-top').addClass('hide-controls');	
		}
	}
	this.openFull = function(){
		if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
			that.play();
			if(player.webkitEnterFullscreen)player.webkitEnterFullscreen()
			if(player.webkitRequestFullScreen)player.webkitRequestFullScreen()
			if(player.mozRequestFullScreen)player.mozRequestFullScreen()
			if(player.oRequestFullScreen)player.oRequestFullScreen()
			if(player.msRequestFullscreen)player.msRequestFullscreen()
		}else{
			var docElm = $(view)[0]; 
			//W3C
			if(docElm.requestFullscreen){
				docElm.requestFullscreen();
			}
			//FireFox
			else if (docElm.mozRequestFullScreen) {
				docElm.mozRequestFullScreen();
			}
			//Chrome等
			else if (docElm.webkitRequestFullScreen) {
				docElm.webkitRequestFullScreen();
			}
			//IE11
			else if (elem.msRequestFullscreen) {
				elem.msRequestFullscreen();
			}
		}
	}
	this.closeFull = function(){
		if (document.exitFullscreen) {
			document.exitFullscreen();
		}
		else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		}
		else if (document.webkitCancelFullScreen) {
			document.webkitCancelFullScreen();
		}
		else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		}
	}
	this.openList = function(){
		if($(view).width() > 800){$('.panel-list-wrap').css('width','50%');}
		$('.panel-list-wrap').show();
	}
	this.closeList = function(){
		$('.panel-list-wrap').fadeOut(200);
	}
	this.toggleList = function(){
		if($('.panel-list-wrap').css('display')=='none'){
			that.openList();
		}else{
			that.closeList();
		}
	}
	window.onresize = function() {
		var isFull=!!(document.webkitIsFullScreen || document.mozFullScreen || 
			document.msFullscreenElement || document.fullscreenElement);
		if (isFull==false) {
			$('.icon-ofull').css('display','block');
			$('.icon-cfull').css('display','none');
			$(view).css({"height":that.varDefault.height,"width":that.varDefault.width});
			//that.toggleControl('show');
			that.state('closeFullscreen','退出全屏');
		}else{
			$('.icon-ofull').css('display','none');
			$('.icon-cfull').css('display','block');
			$(view).css({"height":"100%","width":"100%"});
			//setTimeout(function(){that.toggleControl('hide')},1000);
			that.state('openFullscreen','进入全屏');
		}
	}

	$("#player").on('webkitendfullscreen', function(e) {
		// Ios中退出全屏事件
		cfull();
	}).on('x5videoexitfullscreen', function(e) {
		// Android中退出全屏事件
		cfull();
	});
	function cfull() {
		setTimeout(function(){that.play()},500);
		that.state('closeFullscreen','退出全屏');
	}
	this.FB = function (s=5){
		//快退
		if(player.currentTime > 10){
			player.currentTime = player.currentTime-s;
		}
	}
	this.FF = function (s=5){
		//快进
		if(player.currentTime+10 < player.duration){
			player.currentTime = player.currentTime+s;
		}
	}
	/*下载方法*/
	this.down = function(){
		const downloadFileA = document.createElement('a');
		document.body.append(downloadFileA);
		downloadFileA.href=player.currentSrc;
		downloadFileA.download = new Date().getTime();
		downloadFileA.rel = 'noopener noreferrer';
		downloadFileA.click();
		document.body.removeChild(downloadFileA);
		//window.location.href=player.currentSrc;
	}
	$(view).on('mousedown',function(e){
		//屏蔽浏览器右键菜单
		$(view).bind("contextmenu",function(){
			return false;
		})
		var menulist = that.varDefault.menulist;
		var mls = menulist.length>0 ? menulist : [];
		var key=e.which;
		if(key==3){
			if($('.menu').length>0){$('.menu').remove();}
			var menu='';
			for(var s in mls){
				menu+='<a class="'+(mls[s].disable?'disable':'')+'" href="'+mls[s].href+'">'+mls[s].name+'</a>'+ (mls[s].hr?'<hr/>':'');
			}
			$('<div class="menu">'+menu+'</div>').appendTo('.qp-wrap');
			var x = e.clientX;
			var y = e.clientY;
			var vx = $(view).offset().left;
			var vy = $(view).offset().top;
			$('.menu').show().css({left:x-vx,top:y-vy});
		}
		//点击任意部位隐藏
		$(document).on('click',function(){
			$('.menu').hide();
		})
	})
	$(view).hover(function(e){
		if(!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)){
			var lastX =0, lastY = 0, rtime;
			$(view).mousemove(function(e) {
				that.toggleControl('show');
				$(view).css('cursor','inherit');
				clearTimeout(rtime);
				rtime = setTimeout(function (){
					that.toggleControl('hide');
					$(view).css('cursor','none');
				}, 5000);
			});
		}
	});
	this.about = function(){
		that.dialog('乐随享音乐播放器基于<a target="_blank" href="http://qinchen.vip/QinPlayer3.0/">QinPlayer</a><br /><p style="text-align:right">访问主页： <a target="_blank" href="https://yuesuixiang.com/">乐随享</a></p>','关于播放器');
	}

	
	function hls(src,paused){
		LoadJs('http://css.ost.online/QinPlayer/hls.min.js',function(){
			var wHls = window.Hls;
			if(wHls.isSupported()){// && (src.indexOf("m3u8")!=-1 || src.indexOf(".flv")!=-1)) {// && !/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)
				var hls = new Hls();
				hls.loadSource(src);
				hls.attachMedia(player);
				hls.on(wHls.Events.MANIFEST_PARSED,function() {
					if(paused=='play')player.play();
				});
 			}else{
				player.src = src;
				if(paused=='play')player.play();
			}
			loadedmetadata();
		});
	}
	
	function error(){
		if(player.error.code===1){
			vars.state = '用户终止！';
		}else if(player.error.code===2){
			vars.state = '网络错误！';
		}else if(player.error.code===3){
			vars.state = '解码错误！';
		}else if(player.error.code===4){
			vars.state = 'URL无效！';
		}else{
			vars.state = '未知错误！';
		}
		that.msg(vars.state);
		that.state('error',vars.state);
	}
	function loadedmetadata(e) {
		that.state('loadedmetadata','元数据已加载');
		vars.loadedmetadata = true;
	}
	function progress(){
		if(player.buffered.length > 0){
			var buffered = player.buffered.end(0);
			var setbuffered = (buffered / player.duration * 100).toFixed(2) +"%";
			$('.slider .buffered').css('width',setbuffered);
			if(setbuffered == '100.00%'){
				that.state('bufferComplete','缓冲完成');
				return;
			}
			that.state('buffer','已缓冲'+setbuffered);
		}
	}
	function play(){
		$('.icon-pause').removeClass('icon-play');
		that.closeMsg();
		that.state('play','已播放');
	}
	function pause(){
		$('.icon-pause').addClass('icon-play');
		that.msg('已暂停');
		that.state('pause','已暂停');
	}
	function ended(){
		player.currentTime=0;
		that.state('ended','播放完成');
		that.msg('播放完成');
		if(vars.playlist.length >　1)that.nextPlay();
	}
	function canplay(){
		that.hideLoading();
		that.state('canplay','已就绪');
	}
	function playing(){
		that.state('playing','正在播放');
	}
	function durationchange(){
		var duration = tot(player.duration);
		$('#duration').text(duration);
	}
	function timeupdate(){
		var currentTime = tot(player.currentTime);
		$('#currentTime').text(currentTime);
		var setSlider = player.currentTime / player.duration * 100 +"%";
		$(".thumb").css("width",setSlider);
	}
	function waiting(){
		that.showLoading();
		that.state('waiting','加载中...');
	}
	function volumechange(){
		var volumeSlider =  Math.ceil(player.volume * 100) +"%";
		that.msg("当前音量："+volumeSlider);
		that.state('volumechange',"当前音量："+volumeSlider);
	}
	function ratechange(){
		that.state('ratechange',"播放速率已改变");
	}
	
	
	//错误监听
	player.addEventListener("error",error);
	//元数据已加载
	player.addEventListener('loadedmetadata',loadedmetadata);
	//监听播放
	player.addEventListener("play",play);
	//监听暂停
	player.addEventListener("pause",pause);
	//监听播放完成
	player.addEventListener("ended",ended);
	//提示该视频已准备好开始播放
	player.addEventListener("canplay",canplay);
	//当视频在已因缓冲而暂停或停止后已就绪时触发
	player.addEventListener("playing",playing);
	//提示视频的时长已改变
	player.addEventListener("durationchange",durationchange);
	//目前的播放位置已更改时，播放时间更新
	player.addEventListener("timeupdate",timeupdate);
	//视频加载等待。当视频由于需要缓冲下一帧而停止，等待时触发
	player.addEventListener("waiting",waiting);
	//监听音量改变
	player.addEventListener("volumechange",volumechange);
	//监听客户端正在请求数据
	player.addEventListener("progress",progress);
	//监听客户端正在请求数据
	player.addEventListener("ratechange",ratechange);
	/*监听全屏状态改变事件
	document.addEventListener("fullscreenchange",fullscreenchange);
	document.addEventListener("mozfullscreenchange",fullscreenchange);
	document.addEventListener("webkitfullscreenchange",fullscreenchange);
	document.addEventListener("MSFullscreenChange",fullscreenchange);
	*/	
	document.onkeydown = function(e){
		e = e || event;
		e.returnValue=false;
		if(e.keyCode == 32 || e.keyCode == 108){
			that.paused();
		}
		if(player.currentTime > 10 && e.keyCode == 37){
			player.currentTime = player.currentTime-5;
			that.msg('快退5秒',1000);
		}
		if(player.currentTime+10 < player.duration && e.keyCode == 39){
			player.currentTime = player.currentTime+5;
			that.msg('快进5秒',1000);
		}
		if(player.volume+.1 <= 1 && e.keyCode == 38){
			player.volume = player.volume+.1;
		}
		if(player.volume-.1 >= 0 && e.keyCode == 40){
			player.volume = player.volume-.1;
		}
		if(e.keyCode == 116){
			that.load();
		}
	}
	
	this.cklist = function(url,listid){
		var sublist = '';
		if($('#'+listid).attr('loaded') == 1){
			$('#'+listid).removeClass('icon-close').attr({'loaded':0}).next('.sublist').hide();
			return;
		}else
		if($('#'+listid).attr('view') == 1){
			$('#'+listid).addClass('icon-close').attr({'loaded':1}).next('.sublist').show();
			return;
		}
		$.get(url,function(res){
			if(res){
				for(var s in res){
					var plist = 'p_'+listid+'_'+s;
					if(res[s].list_src){
						var label = res[s].label ? res[s].label : '列表'+ (Number(s)+1),
							opened = res[s].opened ? res[s].opened : 0,
							list_src = res[s].list_src;
						sublist += '<h4 class="toggList iconfont icon-open" id="'+plist+'" onclick="QinPlayer.cklist(\''+list_src+'\',\''+plist+'\')">'+label+'</h4><div class="sublist qinchen-anim qinchen-anim-upbit" id="list_'+plist+'"></div>';
						if(opened==1){
							that.cklist(list_src,plist);
						}
					}else
					if(res[s].src){
						var playinfo = {
							label: res[s].label,
							src: res[s].src,
							image: res[s].image?res[s].image:''
						};
						vars.playlist.push(playinfo);
						var itemnum = vars.playlist.length - 1;
						sublist += '<a class="iconfont icon-item" id="item_'+itemnum+'" onclick="QinPlayer.PlayList('+itemnum+')">'+res[s].label+'</a>';
					}
				}
				$('#list_'+listid).html(sublist).show().prev('h4').addClass('icon-close').attr({'loaded':1,'view':1});
				if(vars.playlist.length>0 && vars.playid==0 && that.varDefault.autoplay==true && !player.src){
					that.PlayList(0);
				}
			}
		},"json");
	}
	this.initList = function (listObj){
		var ul = '';
		if(vars.playlist.length > 0){
			var itemnum = 0;
			ul += '<a class="iconfont icon-item" id="item_0" onclick="QinPlayer.PlayList(0)">'+vars.playlist[0].label+'</a>';
		}
		if(Object.prototype.toString.call(listObj) === '[object Array]'){
			for(var i in listObj){
				var plist = 'p_'+i;
				var label = listObj[i].label ? listObj[i].label : '列表'+ (Number(i)+1),
					opened = listObj[i].opened ? listObj[i].opened : 0,
					list_src = listObj[i].list_src;
				ul += '<h4 class="toggList iconfont icon-open" id="'+plist+'" onclick="QinPlayer.cklist(\''+list_src+'\',\''+plist+'\')">'+label+'</h4><div class="sublist qinchen-anim qinchen-anim-upbit" id="list_'+plist+'"></div>';
				if(opened==1){
					that.cklist(list_src,plist);
				}
			}
		}
		$('.qp-list').html(ul);
	}

	
	/*slider滑块*/
	this.Slider=function(slider_view,callback,size=8){var div='<style>'+slider_view+' *{-webkit-transition-duration: .3s;transition-duration: .3s;}.track{width: 100%;height: 2px;background-color: #d6d6d6;position: relative;}.buffered{background-color:#808080;height: inherit;width:0;}.thumb{height: inherit;background-color:#5FB878;position: absolute;top: 0;width:0;max-width:100%;}'+slider_view+' .thumb:before,'+slider_view+' .thumb:after{content:"";position: absolute;width:'+size+'px;height:'+size+'px;border-radius: 50%;background-color:#ffffff;right: -'+(size/2)+'px;top: 50%;margin-top: -'+(size/2)+'px;}'+slider_view+' .thumb:after{right:-5px; margin-top:-5px;width:8px; height:8px;}</style><div class="track"><div class="buffered"></div><div class="thumb"></div></div>';$(slider_view).html(div);var startX,moveX,endX,state=false;var touchEvents={touchstart:"touchstart",touchmove:"touchmove",touchend:"touchend",initTouchEvents:function(){if(!isMobile()){this.touchstart="mousedown";this.touchmove="mousemove";this.touchend="mouseup";}}};touchEvents.initTouchEvents();$(slider_view)[0].addEventListener(touchEvents.touchstart,function(e){startX=e.pageX?e.pageX:e.changedTouches[0].pageX;state=true;});$(slider_view)[0].addEventListener(touchEvents.touchmove,function(e){if(!state)return;moveX=e.pageX?e.pageX:e.changedTouches[0].pageX;var trackL=$(slider_view+' .track').offset().left;var trackW=$(slider_view).width();var thumbW=(moveX-trackL)/trackW*100;if(thumbW>=100){endX=100;}else if(thumbW<=0){endX=0;}else{endX=Math.round(thumbW);}$(slider_view+' .thumb').css('width',endX+"% \!important");});$(slider_view)[0].addEventListener(touchEvents.touchend,function(e){if(typeof callback==="function"){callback(endX);}state=false;});function isMobile(){if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)){return true;}else{return false;}}}
	/*时间格式转换器 - 00:00*/
	function tot(s) {if(s > 0){var MM = Math.floor(s / 60);var SS = s % 60;if (MM < 10){MM = "0" + MM;}if (SS < 10){SS = "0" + SS;}var min = MM + ":" + SS;return min.split('.')[0];}else{return "00:00";}}
	/*动态加载JS*/
	function LoadJs(url, callback) {var head = document.getElementsByTagName('head')[0];var script = document.createElement('script');script.type = 'text/javascript';script.src = url;if(typeof(callback)=='function'){script.onload = script.onreadystatechange = function () {if (!this.readyState || this.readyState === "loaded" || this.readyState === "complete"){callback();script.onload = script.onreadystatechange = null;}};}head.appendChild(script);}
	function decode(input){rv = window.atob(input);rv = escape(rv);rv = decodeURIComponent(rv);return rv;}
	
}