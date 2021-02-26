# PicVid
A simple PHP Script to transform Video into pics that can be uploaded to picbeds.

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
