<?php

/**
 * ip2region - 高性能IP地址定位库
 * 
 * 功能特性：
 * - 支持IPv4和IPv6地址查询
 * - 支持压缩数据库文件，优化大文件加载
 * - 多种缓存策略（文件缓存、向量索引、内容缓存）
 * - 高性能查询，毫秒级响应
 * - 支持多种数据库格式（xdb、dat）
 * - 自动数据库版本检测和加载
 * 
 * 使用场景：
 * - 网站访问统计和地域分析
 * - 网络安全和访问控制
 * - 广告投放和精准营销
 * - 用户行为分析
 * 
 * 性能特点：
 * - 内存占用低，支持大数据库文件
 * - 查询速度快，支持高并发
 * - 支持压缩数据库，减少内存使用
 * - 自动缓存机制，提升重复查询性能
 */

// Copyright 2022 The Ip2Region Authors. All rights reserved.
// Use of this source code is governed by a Apache2.0-style
// license that can be found in the LICENSE file.

if (!class_exists('ip2region\xdb\Searcher')) {
    require_once __DIR__ . '/XdbSearcher.php';
}

/**
 * ip2region 主类
 * 
 * 提供统一的IP地址查询接口，支持IPv4和IPv6
 * 自动处理数据库加载、缓存管理和查询优化
 */
class Ip2Region
{
    private $searcherV4 = null;
    private $searcherV6 = null;
    private $cachePolicy = 'file'; // file, vectorIndex, content
    
    // 自定义数据库路径配置
    private $dbPathV4 = null;
    private $dbPathV6 = null;

    // 静态缓存，避免重复生成临时文件
    private static $cachedV4File = null;
    private static $cachedV6File = null;
    
    // 缓存文件路径，用于持久化缓存
    private static $cacheDir = null;

    public function __construct($cachePolicy = 'file', $dbPathV4 = null, $dbPathV6 = null)
    {
        $this->cachePolicy = $cachePolicy;
        $this->dbPathV4 = $dbPathV4;
        $this->dbPathV6 = $dbPathV6;
        
        // 初始化缓存目录
        if (self::$cacheDir === null) {
            self::$cacheDir = sys_get_temp_dir() . '/ip2region_cache';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
        
    }

    public function __destruct()
    {
        if ($this->searcherV4 !== null) {
            $this->searcherV4->close();
        }
        if ($this->searcherV6 !== null) {
            $this->searcherV6->close();
        }
    }

    /**
     * 获取IP版本
     */
    private function getIpVersion($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'v4';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'v6';
        } else {
            throw new \Exception("无效的IP地址: {$ip}");
        }
    }

    /**
     * 懒加载获取对应的查询器
     */
    private function getSearcher($ip)
    {
        $version = $this->getIpVersion($ip);
        if ($version === 'v6') {
            // 懒加载 IPv6 查询器
            if ($this->searcherV6 === null) {
                $this->searcherV6 = $this->createSearcher('v6');
            }
            return $this->searcherV6;
        } else {
            // 懒加载 IPv4 查询器
            if ($this->searcherV4 === null) {
                $this->searcherV4 = $this->createSearcher('v4');
            }
            return $this->searcherV4;
        }
    }

    /**
     * 验证缓存文件是否有效
     * 
     * @param string $cacheFile 缓存文件路径
     * @param string $version 版本 (v4/v6)
     * @return bool 是否有效
     */
    private static function isValidCacheFile($cacheFile, $version)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $fileSize = filesize($cacheFile);
        
        // 基本大小检查（避免空文件或损坏文件）
        $minSize = $version === 'v4' ? 10 * 1024 * 1024 : 100 * 1024 * 1024; // 10MB for v4, 100MB for v6
        if ($fileSize < $minSize) {
            return false;
        }
        
        // 检查缓存文件是否比源压缩文件更新
        $compressedFile = dirname(__DIR__) . '/db/ip2region_' . $version . '.xdb.gz';
        if (file_exists($compressedFile)) {
            $cacheTime = filemtime($cacheFile);
            $compressedTime = filemtime($compressedFile);
            if ($cacheTime < $compressedTime) {
                return false;
            }
        }
        
        // 检查文件格式（简单验证）
        $sample = file_get_contents($cacheFile, false, null, 0, 1024);
        if ($sample === false) {
            return false;
        }
        
        // 检查是否包含地理位置相关的字符串
        $hasGeoData = strpos($sample, '中国') !== false || 
                      strpos($sample, '美国') !== false || 
                      strpos($sample, '|') !== false;
        
