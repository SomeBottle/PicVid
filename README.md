# PicVid
A simple PHP Script to transform Video into pics that can be uploaded to picbeds.

## Demo  
* [https://pv.xbottle.top/demo](https://pv.xbottle.top/demo)  

## Thanks to
* [用图床看视频是什么体验](https://i-meto.com/hlsjs-upimg-wrapper/) by metowolf   

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

* Video file is in the same directory as **pv.php**  
* If you use the option ```-recomp``` , it will try to re-encode your video **in order to make it easier to be split.**  
* If your video file size is small , do not use the option ```-recomp``` , because it may make it bigger.  
* If you used ```-recomp``` and still get a **TS compression failure** ，please compress the video file by yourself, or change the config item ```$maxTSFileSize```.    

## Notice  
* If space exists in the absolute path where **pv.php** lies , it may return **"Video bitrate get failed"** error.  

## How to play  
Use [Hls.js](https://github.com/video-dev/hls.js) , also you can take a look at the demo.  
* I haven't found a way to parse disguised ts source on Safari of iOS because it doesn't support MediaSourceExtension.  

------------
**MIT License.**  
