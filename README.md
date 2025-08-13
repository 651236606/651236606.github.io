Rynkis' FM
========

### 个性化部署  

测试或部署前请根据运行环境新建 `.env` 文件，可指定：  
- SERVER_NAME，音乐运营商  
- SERVER_PLAYLIST，个人播放列表  
- SERVER_PLAYLIST_PL，播放列表 api 参数白名单，可用于共享接口给其它音乐应用  
- SERVER_PRIVATE, 私有曲库服务器 url
- NO_CACHE，是否启用 api 缓存（因为 Vercel 服务器在国外访问国内音乐运营商存在延迟，建议开启；国内测试或部署则无需开启）。  

### 语音合成  

语音合成功能暂时仅支持网易云音乐，在歌单简介输入内容即可启用，具体规则为：

```
index1: content1 // index1 为插入音乐位置，从 0 开始；content1 为语音合成内容
index2: content2 // 语音内容使用回车
```

```
0: Hello，你好，这里是rynkis' FM发出的第一句问候。
```

### 私有曲库  

私有曲库功能暂时仅支持网易云音乐，在歌单简介输入内容即可启用，具体规则为：

```
r + index1: filename1 index1 为插入音乐位置，从 0 filename1 为曲目文件名（不含扩展名）
r + index2: filename1
```

```
r0: Spotlight (feat. Yui Mugino)
```

### Usage  

```
git clone https://github.com/Shy07/fm.rynkis.com.git
cd fm.rynkis.com
yarn install
yarn dev
```
Then open http://localhost:3000 in your web browser and enjoy!  

### Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out [Next.js deployment documentation](https://nextjs.org/docs/deployment) for more details.

### Update log  
Cache netease api data with Vercel KV.  
Rewrite project with Next.js.  
Pack all front-end resources with webpack.  
Rewrite player with jQuery + ES6.  
Rewrite API with Ruby and use Sinatra Server.  
