<?php

/**
 * ip2region 数据库压缩工具
 * 
 * 用法: php tools/compress_db.php [source] [compress]
 * 参数: source=源文件路径, compress=压缩方式(gzip/zip/zstd)
 * 示例: php tools/compress_db.php
 */

$sourceFile = isset($argv[1]) ? $argv[1] : 'ip2region_v4.xdb';
$compress = isset($argv[2]) ? $argv[2] : 'gzip';

// 验证压缩方式
$validCompress = array('gzip', 'zip', 'zstd');
if (!in_array($compress, $validCompress)) {
    fwrite(STDERR, "无效的压缩方式: $compress，支持的方式: " . implode(', ', $validCompress) . "\n");
    exit(1);
}

// 检查源文件
if (!file_exists($sourceFile)) {
    fwrite(STDERR, "源文件不存在: $sourceFile\n");
    exit(1);
}

// 检查压缩扩展
if ($compress === 'gzip' && !extension_loaded('zlib')) {
    fwrite(STDERR, "错误: zlib 扩展未加载，无法使用 gzip 压缩\n");
    exit(1);
}
if ($compress === 'zip' && !extension_loaded('zip')) {
    fwrite(STDERR, "错误: zip 扩展未加载，无法使用 zip 压缩\n");
    exit(1);
}
if ($compress === 'zstd' && !extension_loaded('zstd')) {
    fwrite(STDERR, "错误: zstd 扩展未加载，无法使用 zstd 压缩\n");
    exit(1);
}

// 生成输出文件路径 - 输出到 db/ 目录
$outputDir = dirname(__DIR__) . '/db';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$baseName = basename($sourceFile);
$outputFile = $outputDir . '/' . $baseName . '.gz';
if ($compress === 'zip') {
    $outputFile = $outputDir . '/' . $baseName . '.zip';
} elseif ($compress === 'zstd') {
    $outputFile = $outputDir . '/' . $baseName . '.zst';
}

$originalSize = filesize($sourceFile);

echo "开始压缩数据库文件...\n";
echo "源文件: $sourceFile\n";
echo "输出文件: $outputFile\n";
echo "原始大小: " . round($originalSize / 1024 / 1024, 2) . "MB\n";
echo "压缩方式: $compress\n\n";

/**
 * 压缩文件函数
 * 
 * 使用流式处理避免内存问题，支持多种压缩算法
 * 
 * @param string $inputFile  输入文件路径
 * @param string $outputFile 输出文件路径
 * @param string $method     压缩方法 (gzip, zip, zstd)
 * @return bool 压缩是否成功
 */
function compressFile($inputFile, $outputFile, $method)
{
    switch ($method) {
        case 'gzip':
            // 使用流式 gzip 压缩
            $in = fopen($inputFile, 'rb');
            $gz = gzopen($outputFile, 'wb9'); // 最高压缩级别
            
            if (!$in || !$gz) {
                if ($in) fclose($in);
                if ($gz) gzclose($gz);
                return false;
            }

            // 流式复制和压缩
            while (!feof($in)) {
                $data = fread($in, 64 * 1024); // 64KB 块
                if ($data !== false) {
                    gzwrite($gz, $data);
                }
            }

            fclose($in);
            gzclose($gz);
            return true;

        case 'zip':
            // 使用 zip 压缩
            $zip = new ZipArchive();
            if ($zip->open($outputFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($inputFile, basename($inputFile));
                $result = $zip->close();
                return $result;
            }
            return false;

        case 'zstd':
            // 使用 zstd 压缩
            $in = fopen($inputFile, 'rb');
            $out = fopen($outputFile, 'wb');
            
            if (!$in || !$out) {
                if ($in) fclose($in);
                if ($out) fclose($out);
                return false;
            }

            // 读取整个文件进行 zstd 压缩
            $data = file_get_contents($inputFile);
            if ($data === false) {
                fclose($in);
                fclose($out);
                return false;
            }

            $compressed = zstd_compress($data, 22); // 最高压缩级别
            if ($compressed === false) {
                fclose($in);
                fclose($out);
                return false;
            }

            fwrite($out, $compressed);
            fclose($in);
            fclose($out);
            return true;

        default:
            return false;
    }
}

// 执行压缩
$startTime = microtime(true);
$success = compressFile($sourceFile, $outputFile, $compress);
$endTime = microtime(true);

if (!$success) {
    fwrite(STDERR, "压缩失败！\n");
    exit(1);
}

$compressedSize = filesize($outputFile);
$compressionRatio = (1 - $compressedSize / $originalSize) * 100;
$timeTaken = round($endTime - $startTime, 2);

echo "\n压缩完成！\n";
echo "原始大小: " . round($originalSize / 1024 / 1024, 2) . "MB\n";
echo "压缩后大小: " . round($compressedSize / 1024 / 1024, 2) . "MB\n";
echo "压缩率: " . round($compressionRatio, 1) . "%\n";
echo "压缩时间: {$timeTaken}秒\n";
echo "输出文件: $outputFile\n";

// 询问是否替换原文件
echo "\n是否替换原文件？(y/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) === 'y' || trim($line) === 'Y') {
    if (unlink($sourceFile)) {
        echo "原文件已删除: $sourceFile\n";
    } else {
        echo "警告: 无法删除原文件: $sourceFile\n";
    }
} else {
    echo "保留原文件: $sourceFile\n";
}

echo "\n压缩工具执行完成！\n";
