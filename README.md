# PicVid
A simple PHP Script to transform Video into pics that can be uploaded to picbeds.

## Demo  
* [https://pv.xbottle.top/demo](https://pv.xbottle.top/demo)  

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

## Usage  
Type at the command line: ```php pv.php [-recomp] -v videofile```  

* I suggest you **delete all of the files in the output folder** before running the script.  
* Maybe only available for ```x264``` encoded video now.  
* Video file is in the same directory as **pv.php**  
* The script will automatically compress large TS Files through FFmpeg's ```crf``` option.  
* If you use the option ```-recomp``` , it will try to re-encode your original video **in order to make it easier to be split.**  
* If you used ```-recomp``` and still get a **TS compression failure** ，please compress the video file by yourself, or change the config item ```$maxTSFileSize```.    

## Notice  
* If space exists in the absolute path where **pv.php** lies , it may return **"Video bitrate get failed"** error.  

## How to play  
* Use [Hls.js](https://github.com/video-dev/hls.js) , also you can take a look at the demo.  
* After running the script , you will get two files in the output folder: ```video.real.m3u8``` and ```video.m3u8.<suffix of disguise pic>```  
### On the one hand  
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
                response.data = response.data.slice(offset);
                return onSuccess.call(this, response, stats, context);
            };
        }
        return load.call(this, context, config, callbacks);
    }
  })(Hls, 69);
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
In this way,your video is available in almost all of the main stream web browser.When it comes to deal with m3u8 file,you can upload it to any of the file storages because it is in small file size.(I suggest ```catbox.moe``` here)  

## Special thanks  
* **Shota** for feedbacking the problem in Safari.  
* **Ohmyga** and **Yueer** for helping test the new ways.  

------------
**MIT License.**  
