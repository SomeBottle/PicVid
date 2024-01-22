# PicVid
A simple PHP script that hides video data within image files.   

## Demo  

Using [a hook](#on-the-one-handnot-suggested) based on Hls loader:   

* https://somebottle.gitee.io/bottlecos/picvid_demo/

Using [`EXT-X-BYTERANGE`](#on-the-other-hand) option in m3u8 file:   

* [https://somebottle.gitee.io/bottlecos/picvid_demo/oyashirosama.html](https://somebottle.gitee.io/bottlecos/picvid_demo/oyashirosama.html)  

AliExpress image hosting has adopted a strategy of converting images to the webp format, so the previous demo based on AliExpress image hosting is no longer functional. The demo has now been migrated to Gitee Pages for learning and reference purposes only. (Video loading may be slightly slow).  

## Thanks to
* [用图床看视频是什么体验](https://i-meto.com/hlsjs-upimg-wrapper/) by metowolf   
* [用图床传视频](https://akarin.dev/2020/02/07/alicdn-video-hosting/) by akarin.dev  

## Requirements  
* Allow PHP function: **shell_exec**  
* Install and correctly configure **ffmpeg**'s environment variables.  

## Config  
Edit **pv.php**:  
```php
$output_dir = 'output';  /*output directory(based on the location of pv.php)*/  
$if_windows = true;  /*whether you are using Windows*/  
$clearLogOnStart = true;  /*clear executeLog.log when running the script*/  
$maxTSFileSize = 5242880; /*(In bytes) Maximum size of each split out ts file (more than that will be compressed) */  
$mergeTSUpTo = 2097152; /*(In bytes,cannot be set over $maxTSFileSize) The max ts file size generated when merging several small ts files*/  
$disguisePic = __DIR__ . '/small.png'; /*The picture used for disguising, we suggest using a jpg or png file*/  
$exitBigTSNum = 0.50; /*(×100%)When large(>$maxTSFileSize) ts files' total num account for more than $exitBigTSNum, the script will exit and recommend you to use -recomp*/
$useByteRange = true; /*Will you use the BYTERANGE option in the generated m3u8 file, you can also use '--nobr' to disable it in the command line*/
```

Edit **uploadAPI.php**:  
```php
/*PV.PHP UPLOAD PIC, the first return value:<pic url> or <bool:false> in stand of error*/
function PVUpload($path){
	//pic upload function
	$rawdata=
	$imgurl=
	return [(empty($imgurl) ? false : $imgurl),$rawdata];
}   
```
You should write your own function that uploads pics to the picbed through the picbed's API.  
* **$path** is the **absolute path** of the file which is to be uploaded.  
* **$rawdata** is the raw response that picbed's API returns , in many cases , it is in JSON format.  
* **$imgurl** is the url of the uploaded pic , usually it is contained in **$rawdata**.  
* There are 2 values in return: the first one is **the url of the uploaded pic** , the second one is **rawdata**. When **error occurs** during uploading, the first value should be set to **false**.

for example , if you make a function ```upload($path)``` to upload each picture , and it returns with the JSON below:  
```json
{"code":0,"data":{"size":"264","url":"https://s1.ax1x.com/2020/09/16/wgnGxf.png"}}  
```   
then:  
```php
/*PV.PHP UPLOAD PIC, the first return value:<pic url> or <bool:false> in stand of error*/
function PVUpload($path){
	//pic upload function
	$rawdata=upload($path);
	$parsed_data=json_decode($rawdata,true);
	$imgurl=$parsed_data['data']['url'];
	return [(empty($imgurl) ? false : $imgurl),$rawdata];
}   
```

## Bypass uploading

By default, `pv.php` will upload the disguised pics to picbeds through `uploadAPI.php`.  


If you want to bypass the upload process, and just save these pics to local, you can use `pv_bypass_upload.php` and `saveToLocal.php` instead.  

In this way, your directory structure will be as follows:  
```shell
YourDir
├── executeLog.log # ffmpeg output logs
├── output # output directory
│   ├── 0.ts # ts files
│   ├── 1.ts
│   ├── pics # disguised pictures you saved
│   │   ├── temppic1440-0.jpg
│   │   └── temppic999-1.jpg
│   ├── video.m3u8.jpg # m3u8 file disguised in jpg
│   └── video.real.m3u8 # m3u8 file without disguise (recommend)
├── potato.jpg # disguising picture
├── pv_bypass_upload.php 
├── rick.mp4 # original video
└── saveToLocal.php # required by pv_bypass_upload.php
```  

You may want to upload those pics to picbeds manually, and the URLs in m3u8 file need to be updated.   

Fortunately, you will be asked to type in the URL of the parent directory of the place you would like to upload the disguised pictures.  

The prompt will look like this:  

```bash
Example: If you are to upload pic.png to https://example.com/abc/pic.png
You should type in 'https://example.com/abc/'
Please input the parent URL of the pics to be uploaded: https://test/

Preview: Pic test.png corresponds to https://test/test.png
Type in 'yes' to confirm: yes
OK.
```  

In the example above, I use `https://test/` as the parent URL, and the generated m3u8 file will look like this:  

```m3u8
...
#EXTINF:10.08,
#EXT-X-BYTERANGE:3408252@3610
https://test/temppic941-0.jpg
...
```

If I leave the input blank, the generated m3u8 file will be as follows:  

```m3u8
...
#EXTINF:10.08,
#EXT-X-BYTERANGE:3408252@3610
temppic941-0.jpg
...
```

## Usage  
Type at the command line: ```php <pv.php|pv_bypass_upload.php> [--nobr] [--recomp] -v videofile```  

* I suggest you **delete all of the files in the output folder** before running the script.  
* Maybe only available for ```x264``` encoded video now.  
* Video file is in the same directory as **pv.php**  
* The script will automatically compress large TS Files through FFmpeg's ```crf``` option.  
* If you use the option ```--nobr``` , it won't use EXT-X-BYTERANGE option in the generated m3u8 file.  
* If you use the option ```--recomp``` , it will try to re-encode your original video **in order to make it easier to be split.**  
* If you used ```--recomp``` and still get a **TS compression failure** ，please compress the video file by yourself, or change the config item ```$maxTSFileSize```.    

## Notice  
* If space exists in the absolute path where **pv.php** lies , it may return **"Video bitrate get failed"** error.  

## How to play  
* Use [Hls.js](https://github.com/video-dev/hls.js) , also you can take a look at the demo.  
* After running the script , you will get two files in the output folder: ```video.real.m3u8``` and ```video.m3u8.<suffix of disguise pic>```  
### On the one hand(Not suggested)  
Assume that your disguise pic's suffix is ```.png```,and you've got ```video.m3u8.png``` in the output folder. With **hls.js** you can write like this:  
```javascript
 var video = document.getElementById('video');
  var videoSrc = 'video.m3u8.png';
  var config = {
	  debug:true
  };
  (function (Hls, offset) {
    var load = Hls.DefaultConfig.loader.prototype.load;
    Hls.DefaultConfig.loader.prototype.load = function (context, config, callbacks) {
        if (context.type === 'manifest' || context.type === 'level' || context.responseType === 'arraybuffer') {
            var onSuccess = callbacks.onSuccess;
            callbacks.onSuccess = function (response, stats, context) {
                // .m3u8文件载入时不要给截取了
                if (response.url.endsWith('.m3u8')) return onSuccess.call(this, response, stats, context);
                response.data = response.data.slice(offset);
                return onSuccess.call(this, response, stats, context);
            };
        }
        return load.call(this, context, config, callbacks);
    }
  })(Hls, 69); // Video bytes start from pos 69
 if (Hls.isSupported()) {
    var hls = new Hls(config);
    hls.loadSource(videoSrc);
    hls.attachMedia(video);
  }else{
    //not supported
  }  
```
By using a custom loader which helps you ignore the picture in front of the m3u8 file, you can play the video successfully.**However**, due to the nonsupport of Media Source Extension on iOS Safari, you can't even load it in iOS Safari.  

### On the other hand  

⚠️ Currently not working **without** `EXT-X-BYTERANGE`, you need to set `$useByteRange = true;` in the [config](#config) so as to play in this way.  

-----

Just put aside ```video.m3u8.png``` and take a look at ```video.real.m3u8```, you just need to delete the hook code of Hls and add codes to make it alternative for natively m3u8 support browser(Such as Safari):  
```javascript
 var video = document.getElementById('video');
  var videoSrc = 'video.real.m3u8';
  var config = {
	  debug:true
  };
 if (Hls.isSupported()) {
    var hls = new Hls(config);
    hls.loadSource(videoSrc);
    hls.attachMedia(video);
  }else{
    video.src = videoSrc;
    alert('你的浏览器自带支持m3u8，如果无法播放请发issue\nYour browser natively support m3u8 playing, create issue if it doesn\'t work.');  
  }  
```
In this way, your video is available in almost all of the main stream web browser.  

### Additional notice  

* Due to the [security policy](https://github.com/google/shaka-player/issues/2227) of the web browser , you won't able to play **disguised videos** if the picbed has a restriction on **CORS policy**.  

## Special thanks  
* **Shota** for feedbacking the problem in Safari.  
* **Ohmyga** and **Yueer** for helping test the new ways.  

------------
**MIT License.**  
