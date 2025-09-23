<?php

/**
 * 数据库管理器
 * 
 * @author Anyon <zoujingli@qq.com>
 * @version 3.0.3
 */
class DatabaseManager
{
    private $dbDir;
    private $downloadUrls = [
        'v4' => [
            'url' => 'https://cdn.jsdelivr.net/gh/lionsoul2014/ip2region@master/data/ip2region_v4.xdb',
            'filename' => 'ip2region_v4.xdb',
            'description' => 'IPv4 数据库',
            'minSize' => 10 * 1024 * 1024 // 10MB
        ],
        'v6' => [
            'url' => 'https://gitee.com/lionsoul/ip2region/raw/master/data/ip2region_v6.xdb?lfs=1',
            'filename' => 'ip2region_v6.xdb',
            'description' => 'IPv6 数据库',
            'minSize' => 100 * 1024 * 1024 // 100MB
        ]
    ];

    public function __construct($dbDir = null)
    {
        if ($dbDir !== null) {
            $this->dbDir = $dbDir;
        } else {
            // 自动检测存储位置
            // 从 src/ 目录向上一级到项目根目录
            $projectRoot = dirname(__DIR__);
            
            // 检查是否在 vendor 目录下（通过 composer 安装）
            if (strpos($projectRoot, '/vendor/') !== false) {
                // 通过 composer 安装，存储在项目根目录的 vendor/bin/ip2data/ 目录
                // 从 vendor/zoujingli/ip2region 向上到项目根目录
                // 需要向上 2 级：ip2region -> zoujingli -> vendor -> 项目根目录
                $realProjectRoot = dirname(dirname(dirname($projectRoot)));
                $this->dbDir = $realProjectRoot . '/vendor/bin/ip2data';
            } else {
                // 开发环境，也存储在 vendor/bin/ip2data/ 目录
                $this->dbDir = $projectRoot . '/vendor/bin/ip2data';
            }
        }
        
        if (!is_dir($this->dbDir)) {
            mkdir($this->dbDir, 0755, true);
        }
    }

    /**
     * 下载数据库文件
     */
    public function download($version = 'all', $progressCallback = null)
    {
        $results = [];
        
        if ($version === 'all') {
            $results['v4'] = $this->downloadVersion('v4', $progressCallback);
            $results['v6'] = $this->downloadVersion('v6', $progressCallback);
        } elseif (isset($this->downloadUrls[$version])) {
            $results[$version] = $this->downloadVersion($version, $progressCallback);
        } else {
            throw new \InvalidArgumentException("不支持的版本: $version");
        }
        
        return $results;
    }

