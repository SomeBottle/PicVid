<?php

/**Save to local */
$confirmed = false;
do {
	echo "\n\nExample: If you are to upload pic.png to https://example.com/abc/pic.png\n";
	echo "You should type in 'https://example.com/abc/'\n";
	$userInput = readline("Please input the parent URL of the pics to be uploaded: ");

	// 输出用户输入并等待确认
	echo "\nPreview: Pic test.png corresponds to " . $userInput . "test.png\n";
	$confirmation = readline("Type in 'yes' to confirm: ");

	// 检查用户确认
	if ($confirmation === 'yes') {
		echo "OK.\n";
		$confirmed = true;
		// 这里可以继续执行其他操作
	}
} while (!$confirmed);
function PVUpload($path)
{
	global $userInput;
	// Export to local
	$rawdata = "";
	$fileName = basename($path);
	$dirName = dirname($path);
	$picsDir = $dirName . '/pics';
	if (!is_dir($picsDir)) {
		mkdir($picsDir, 0755, true);
	}
	// destination
	$destFilePath = $picsDir . '/' . $fileName;
	if (copy($path, $destFilePath)) {
		echo '  Copied to local: ' . $destFilePath . PHP_EOL;
	}
	$imgurl = $userInput . $fileName;
	return [(empty($imgurl) ? false : $imgurl), $rawdata];
}
