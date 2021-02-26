<?php
/*Config*/
$output_dir = 'output';
$if_windows = true;
$clearLogOnStart = true;
$maxTSFileSize = 5242880; /*每个分割出来ts文件的最大大小（超过就会被压）In bytes*/
$mergeTSUpTo = 2097152; /*合并小TS到大TS的时候，大TS最高多大 In bytes（不要大于maxTSFileSize）*/
$disguisePic = __DIR__ . '/small.png'; /*伪装用的图片，png和jpg有严格的文件尾，建议使用*/
/*ConfigEnd*/
set_time_limit(0);
date_default_timezone_set("Asia/Shanghai");
$params_arr = getopt('v:');
$compressOVideo = false; /*是否压缩原视频*/
if (in_array('-recomp', $argv)) $compressOVideo = true; /*有-compress参数就压缩原视频*/
$video = @$params_arr['v'];
if (empty($video)) die('Please Input Video file');
/*Initialization*/
function is_cli() {
    return preg_match("/cli/i", php_sapi_name()) ? true : false;
}
function p($p) {
    global $if_windows;
    return __DIR__ . ($if_windows ? '\\' : '/') . $p;
}
function outp($path) {
    global $output_dir, $if_windows;
    return p($output_dir . ($if_windows ? '\\' : '/') . $path);
}
function execCommand($command, $output = false) {
    if (!$output) {
        $trace = shell_exec($command . ' 2>&1');
        /*https://stackoverflow.com/questions/1110655/capture-ffmpeg-output/1110765*/
        writeInLog($trace);
        return $trace;
    } else {
        shell_exec($command);
    }
}
function writeInLog($content) {
    file_put_contents(p('executeLog.log'), '[' . date('Y-m-d H:i:s', time()) . ']' . $content . PHP_EOL, FILE_APPEND);
}
function getSuffix($filename) {
    $exploded = explode('.', $filename);
    return end($exploded);
}
function tsSizeCheck() {
    global $output_dir, $maxTSFileSize;
    $scanOutput = scandir(p($output_dir));
    $bigfiles = [];
    foreach ($scanOutput as $v) {
        if (getSuffix($v) == 'ts') {
            $size = filesize(outp($v)); /*查询每个ts文件的大小*/
            if ($size >= $maxTSFileSize) $bigfiles[] = $v; /*记录超过设置大小的ts文件*/
        }
    }
    return $bigfiles;
}
function getAllTS() { /*返回ts文件和对应的大小*/
    global $output_dir;
    $scanOutput = scandir(p($output_dir));
    $tsfiles = [];
    natsort($scanOutput); /*这里用的自然排序法，计算机排序法里10<2，而自然排序法里10>2*/
    foreach ($scanOutput as $v) {
        if (getSuffix($v) == 'ts') {
            $size = filesize(outp($v)); /*查询每个ts文件的大小*/
            $tsfiles[] = array('file' => $v, 'size' => $size);
        }
    }
    return $tsfiles;
}
function compressTS($file) { /*利用ffmpeg压缩ts文件*/
    global $maxTSFileSize, $video;
    $singlevideo = str_ireplace('.ts', '.nosound.ts', $file); /*剥离出来的无声视频的文件名*/
    $singleaudio = str_ireplace('.ts', '.m4a', $file); /*剥离出来的无声音频的文件名*/
    $compvideo = str_ireplace('.ts', '.comp.nosound.ts', $file); /*无声视频压缩后的视频文件名*/
    $finalcomp = str_ireplace('.ts', '.comp.ts', $file); /*最终合并成的副本的文件名*/
    execCommand('ffmpeg -y -i ' . $file . ' -vcodec copy -an ' . $singlevideo); /*剥离视频*/
    execCommand('ffmpeg -y -i ' . $file . ' -acodec copy -vn ' . $singleaudio); /*剥离音频*/
    execCommand('ffmpeg -y -i ' . $file . ' -vcodec libx264 -preset fast -crf 18 -profile:v high ' . $compvideo); /*压缩无声视频*/
    execCommand('ffmpeg -y -i ' . $compvideo . ' -i ' . $singleaudio . ' -c:v copy -c:a aac -strict experimental ' . $finalcomp); /*合并压缩后的视频和音轨*/
    unlink($file); /*删掉原文件*/
    rename($finalcomp, $file); /*把合并后的视频副本重命名为原文件的名字*/
    unlink($compvideo);
    unlink($singleaudio);
    unlink($singlevideo); /*删掉多余的文件*/
    $newSize = filesize($file); /*查询压缩后的文件大小*/
    if ($newSize >= $maxTSFileSize) { /*压缩后还是很大*/
        echo 'TS File is big even after we compressed it.You can choose to reencode the original video.' . PHP_EOL;
        echo "use \e[38;5;1;1mphp pv.php -recomp -v " . $video . "\e[0m to reencode and continue", PHP_EOL;
        echo "\e[38;5;255;48;5;1;1;4;9;5mWARNING:\e[0m If you use the command above to reencode the original video,it will be covered , please make one backup" . PHP_EOL;
        exit();
    }
}
if ($clearLogOnStart) @unlink(p('executeLog.log'));
if (!is_cli()) die('Please run in CLI Mode');
/*Initialization end*/
/*mkdir for output*/
if (!is_dir(p($output_dir))) {
    mkdir(p($output_dir));
}
if (!file_exists(p($video))) die('Video not found:' . p($video));
/*get video details*/
echo 'Getting video details' . PHP_EOL;
$getVideoDetail = 'ffmpeg -i ' . p($video);
$ffmpegoutput = execCommand($getVideoDetail);
preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $ffmpegoutput, $match); /*正则匹配获取*/
preg_match("/Video: (.*?), (.*?), (.*?), (.*?), (.*?)[,\s]/", $ffmpegoutput, $match2);
$fps = intval($match2[5]);
$bitrate = intval($match[3]); /*获取视频比特率*/
if ($bitrate <= 0 || $fps <= 0) die('Video bitrate get failed.');
/*compress original video*/
if ($compressOVideo) {
    echo 'start compressing original video.' . PHP_EOL;
    $videosuffix = getSuffix($video);
    $tempvideo = str_ireplace('.' . $videosuffix, '.comp.' . $videosuffix, $video);
    execCommand('ffmpeg -i ' . p($video) . ' -vcodec libx264 -keyint_min 1 -x264-params keyint=' . ($fps * 5) . ':scenecut=0 -acodec copy ' . p($tempvideo), true); /*改变帧间距压制整个视频*/
    if (!file_exists(p($tempvideo))) die('Original video compression failed'); /*压制失败*/
    unlink(p($video)); /*删掉原视频*/
    rename(p($tempvideo), p($video)); /*重命名压制后的视频为原有视频名*/
    echo 'Original video compression completed.' . PHP_EOL;
}
/*execute ffmpeg*/
$tsGenerate = 'ffmpeg -y -i ' . p($video) . ' -c copy -vbsf h264_mp4toannexb -absf aac_adtstoasc ' . outp('video.ts'); /*生成视频ts文件*/
$tsSplit = 'ffmpeg -y -i ' . outp('video.ts') . ' -c copy -f segment -segment_list ' . outp('video.m3u8') . ' ' . outp('%d.ts'); /*切割ts文件并生成m3u8*/
echo 'Transforming video into TS' . PHP_EOL;
execCommand($tsGenerate);
if (!file_exists(outp('video.ts'))) die('Video coversion failed');
echo 'Spliting TS File' . PHP_EOL;
execCommand($tsSplit);
unlink(outp('video.ts')); /*delete original ts file*/
$scanOutput = scandir(p($output_dir));
if (!in_array('0.ts', $scanOutput)) die('TS File split failed');
/*process ts files*/
echo 'Checking TS Files' . PHP_EOL;
$bigfiles = tsSizeCheck(); /*检查ts大小并返回包含不合格大小ts的数组*/
foreach ($bigfiles as $val) {
    echo 'Compressing TS: ' . $val . PHP_EOL;
    compressTS(outp($val));
}
$bigfiles = tsSizeCheck(); /*二次检查*/
if (count($bigfiles) > 0) die('TS compression failed , too big.'); /*TS压了一遍还是大，只能放弃了*/
/*parse m3u8*/
echo 'Parsing m3u8 file' . PHP_EOL;
function m3u8parser($filepath) {
    $m3u8resource = file($filepath); /*将m3u8文件每行写入数组*/
    $readline = 0; /*从数组第一位开始读（从文件的第一行开始读）*/
    $endline = count($m3u8resource) - 1; /*最后一行对应的数组键值*/
    $parsedm3u8 = ['info' => [], 'meta' => []]; /*初始化m3u8解析数组*/
    while ($readline <= $endline) {
        $m3u8resource[$readline] = preg_replace("/\s/", "", $m3u8resource[$readline]); /*PHP file()读出来的带换行符，要处理*/
        if ($m3u8resource[$readline] == '#EXTM3U' || $m3u8resource[$readline] == '#EXT-X-ENDLIST') { /*如果读到m3u8文件头或者文件尾就跳出当前循环进入下个循环*/
            $readline+= 1; /*向下读一行*/
            continue;
        }
        if (stripos($m3u8resource[$readline], '#EXTINF:') === 0) { /*往下读到以#EXTINF开头的每行，这里有个PHP非常常见的坑，字串符用双等==与0作比较时会被转换成0来比较，导致'aa'==0判断为真的情况出现*/
            $rmhead = str_ireplace('#EXTINF:', '', $m3u8resource[$readline]); /*去掉这一行的#EXTINF:*/
            $duration = floatval(str_ireplace(',', '', $rmhead)); /*去掉这一行末尾的逗号，并转化为浮点数，获得这一段ts对应的时间长度duration*/
            $readline+= 1; /*向下读一行*/
            $tsfile = preg_replace("/\s/", "", $m3u8resource[$readline]); /*#EXTINF:开头一行的下一行一定是一个ts文件地址，注意有换行符!*/
            $parsedm3u8['info'][$tsfile] = ['duration' => $duration, 'file' => $tsfile];
        } else {
            $parsedm3u8['meta'][] = $m3u8resource[$readline]; /*其他行丢到meta元数据里去*/
        }
        $readline+= 1; /*向下读一行*/
    }
    return $parsedm3u8;
}
$parsedm3u8 = m3u8parser(outp('video.m3u8'));
//print_r($parsedm3u8);
/*parse m3u8 end*/
/*merge ts files*/
echo 'Merging TS Files' . PHP_EOL;
/*ts分片文件小而多，可以将一堆小的合成一个大点的ts文件，减少上传的量*/
$tsfiles = getAllTS(); /*获得所有的ts文件以及其大小*/
$category1Index = 0; /*分类第一步的索引*/
$category1 = array();
foreach ($tsfiles as $val) { /*Step1-找出所有大小超过$mergeTSUpTo的大文件*/
    $thefile = $val['file'];
    $thesize = $val['size'];
    $theduration = $parsedm3u8['info'][$thefile]['duration'];
    if ($thesize >= $mergeTSUpTo) {
        $category1Index+= 1;
        $category1[$category1Index] = [[$thefile, $thesize, $theduration]]; /*大文件单独占一个数组值*/
        $category1Index+= 1; /*下一个又是新的小文件数组*/
    } else {
        $category1[$category1Index][] = [$thefile, $thesize, $theduration]; /*小文件塞一个数组里*/
    }
}
$category2Index = 0; /*分类第二步的索引*/
$category2 = array();
foreach ($category1 as $tsval) { /*Step2-合并小的文件形成新的数组*/
    if (count($tsval) == 1) { /*这是一个大文件数组*/
        $thefile = $tsval[0][0];
        $thesize = $tsval[0][1];
        $theduration = $tsval[0][2];
        $category2[$category2Index] = [[outp($thefile), $theduration]];
        $category2Index+= 1;
    } else {
        $sizeToAdd = 0; /*用于统计小文件合并后的大小*/
        foreach ($tsval as $ts) { /*遍历小文件*/
            $thefile = $ts[0];
            $thesize = $ts[1];
            $theduration = $ts[2];
            $testSize = $sizeToAdd + $thesize; /*试试累加大小*/
            if ($testSize >= $mergeTSUpTo) { /*超过设定的大小了，合成一个文件*/
                $sizeToAdd = $thesize; /*记录落单的小文件大小*/
                $category2Index+= 1;
                $category2[$category2Index][] = [outp($thefile), $theduration]; /*记录落单的小文件*/
            } else {
                $sizeToAdd+= $thesize;
                $category2[$category2Index][] = [outp($thefile), $theduration]; /*可以合并的小文件加在一个数组里*/
            }
        }
        $category2Index+= 1; /*这里+1是为了防止进入下一个循环的时候如果是大文件就会覆盖掉上面循环最后两个小文件*/
    }
};
/*start merging progress*/
/*Step3-根据Category2来合并ts文件*/
/*注意，这里的文件tsarr全部outp()处理过了*/
foreach ($category2 as $fileindex => $tsarr) {
    $concatVal = '';
    $finalstream = '';
    foreach ($tsarr as $ts) {
        $finalstream.= file_get_contents($ts[0]);
    }
    file_put_contents(outp('temp.ts'), $finalstream);
    /*这里直接改成二进制拼接文件了，而且比ffmpeg concat协议要快特别多，也没有时间戳问题*/
    /*
    foreach($tsarr as $ts) $concatVal.=$ts[0].'|';
    $mergeCmd=count($tsarr)>1 ? ('ffmpeg -y -i concat:"'.$concatVal.'" -acodec copy -vcodec copy '.outp('temp.ts')) : ('ffmpeg -y -i '.$tsarr[0][0].'  -acodec copy -vcodec copy '.outp('temp.ts'));//如果只有一个文件，ffmpeg命令行语句不同*/
    //execCommand($mergeCmd);/*先创建副本*/
    foreach ($tsarr as $fileToDel) unlink($fileToDel[0]); /*删除原本的小ts文件*/
    rename(outp('temp.ts'), outp($fileindex . '.ts')); /*重命名temp.ts为新索引*/
}
/*Update M3U8 file*/
echo 'Rewriting m3u8 file' . PHP_EOL;
$m3u8contents = '#EXTM3U' . PHP_EOL; /*初始化m3u8文件头（别忘了还原换行符）*/
$m3u8list = ''; /*m3u8播放源*/
$maxDuration = 0; /*记录最大的duration以更改m3u8元数据里的#EXT-X-TARGETDURATION*/
foreach ($category2 as $fileindex => $tsarr) {
    $totalDuration = 0; /*每个合并后的大ts文件的duration*/
    foreach ($tsarr as $ts) $totalDuration+= $ts[1];
    if ($totalDuration > $maxDuration) $maxDuration = $totalDuration; /*通过不断比较得出最大的duration*/
    $m3u8list.= '#EXTINF:' . $totalDuration . ',' . PHP_EOL; /*写入大ts持续的duration*/
    $m3u8list.= $fileindex . '.ts' . PHP_EOL; /*写入大ts文件名*/
}
$maxDuration = ceil($maxDuration); /*向上取整*/
foreach ($parsedm3u8['meta'] as $metav) { /*先把meta写进m3u8*/
    if (stripos($metav, '#EXT-X-TARGETDURATION') !== false) { /*单独处理#EXT-X-TARGETDURATION*/
        $exploded = explode(':', $metav);
        $exploded[1] = $maxDuration;
        $metav = join(':', $exploded);
    }
    $m3u8contents.= $metav . PHP_EOL;
}
$m3u8contents.= $m3u8list; /*写入m3u8播放源*/
$m3u8contents.= '#EXT-X-ENDLIST' . PHP_EOL; /*写入m3u8文件尾*/
file_put_contents(outp('video.m3u8'), $m3u8contents);
/*upload files*/
echo 'Uploading files' . PHP_EOL;
$parsedm3u8Again = m3u8parser(outp('video.m3u8')); /*再次解析m3u8*/
$disguiseStream = file_get_contents($disguisePic);
$disguiseSuffix = getSuffix($disguisePic); /*伪装图片的后缀*/
require_once p('uploadAPI.php'); /*引入图片上传模块*/
foreach ($parsedm3u8Again['info'] as $key => $val) {
    $thefile = $val['file'];
    $tempStream = $disguiseStream . file_get_contents(outp($thefile)); /*临时组合图片和ts文件流*/
    $tempFilename = 'temppic' . rand(1, 10000) . '.' . $disguiseSuffix;
    file_put_contents(outp($tempFilename), $tempStream);
    $back = PVUpload(outp($tempFilename)); /*上传伪装的图片文件*/
    if (!$back[0]) die('Disguise Pic Upload failed:' . $back[1]);
    $picurl = str_ireplace('http://', 'https://', $back[0]); /*替换为https*/
    $parsedm3u8Again['info'][$key]['file'] = $picurl; /*更新m3u8文件内的资源为图片url*/
    unlink(outp($tempFilename)); /*删除临时文件*/
    echo 'Disguised ' . $thefile . ' uploaded' . PHP_EOL;
}
/*rewrite m3u8*/
echo 'Rewriting m3u8' . PHP_EOL;
$m3u8contents = '#EXTM3U' . PHP_EOL; /*初始化m3u8文件头（别忘了还原换行符）*/
foreach ($parsedm3u8Again['meta'] as $eachmeta) { /*写入m3u8元数据*/
    $m3u8contents.= $eachmeta . PHP_EOL;
}
foreach ($parsedm3u8Again['info'] as $fileinfo) { /*写入更新后的资源列表*/
    $m3u8contents.= '#EXTINF:' . $fileinfo['duration'] . ',' . PHP_EOL; /*写入ts持续的duration*/
    $m3u8contents.= $fileinfo['file'] . PHP_EOL; /*写入伪装的url*/
}
$m3u8contents.= '#EXT-X-ENDLIST' . PHP_EOL; /*写入m3u8文件尾*/
file_put_contents(outp('video.m3u8.' . $disguiseSuffix), $disguiseStream . $m3u8contents); /*写入m3u8图片伪装文件*/
unlink(outp('video.m3u8')); /*删掉原来的m3u8*/
echo 'Everything\'s fine now~The size of disguise pic is:' . PHP_EOL;
echo "\e[38;5;255;48;5;1;1;4;9;5m" . filesize($disguisePic) . " B\e[0m" . PHP_EOL;
echo 'Upload the disguised m3u8 file and enjoy!' . PHP_EOL;
?>