    /**
     * 下载指定版本的数据库
     */
    private function downloadVersion($version, $progressCallback = null)
    {
        $config = $this->downloadUrls[$version];
        $filepath = $this->dbDir . '/' . $config['filename'];
        
        if ($progressCallback) {
            $progressCallback("正在下载 {$config['description']}...", $version);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 300,
                'user_agent' => 'ip2region-php/3.0.2'
            ]
        ]);

        // 使用流式下载避免内存问题
        $handle = @fopen($config['url'], 'rb', false, $context);
        if ($handle === false) {
            throw new \RuntimeException("无法从 {$config['url']} 下载文件");
        }

        $output = @fopen($filepath, 'wb');
        if ($output === false) {
            fclose($handle);
            throw new \RuntimeException("无法写入文件: $filepath");
        }

        // 获取文件总大小（如果支持）
        $meta = stream_get_meta_data($handle);
        $totalSize = 0;
        if (isset($meta['wrapper_data'])) {
            foreach ($meta['wrapper_data'] as $header) {
                if (preg_match('/Content-Length:\s*(\d+)/i', $header, $matches)) {
                    $totalSize = (int)$matches[1];
                    break;
                }
            }
        }

        // 流式复制，显示进度
        $bytesCopied = 0;
        $chunkSize = 8192; // 8KB 块
        $lastProgress = 0;
        $startTime = time();
        $lastTime = $startTime;
        $lastBytes = 0;
        
        while (!feof($handle)) {
            $data = fread($handle, $chunkSize);
            if ($data === false) {
                break;
            }
            
            $written = fwrite($output, $data);
            if ($written === false) {
                break;
            }
            
            $bytesCopied += $written;
            $currentTime = time();
            
            // 显示进度（每 1MB 或 5% 更新一次）
            if ($totalSize > 0) {
                $progress = intval(($bytesCopied / $totalSize) * 100);
                if ($progress >= $lastProgress + 5 || $bytesCopied % (1024 * 1024) == 0) {
                    if ($progressCallback) {
                        $elapsed = $currentTime - $startTime;
                        $speed = $elapsed > 0 ? $bytesCopied / $elapsed : 0;
                        $eta = $speed > 0 ? ($totalSize - $bytesCopied) / $speed : 0;
                        
                        $message = "下载进度: {$progress}% (" . $this->formatBytes($bytesCopied) . " / " . $this->formatBytes($totalSize) . ")";
                        if ($speed > 0) {
                            $message .= " - " . $this->formatBytes($speed) . "/s";
                            if ($eta > 0 && $eta < 3600) {
                                $message .= " - 剩余 " . intval($eta) . "s";
                            }
                        }
                        $progressCallback($message, $version);
                    }
                    $lastProgress = $progress;
                }
            } else {
                // 没有总大小信息，按字节数和速度显示
                if ($bytesCopied % (1024 * 1024) == 0 || ($currentTime - $lastTime) >= 2) {
                    if ($progressCallback) {
                        $elapsed = $currentTime - $startTime;
                        $speed = $elapsed > 0 ? $bytesCopied / $elapsed : 0;
                        
                        $message = "已下载: " . $this->formatBytes($bytesCopied);
                        if ($speed > 0) {
                            $message .= " - " . $this->formatBytes($speed) . "/s";
                            
                            // 根据版本估算剩余时间
                            $estimatedSize = $version === 'v4' ? 11 * 1024 * 1024 : 600 * 1024 * 1024; // 估算大小
                            if ($bytesCopied < $estimatedSize) {
                                $eta = ($estimatedSize - $bytesCopied) / $speed;
                                if ($eta > 0 && $eta < 3600) {
                                    $message .= " - 预计剩余 " . intval($eta) . "s";
                                }
                            }
                        }
                        $progressCallback($message, $version);
                    }
                    $lastTime = $currentTime;
                }
            }
        }
        
        fclose($handle);
        fclose($output);

        if ($bytesCopied === 0) {
            unlink($filepath);
            throw new \RuntimeException("下载过程中发生错误");
        }

        $size = filesize($filepath);
        if ($size < $config['minSize']) {
            unlink($filepath);
            throw new \RuntimeException("下载的文件大小异常: " . $this->formatBytes($size) . " (期望: " . $this->formatBytes($config['minSize']) . "+)");
        }

        if ($progressCallback) {
            $progressCallback("✅ 下载完成: {$config['description']} (" . $this->formatBytes($size) . ")", $version);
        }

        return [
            'success' => true,
            'filepath' => $filepath,
            'size' => $size,
            'version' => $version
        ];
    }

    /**
     * 列出已下载的数据库文件
     */
    public function listFiles()
    {
        $files = [];
        $patterns = ['*.xdb', '*.xdb.part*.gz', '*.xdb.part*.zip'];
        
        foreach ($patterns as $pattern) {
            $matches = glob($this->dbDir . '/' . $pattern);
            foreach ($matches as $file) {
                $filename = basename($file);
                $files[] = [
                    'filename' => $filename,
                    'filepath' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'type' => $this->getFileType($filename)
                ];
            }
        }
        
        return $files;
    }

    /**
     * 检查数据库文件是否存在
     */
    public function hasDatabase($version)
    {
        if (!isset($this->downloadUrls[$version])) {
            return false;
        }
        
        $filepath = $this->dbDir . '/' . $this->downloadUrls[$version]['filename'];
        return file_exists($filepath) && filesize($filepath) >= $this->downloadUrls[$version]['minSize'];
    }

    /**
     * 获取数据库文件路径
     * 
     * @param string $version 版本
     * @return string|null 文件路径
     */
    public function getDatabasePath($version)
    {
        if (!$this->hasDatabase($version)) {
            return null;
        }
        
        return $this->dbDir . '/' . $this->downloadUrls[$version]['filename'];
    }

    /**
     * 获取存储目录
     */
    public function getStorageDir()
    {
        return $this->dbDir;
    }

    /**
     * 验证数据库文件
     */
    public function validateDatabase($filepath)
    {
        if (!file_exists($filepath)) {
            return false;
        }
        
        $size = filesize($filepath);
        if ($size < 1024 * 1024) { // 至少 1MB
            return false;
        }
        
        // 检查文件头是否包含 xdb 标识
        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 16);
        fclose($handle);
        
        // 简单的格式检查
        return strlen($header) >= 16;
    }

    /**
     * 清除缓存文件
     */
    public function clearCache()
    {
        $count = 0;
        
        // 清除临时文件
        $tempFiles = glob($this->dbDir . '/*.tmp');
        foreach ($tempFiles as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * 获取文件类型
     */
    private function getFileType($filename)
    {
        if (strpos($filename, 'v6') !== false) {
            return 'IPv6';
        } elseif (strpos($filename, 'v4') !== false) {
            return 'IPv4';
        } elseif (strpos($filename, '.part') !== false) {
            return '压缩文件';
        } else {
            return '未知';
        }
    }

    /**
     * 格式化字节大小
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