        return $hasGeoData;
    }

    /**
     * 获取或创建数据库文件
     * 优先级：自定义xdb > 下载的数据库文件 > IPv4压缩文件（IPv6不使用压缩）
     */
    private function getDbFile($version)
    {
        $staticVar = $version === 'v4' ? 'cachedV4File' : 'cachedV6File';
        $dbPath = $version === 'v4' ? $this->dbPathV4 : $this->dbPathV6;
        
        // 1. 如果使用自定义数据库，直接返回
        if ($dbPath !== null && file_exists($dbPath)) {
            return $dbPath;
        }
        
        // 2. 检查静态缓存
        if (self::$$staticVar !== null) {
            return self::$$staticVar;
        }
        
        // 3. 检查下载的数据库文件
        $projectRoot = dirname(__DIR__);
        
        // 自动检测存储位置
        if (strpos($projectRoot, '/vendor/') !== false) {
            // 通过 composer 安装，优先检查项目根目录的 vendor/bin/ip2data/ 目录
            // 需要向上 2 级：ip2region -> zoujingli -> vendor -> 项目根目录
            $realProjectRoot = dirname(dirname(dirname($projectRoot)));
            $downloadedFile = $realProjectRoot . '/vendor/bin/ip2data/ip2region_' . $version . '.xdb';
            if (file_exists($downloadedFile)) {
                self::$$staticVar = $downloadedFile;
                return $downloadedFile;
            }
        }
        
        // 回退到项目根目录的 vendor/bin/ip2data/ 目录
        $downloadedFile = $projectRoot . '/vendor/bin/ip2data/ip2region_' . $version . '.xdb';
        if (file_exists($downloadedFile)) {
            self::$$staticVar = $downloadedFile;
            return $downloadedFile;
        }
        
        // 4. 检查持久化缓存
        $cacheFile = self::$cacheDir . '/ip2region_' . $version . '.xdb';
        if (file_exists($cacheFile)) {
            // 检查缓存文件是否有效
            if (self::isValidCacheFile($cacheFile, $version)) {
                self::$$staticVar = $cacheFile;
                return $cacheFile;
            }
        }
        
        // 5. 对于 IPv4，尝试使用内置压缩数据库
        if ($version === 'v4') {
            $compressedFile = dirname(__DIR__) . '/db/ip2region_v4.xdb.gz';
            if (file_exists($compressedFile)) {
                // 解压到缓存目录
                $decompressedFile = $cacheFile;
                if (!file_exists($decompressedFile) || filemtime($compressedFile) > filemtime($decompressedFile)) {
                    $fp = gzopen($compressedFile, 'rb');
                    $out = fopen($decompressedFile, 'wb');
                    if ($fp && $out) {
                        while (!gzeof($fp)) {
                            fwrite($out, gzread($fp, 8192));
                        }
                        fclose($fp);
                        fclose($out);
                    }
                }
                if (file_exists($decompressedFile)) {
                    self::$$staticVar = $decompressedFile;
                    return $decompressedFile;
                }
            }
        }
        
        // 6. 抛出异常，提示用户下载数据库
        if ($version === 'v6') {
            throw new \Exception("IPv6 查询需要下载完整数据库文件。\n\n下载方式：\n1. 使用 Composer 命令：composer download-db:v6\n2. 使用下载工具：./vendor/bin/ip2down download v6\n3. 手动下载：https://github.com/lionsoul2014/ip2region/raw/refs/heads/master/data/ip2region_v6.xdb\n\n注意：IPv6 不支持压缩文件，必须使用完整数据库。");
        } else {
            throw new \Exception("未找到 IPv4 数据库文件。\n\n解决方案：\n1. 使用 Composer 命令：composer download-db:v4\n2. 使用下载工具：./vendor/bin/ip2down download v4\n3. 确保压缩数据库文件存在于 db/ 目录中");
        }
    }

    /**
     * 创建查询器
     */
    private function createSearcher($version)
    {
        try {
            // 使用自动缓存机制获取数据库文件
            $file = $this->getDbFile($version);
            
            if ($version === 'v4') {
                $ipVersion = \ip2region\xdb\IPv4::default();
            } else {
                $ipVersion = \ip2region\xdb\IPv6::default();
            }

            if (!file_exists($file)) {
                throw new \Exception("数据库文件不存在: {$file}");
            }

            return \ip2region\xdb\Searcher::newWithFileOnly($ipVersion, $file);
        } catch (\Exception $e) {
            // 如果是数据库文件相关的错误，直接传递原始错误信息
            if (strpos($e->getMessage(), 'IPv6 查询需要下载') !== false || 
                strpos($e->getMessage(), '未找到 IPv4 数据库文件') !== false) {
                throw $e;
            }
            throw new \Exception("创建 {$version} 查询器失败: " . $e->getMessage());
        }
    }

    /**
     * 内存查询
     */
    public function memorySearch($ip)
    {
        $searcher = $this->getSearcher($ip);
        $region = $searcher->search($ip);
        return array('city_id' => 0, 'region' => $region === null ? '' : $region);
    }

    /**
     * 批量查询
     */
    public function batchSearch($ips)
    {
        $results = array();
        foreach ($ips as $ip) {
            try {
                $result = $this->memorySearch($ip);
                $results[$ip] = isset($result['region']) ? $result['region'] : null;
            } catch (Exception $e) {
                $results[$ip] = null;
            }
        }
        return $results;
    }

    /**
     * IPv6 专用查询
     */
    public function searchIPv6($ip)
    {
        if (!$this->isIPv6($ip)) {
            throw new \Exception("不是有效的IPv6地址: {$ip}");
        }
        $result = $this->memorySearch($ip);
        return isset($result['region']) ? $result['region'] : null;
    }

    /**
     * 获取IP信息
     */
    public function getIpInfo($ip)
    {
        $result = $this->memorySearch($ip);
        if ($result === null || !isset($result['region'])) {
            return null;
        }

        $parts = explode('|', $result['region']);
        return array(
            'country'  => isset($parts[0]) ? $parts[0] : '',
            'region'   => isset($parts[1]) ? $parts[1] : '',
            'province' => isset($parts[2]) ? $parts[2] : '',
            'city'     => isset($parts[3]) ? $parts[3] : '',
            'isp'      => isset($parts[4]) ? $parts[4] : '',
            'ip'       => $ip,
            'version'  => $this->getIpVersion($ip)
        );
    }

    /**
     * 检查是否为IPv6
     */
    private function isIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * 获取统计信息
     */
    public function getStats()
    {
        $stats = array(
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'v4_io_count' => 0,
            'v6_io_count' => 0,
            'v4_loaded' => $this->searcherV4 !== null,
            'v6_loaded' => $this->searcherV6 !== null,
            'cache_policy' => $this->cachePolicy
        );

        if ($this->searcherV4 !== null) {
            $stats['v4_io_count'] = $this->searcherV4->getIOCount() === null ? 0 : $this->searcherV4->getIOCount();
        }
        if ($this->searcherV6 !== null) {
            $stats['v6_io_count'] = $this->searcherV6->getIOCount() === null ? 0 : $this->searcherV6->getIOCount();
        }

        return $stats;
    }

    /**
     * 获取内存使用情况
     */
    public function getMemoryUsage()
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return array(
            'current'   => $this->formatBytes($memory),
            'peak'      => $this->formatBytes($peak),
            'v4_loaded' => $this->searcherV4 !== null,
            'v6_loaded' => $this->searcherV6 !== null
        );
    }

    /**
     * 格式化字节数
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 清理缓存
     */
    public static function clearCache()
    {
        // 清理缓存文件
        $instance = new self();
        $instance->clearCacheFiles();
        self::$cachedV4File = null;
        self::$cachedV6File = null;
    }

    /**
     * 清理过期缓存
     */
    public static function clearExpiredCache($days = 7)
    {
        // 清理过期缓存
        $cacheDir = sys_get_temp_dir() . '/ip2region_cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            $expireTime = time() - ($days * 24 * 3600);
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $expireTime) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * 清理持久化缓存
     * 
     * 清理系统临时目录中的持久化缓存文件
     * 在 FPM 环境下，这可以避免重复生成缓存文件
     */
    public static function clearPersistentCache()
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = sys_get_temp_dir() . '/ip2region_cache';
        }
        
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '/ip2region_*.xdb');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            return count($files);
        }
        return 0;
    }

    /**
     * 获取缓存统计信息
     */
    public static function getCacheStats()
    {
        $cacheDir = sys_get_temp_dir() . '/ip2region_cache';
        $stats = array(
            'cache_dir' => $cacheDir,
            'cache_files' => 0,
            'total_size' => 0,
            'v4_cached' => false,
            'v6_cached' => false
        );
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            $stats['cache_files'] = count($files);
            foreach ($files as $file) {
                if (is_file($file)) {
                    $stats['total_size'] += filesize($file);
                    if (strpos($file, 'v4') !== false) {
                        $stats['v4_cached'] = true;
                    }
                    if (strpos($file, 'v6') !== false) {
                        $stats['v6_cached'] = true;
                    }
                }
            }
        }
        
        return $stats;
    }

    /**
     * 简单查询方法（兼容旧版本）
     * @param string $ip IP地址
     * @return string|null 查询结果
     */
    public function simple($ip)
    {
        $geo = $this->memorySearch($ip);
        $arr = explode('|', str_replace(array('0|'), '|', isset($geo['region']) ? $geo['region'] : ''));
        if (($last = array_pop($arr)) === '内网IP') $last = '';
        return join('', $arr) . (empty($last) ? '' : "【{$last}】");
    }

    /**
     * 搜索方法（兼容旧版本）
     * @param string $ip IP地址
     * @return string|null 查询结果
     */
    public function search($ip)
    {
        $result = $this->memorySearch($ip);
        return isset($result['region']) ? $result['region'] : null;
    }

    /**
     * 二进制搜索方法（兼容旧版本）
     * @param string $ip IP地址
     * @return array 查询结果
     */
    public function binarySearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 二进制字节搜索方法
     * @param string $ipBytes 二进制IP地址
     * @return string|null 查询结果
     */
    public function searchByBytes($ipBytes)
    {
        // 确定IP版本
        $version = strlen($ipBytes) == 4 ? 'v4' : 'v6';

        if ($version === 'v4') {
            if ($this->searcherV4 === null) {
                $this->searcherV4 = $this->createSearcher('v4');
            }
            return $this->searcherV4->searchByBytes($ipBytes);
        } else {
            if ($this->searcherV6 === null) {
                $this->searcherV6 = $this->createSearcher('v6');
            }
            return $this->searcherV6->searchByBytes($ipBytes);
        }
    }

    /**
     * B树搜索方法（兼容旧版本）
     * @param string $ip IP地址
     * @return array 查询结果
     */
    public function btreeSearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 获取IP协议版本（公共方法）
     * @param string $ip IP地址
     * @return string IP版本
     */
    public function getProtocolVersion($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return 'v4';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return 'v6';
        } else {
            return 'unknown';
        }
    }

    /**
     * 获取IO计数
     * @return array IO计数信息
     */
    public function getIOCount()
    {
        $stats = $this->getStats();
        return array(
            'v4_io_count' => $stats['v4_io_count'],
            'v6_io_count' => $stats['v6_io_count'],
            'total_io_count' => $stats['v4_io_count'] + $stats['v6_io_count']
        );
    }

    /**
     * 检查是否支持IPv6
     * @return bool 是否支持IPv6
     */
    public function isIPv6Supported()
    {
        return true;
    }

    /**
     * 检查是否支持IPv4
     * @return bool 是否支持IPv4
     */
    public function isIPv4Supported()
    {
        return true;
    }

    /**
     * 获取数据库信息
     * @return array 数据库信息
     */
    public function getDatabaseInfo()
    {
        $info = array(
            'v4_loaded' => $this->searcherV4 !== null,
            'v6_loaded' => $this->searcherV6 !== null,
            'cache_policy' => $this->cachePolicy,
            'custom_v4_path' => $this->dbPathV4,
            'custom_v6_path' => $this->dbPathV6
        );

        if ($this->searcherV4 !== null) {
            $info['v4_version'] = $this->searcherV4->getIPVersion();
        }
        if ($this->searcherV6 !== null) {
            $info['v6_version'] = $this->searcherV6->getIPVersion();
        }

        return $info;
    }

    /**
     * 获取自定义数据库文件信息
     * @return array 自定义数据库文件信息
     */
    public function getCustomDbInfo()
    {
        $info = array(
            'v4' => $this->getDbFileInfo($this->dbPathV4),
            'v6' => $this->getDbFileInfo($this->dbPathV6)
        );

        return $info;
    }

    /**
     * 设置自定义数据库路径
     * @param string $v4Path IPv4数据库路径
     * @param string $v6Path IPv6数据库路径
     */
    public function setCustomDbPaths($v4Path = null, $v6Path = null)
    {
        $this->dbPathV4 = $v4Path;
        $this->dbPathV6 = $v6Path;
        
        // 重置查询器，强制重新加载
        if ($this->searcherV4 !== null) {
            $this->searcherV4->close();
            $this->searcherV4 = null;
        }
        if ($this->searcherV6 !== null) {
            $this->searcherV6->close();
            $this->searcherV6 = null;
        }
    }

    /**
     * 检查是否使用自定义数据库
     * @return array 使用状态
     */
    public function isUsingCustomDb()
    {
        return array(
            'v4' => $this->dbPathV4 !== null && $this->isCustomDbExists($this->dbPathV4),
            'v6' => $this->dbPathV6 !== null && $this->isCustomDbExists($this->dbPathV6)
        );
    }
    
    /**
     * 清理缓存文件
     */
    private function clearCacheFiles()
    {
        $cacheDir = sys_get_temp_dir() . '/ip2region_cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    
    
    /**
     * 获取数据库文件信息
     */
    private function getDbFileInfo($filePath)
    {
        if ($filePath === null || !file_exists($filePath)) {
            return null;
        }
        
        return array(
            'path' => $filePath,
            'size' => filesize($filePath),
            'modified' => filemtime($filePath),
            'exists' => true
        );
    }
    
    /**
     * 检查自定义数据库是否存在
     */
    private function isCustomDbExists($filePath)
    {
        return $filePath !== null && file_exists($filePath);
    }
}
