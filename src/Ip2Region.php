<?php

/**
 * ip2region - 高性能IP地址定位库
 *
 * 核心功能：
 * - 支持 IPv4 和 IPv6 地址查询
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
 *
 * 数据库优先级：
 * 1. 自定义数据库路径（通过构造函数指定）
 * 2. 下载的数据库文件（vendor/bin/ip2data/ 目录）
 * 3. 内置压缩数据库（db/ 目录）
 *
 * @author Anyon <zoujingli@qq.com>
 * @version 3.0
 * @since 2022-01-01
 */

// Copyright 2022 The Ip2Region Authors. All rights reserved.
// Use of this source code is governed by a Apache2.0-style
// license that can be found in the LICENSE file.

// 通过 Composer autoload 自动加载

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

    /**
     * 构造函数
     *
     * 初始化 IP2Region 实例，配置缓存策略和自定义数据库路径
     *
     * @param string $cachePolicy 缓存策略，可选值：
     * - 'file': 文件缓存（默认）
     * - 'vectorIndex': 向量索引缓存
     * - 'content': 内容缓存
     * @param string|null $dbPathV4 IPv4 数据库自定义路径，为 null 时使用默认路径
     * @param string|null $dbPathV6 IPv6 数据库自定义路径，为 null 时使用默认路径
     *
     * @throws \Exception 当缓存目录创建失败时抛出异常
     *
     * @example
     * ```php
     * // 使用默认配置
     * $searcher = new Ip2Region();
     *
     * // 使用自定义数据库路径
     * $searcher = new Ip2Region('file', '/path/to/ipv4.xdb', '/path/to/ipv6.xdb');
     *
     * // 使用向量索引缓存策略
     * $searcher = new Ip2Region('vectorIndex');
     * ```
     */
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

    /**
     * 析构函数
     *
     * 自动清理资源，关闭已打开的搜索引擎连接
     * 确保在对象销毁时释放文件句柄和内存资源
     *
     * @return void
     */
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
     * 获取IP地址版本
     *
     * 检测给定的IP地址是IPv4还是IPv6格式
     *
     * @param string $ip 要检测的IP地址
     * @return string 返回 'v4' 表示IPv4，'v6' 表示IPv6
     * @throws \Exception 当IP地址格式无效时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * echo $searcher->getIpVersion('8.8.8.8'); // 输出: v4
     * echo $searcher->getIpVersion('2001:4860:4860::8888'); // 输出: v6
     * ```
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
     * 懒加载获取对应的搜索引擎
     *
     * 根据IP版本自动创建或返回对应的搜索引擎实例
     * 采用懒加载模式，只有在实际查询时才创建搜索引擎
     *
     * @param string $ip 要查询的IP地址
     * @return \ip2region\xdb\Searcher 返回对应版本的搜索引擎实例
     * @throws \Exception 当IP地址无效或搜索引擎创建失败时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $v4Searcher = $searcher->getSearcher('8.8.8.8'); // 返回IPv4搜索引擎
     * $v6Searcher = $searcher->getSearcher('2001:4860:4860::8888'); // 返回IPv6搜索引擎
     * ```
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
     * 检查缓存文件是否存在、大小是否合理、是否比源文件更新，
     * 以及是否包含有效的地理位置数据
     *
     * @param string $cacheFile 缓存文件路径
     * @param string $version 版本标识，'v4' 表示IPv4，'v6' 表示IPv6
     * @return bool 返回 true 表示缓存文件有效，false 表示无效需要重新生成
     *
     * @example
     * ```php
     * $isValid = Ip2Region::isValidCacheFile('/tmp/ip2region_cache/ip2region_v4.xdb', 'v4');
     * if ($isValid) {
     *     echo "缓存文件有效，可以直接使用";
     * } else {
     *     echo "缓存文件无效，需要重新生成";
     * }
     * ```
     */
    private static function isValidCacheFile($cacheFile, $version)
    {
        if (!file_exists($cacheFile)) {
            return false;
        }

        $fileSize = filesize($cacheFile);
        $minSize = $version === 'v4' ? 10 * 1024 * 1024 : 100 * 1024 * 1024;
        
        // 快速大小检查
        if ($fileSize < $minSize) {
            return false;
        }

        // 检查缓存文件是否比源压缩文件更新
        $compressedFile = dirname(__DIR__) . '/db/ip2region_' . $version . '.xdb.gz';
        if (file_exists($compressedFile) && filemtime($cacheFile) < filemtime($compressedFile)) {
            return false;
        }

        // 快速格式验证（只读取前512字节）
        $sample = file_get_contents($cacheFile, false, null, 0, 512);
        return $sample !== false && (strpos($sample, '中国') !== false || strpos($sample, '美国') !== false || strpos($sample, '|') !== false);
    }

    /**
     * 获取或创建数据库文件
     *
     * 按照优先级顺序查找和创建数据库文件：
     * 1. 自定义数据库路径（通过构造函数指定）
     * 2. 下载的数据库文件（vendor/bin/ip2data/ 目录）
     * 3. 内置压缩数据库（db/ 目录，仅IPv4支持）
     *
     * @param string $version 版本标识，'v4' 表示IPv4，'v6' 表示IPv6
     * @return string 返回可用的数据库文件路径
     * @throws \Exception 当找不到可用的数据库文件时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $v4DbPath = $searcher->getDbFile('v4'); // 获取IPv4数据库路径
     * $v6DbPath = $searcher->getDbFile('v6'); // 获取IPv6数据库路径
     * ```
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

        // 回退到当前项目目录的 vendor/bin/ip2data/ 目录
        $downloadedFile = $projectRoot . '/vendor/bin/ip2data/ip2region_' . $version . '.xdb';
        if (file_exists($downloadedFile)) {
            self::$$staticVar = $downloadedFile;
            return $downloadedFile;
        }

        // 开发环境：检查当前目录的 vendor/bin/ip2data/ 目录
        $currentDir = __DIR__;
        $downloadedFile = $currentDir . '/vendor/bin/ip2data/ip2region_' . $version . '.xdb';
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
     * 创建搜索引擎实例
     *
     * 根据指定版本创建对应的搜索引擎实例，支持IPv4和IPv6
     *
     * @param string $version 版本标识，'v4' 表示IPv4，'v6' 表示IPv6
     * @return \ip2region\xdb\Searcher 返回搜索引擎实例
     * @throws \Exception 当数据库文件不存在或搜索引擎创建失败时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $v4Searcher = $searcher->createSearcher('v4'); // 创建IPv4搜索引擎
     * $v6Searcher = $searcher->createSearcher('v6'); // 创建IPv6搜索引擎
     * ```
     */
    private function createSearcher($version)
    {
        try {
            // 使用自动缓存机制获取数据库文件
            $file = $this->getDbFile($version);

            if ($version === 'v4') {
                $ipVersion = 4;
            } else {
                $ipVersion = 6;
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
     *
     * 使用内存模式查询IP地址的地理位置信息
     * 这是最常用的查询方法，返回标准格式的结果
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return array 返回包含城市ID和地区信息的数组
     *  格式：['city_id' => int, 'region' => string]
     * @throws \Exception 当IP地址无效或查询失败时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $result = $searcher->memorySearch('8.8.8.8');
     * echo $result['region']; // 输出：美国【Level3】
     * ```
     */
    public function memorySearch($ip)
    {
        $searcher = $this->getSearcher($ip);
        $region = $searcher->search($ip);
        return array('city_id' => 0, 'region' => $region === null ? '' : $region);
    }

    /**
     * 批量查询
     *
     * 一次性查询多个IP地址的地理位置信息
     * 支持IPv4和IPv6混合查询，自动处理查询失败的情况
     *
     * @param array $ips 要查询的IP地址数组
     * @return array 返回以IP地址为键，地区信息为值的关联数组
     *  查询失败的IP地址对应的值为 null
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $ips = array('8.8.8.8', '1.1.1.1', '114.114.114.114');
     * $results = $searcher->batchSearch($ips);
     * foreach ($results as $ip => $region) {
     *     echo "$ip => $region\n";
     * }
     * ```
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
     *
     * 专门用于查询IPv6地址的地理位置信息
     * 包含IPv6地址格式验证，确保只处理有效的IPv6地址
     *
     * @param string $ip 要查询的IPv6地址
     * @return string|null 返回地理位置字符串，查询失败返回 null
     * @throws \Exception 当不是有效的IPv6地址时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $result = $searcher->searchIPv6('2001:4860:4860::8888');
     * echo $result; // 输出：美国加利福尼亚州圣克拉拉【专线用户】
     * ```
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
     * 获取IP详细信息
     *
     * 查询IP地址并返回结构化的地理位置信息
     * 将原始查询结果解析为包含国家、地区、省份、城市、ISP等信息的数组
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return array|null 返回包含详细地理信息的数组，查询失败返回 null
     *      格式：[
     *        'country' => '国家',
     *        'region' => '地区',
     *        'province' => '省份',
     *        'city' => '城市',
     *        'isp' => 'ISP服务商',
     *        'ip' => '原始IP地址',
     *        'version' => 'IP版本(v4/v6)'
     *      ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $info = $searcher->getIpInfo('8.8.8.8');
     * echo $info['country']; // 输出：美国
     * echo $info['isp']; // 输出：Level3
     * ```
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
     * 检查是否为IPv6地址
     *
     * 使用PHP内置函数验证给定字符串是否为有效的IPv6地址格式
     *
     * @param string $ip 要检查的IP地址字符串
     * @return bool 返回 true 表示是有效的IPv6地址，false 表示不是
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * var_dump($searcher->isIPv6('2001:4860:4860::8888')); // 输出：true
     * var_dump($searcher->isIPv6('8.8.8.8')); // 输出：false
     * ```
     */
    private function isIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * 获取统计信息
     *
     * 返回当前实例的详细统计信息，包括内存使用、IO次数、加载状态等
     * 用于性能监控和调试
     *
     * @return array 返回包含统计信息的数组
     *  格式：[
     *    'memory_usage' => int,      // 当前内存使用量（字节）
     *    'peak_memory' => int,       // 峰值内存使用量（字节）
     *    'v4_io_count' => int,       // IPv4 IO操作次数
     *    'v6_io_count' => int,       // IPv6 IO操作次数
     *    'v4_loaded' => bool,        // IPv4搜索引擎是否已加载
     *    'v6_loaded' => bool,        // IPv6搜索引擎是否已加载
     *    'cache_policy' => string    // 当前缓存策略
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $searcher->memorySearch('8.8.8.8'); // 触发IPv4搜索引擎加载
     * $stats = $searcher->getStats();
     * echo "内存使用: " . $stats['memory_usage'] . " 字节\n";
     * echo "IPv4已加载: " . ($stats['v4_loaded'] ? '是' : '否') . "\n";
     * ```
     */
    public function getStats()
    {
        $stats = array(
            'memory_usage' => memory_get_usage(true),
            'peak_memory'  => memory_get_peak_usage(true),
            'v4_io_count'  => 0,
            'v6_io_count'  => 0,
            'v4_loaded'    => $this->searcherV4 !== null,
            'v6_loaded'    => $this->searcherV6 !== null,
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
     *
     * 返回当前实例的内存使用情况，包括当前内存和峰值内存
     * 提供人性化的字节格式显示
     *
     * @return array 返回包含内存使用信息的数组
     *  格式：[
     *    'current' => string,    // 当前内存使用量（格式化后）
     *    'peak' => string,       // 峰值内存使用量（格式化后）
     *    'v4_loaded' => bool,    // IPv4搜索引擎是否已加载
     *    'v6_loaded' => bool     // IPv6搜索引擎是否已加载
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $memory = $searcher->getMemoryUsage();
     * echo "当前内存: " . $memory['current'] . "\n";
     * echo "峰值内存: " . $memory['peak'] . "\n";
     * ```
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
     *
     * 将字节数转换为人类可读的格式（B、KB、MB、GB、TB）
     *
     * @param int $bytes 要格式化的字节数
     * @param int $precision 小数位数，默认为2位
     * @return string 返回格式化后的字符串，如 "1.23 MB"
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * echo $searcher->formatBytes(1024); // 输出：1 KB
     * echo $searcher->formatBytes(1048576); // 输出：1 MB
     * ```
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
     *
     * 清理所有缓存文件，包括静态缓存和持久化缓存
     * 这是一个静态方法，可以全局清理所有缓存
     *
     * @return void
     *
     * @example
     * ```php
     * // 清理所有缓存
     * Ip2Region::clearCache();
     * ```
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
     *
     * 清理指定天数之前的过期缓存文件
     * 用于定期维护，避免缓存文件过多占用磁盘空间
     *
     * @param int $days 过期天数，默认为7天
     * @return void
     *
     * @example
     * ```php
     * // 清理7天前的缓存（默认）
     * Ip2Region::clearExpiredCache();
     *
     * // 清理30天前的缓存
     * Ip2Region::clearExpiredCache(30);
     * ```
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
     *
     * @return int 返回清理的文件数量
     *
     * @example
     * ```php
     * // 清理持久化缓存
     * $count = Ip2Region::clearPersistentCache();
     * echo "清理了 $count 个缓存文件\n";
     * ```
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
     *
     * 返回当前缓存系统的统计信息，包括缓存目录、文件数量、总大小等
     * 用于监控缓存使用情况和调试
     *
     * @return array 返回包含缓存统计信息的数组
     *  格式：[
     *    'cache_dir' => string,    // 缓存目录路径
     *    'cache_files' => int,     // 缓存文件数量
     *    'total_size' => int,      // 缓存文件总大小（字节）
     *    'v4_cached' => bool,      // IPv4缓存是否存在
     *    'v6_cached' => bool       // IPv6缓存是否存在
     *  ]
     *
     * @example
     * ```php
     * $stats = Ip2Region::getCacheStats();
     * echo "缓存目录: " . $stats['cache_dir'] . "\n";
     * echo "缓存文件数: " . $stats['cache_files'] . "\n";
     * echo "总大小: " . $stats['total_size'] . " 字节\n";
     * ```
     */
    public static function getCacheStats()
    {
        $cacheDir = sys_get_temp_dir() . '/ip2region_cache';
        $stats = array(
            'cache_dir'   => $cacheDir,
            'cache_files' => 0,
            'total_size'  => 0,
            'v4_cached'   => false,
            'v6_cached'   => false
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
     *
     * 提供简化的查询接口，返回格式化的地理位置字符串
     * 自动处理空值和内网IP，提供更友好的显示格式
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return string|null 返回格式化的地理位置字符串，查询失败返回 null
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * echo $searcher->simple('8.8.8.8'); // 输出：美国【Level3】
     * echo $searcher->simple('114.114.114.114'); // 输出：中国|江苏省|南京市【114DNS】
     * ```
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
     *
     * 提供基础的搜索接口，返回原始的地理位置字符串
     * 与 memorySearch 方法功能相同，但返回格式更简洁
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return string|null 返回原始地理位置字符串，查询失败返回 null
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * echo $searcher->search('8.8.8.8'); // 输出：美国|0|0|Level3
     * echo $searcher->search('114.114.114.114'); // 输出：中国|江苏省|南京市|0
     * ```
     */
    public function search($ip)
    {
        $result = $this->memorySearch($ip);
        return isset($result['region']) ? $result['region'] : null;
    }

    /**
     * 二进制搜索方法（兼容旧版本）
     *
     * 提供二进制搜索接口，与 memorySearch 方法功能相同
     * 主要用于向后兼容，实际使用 memorySearch 方法
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return array 返回包含城市ID和地区信息的数组
     *  格式：['city_id' => int, 'region' => string]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $result = $searcher->binarySearch('8.8.8.8');
     * echo $result['region']; // 输出：美国|0|0|Level3
     * ```
     */
    public function binarySearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 二进制字节搜索方法
     *
     * 使用二进制格式的IP地址进行查询，支持IPv4和IPv6
     * IPv4使用4字节，IPv6使用16字节的二进制格式
     *
     * @param string $ipBytes 二进制格式的IP地址
     *           - IPv4: 4字节二进制字符串
     *           - IPv6: 16字节二进制字符串
     * @return string|null 返回地理位置字符串，查询失败返回 null
     * @throws \Exception 当IP版本无法确定或搜索引擎创建失败时抛出异常
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $ipv4Bytes = inet_pton('8.8.8.8'); // 4字节二进制
     * $result = $searcher->searchByBytes($ipv4Bytes);
     * echo $result; // 输出：美国|0|0|Level3
     * ```
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
     *
     * 提供B树搜索接口，与 memorySearch 方法功能相同
     * 主要用于向后兼容，实际使用 memorySearch 方法
     *
     * @param string $ip 要查询的IP地址（支持IPv4和IPv6）
     * @return array 返回包含城市ID和地区信息的数组
     *  格式：['city_id' => int, 'region' => string]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $result = $searcher->btreeSearch('8.8.8.8');
     * echo $result['region']; // 输出：美国|0|0|Level3
     * ```
     */
    public function btreeSearch($ip)
    {
        return $this->memorySearch($ip);
    }

    /**
     * 获取IP协议版本（公共方法）
     *
     * 检测给定IP地址的协议版本，支持IPv4和IPv6
     * 这是 getIpVersion 方法的公共版本，不会抛出异常
     *
     * @param string $ip 要检测的IP地址
     * @return string 返回 'v4' 表示IPv4，'v6' 表示IPv6，'unknown' 表示无效IP
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * echo $searcher->getProtocolVersion('8.8.8.8'); // 输出：v4
     * echo $searcher->getProtocolVersion('2001:4860:4860::8888'); // 输出：v6
     * echo $searcher->getProtocolVersion('invalid-ip'); // 输出：unknown
     * ```
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
     *
     * 返回当前实例的IO操作统计信息
     * 包括IPv4和IPv6的IO次数以及总次数
     *
     * @return array 返回包含IO计数信息的数组
     *  格式：[
     *    'v4_io_count' => int,      // IPv4 IO操作次数
     *    'v6_io_count' => int,      // IPv6 IO操作次数
     *    'total_io_count' => int    // 总IO操作次数
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $searcher->memorySearch('8.8.8.8'); // 触发IPv4查询
     * $ioCount = $searcher->getIOCount();
     * echo "总IO次数: " . $ioCount['total_io_count'] . "\n";
     * ```
     */
    public function getIOCount()
    {
        $stats = $this->getStats();
        return array(
            'v4_io_count'    => $stats['v4_io_count'],
            'v6_io_count'    => $stats['v6_io_count'],
            'total_io_count' => $stats['v4_io_count'] + $stats['v6_io_count']
        );
    }

    /**
     * 检查是否支持IPv6
     *
     * 检查当前实例是否支持IPv6查询
     * 当前版本始终返回 true，表示支持IPv6
     *
     * @return bool 返回 true 表示支持IPv6，false 表示不支持
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * if ($searcher->isIPv6Supported()) {
     *     echo "支持IPv6查询\n";
     * } else {
     *     echo "不支持IPv6查询\n";
     * }
     * ```
     */
    public function isIPv6Supported()
    {
        return true;
    }

    /**
     * 检查是否支持IPv4
     *
     * 检查当前实例是否支持IPv4查询
     * 当前版本始终返回 true，表示支持IPv4
     *
     * @return bool 返回 true 表示支持IPv4，false 表示不支持
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * if ($searcher->isIPv4Supported()) {
     *     echo "支持IPv4查询\n";
     * } else {
     *     echo "不支持IPv4查询\n";
     * }
     * ```
     */
    public function isIPv4Supported()
    {
        return true;
    }

    /**
     * 获取数据库信息
     *
     * 返回当前实例的数据库加载状态和配置信息
     * 包括IPv4/IPv6加载状态、缓存策略、自定义路径等
     *
     * @return array 返回包含数据库信息的数组
     *  格式：[
     *    'v4_loaded' => bool,        // IPv4搜索引擎是否已加载
     *    'v6_loaded' => bool,        // IPv6搜索引擎是否已加载
     *    'cache_policy' => string,   // 当前缓存策略
     *    'custom_v4_path' => string, // 自定义IPv4数据库路径
     *    'custom_v6_path' => string, // 自定义IPv6数据库路径
     *    'v4_version' => int,        // IPv4数据库版本（如果已加载）
     *    'v6_version' => int         // IPv6数据库版本（如果已加载）
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $info = $searcher->getDatabaseInfo();
     * echo "IPv4已加载: " . ($info['v4_loaded'] ? '是' : '否') . "\n";
     * echo "缓存策略: " . $info['cache_policy'] . "\n";
     * ```
     */
    public function getDatabaseInfo()
    {
        $info = array(
            'v4_loaded'      => $this->searcherV4 !== null,
            'v6_loaded'      => $this->searcherV6 !== null,
            'cache_policy'   => $this->cachePolicy,
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
     *
     * 返回自定义数据库文件的详细信息，包括文件路径、大小、修改时间等
     * 用于检查自定义数据库文件的状态
     *
     * @return array 返回包含自定义数据库文件信息的数组
     *  格式：[
     *    'v4_path' => string,        // IPv4自定义数据库路径
     *    'v6_path' => string,        // IPv6自定义数据库路径
     *    'v4_exists' => bool,        // IPv4文件是否存在
     *    'v6_exists' => bool,        // IPv6文件是否存在
     *    'v4_size' => int,           // IPv4文件大小（字节）
     *    'v6_size' => int,           // IPv6文件大小（字节）
     *    'v4_modified' => int,       // IPv4文件修改时间
     *    'v6_modified' => int        // IPv6文件修改时间
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $info = $searcher->getCustomDbInfo();
     * if ($info['v4_exists']) {
     *     echo "IPv4文件大小: " . $info['v4_size'] . " 字节\n";
     * }
     * ```
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
     *
     * 设置IPv4和IPv6的自定义数据库文件路径
     * 设置后会自动重置已加载的搜索引擎，强制重新加载
     *
     * @param string|null $v4Path IPv4数据库文件路径，为 null 时使用默认路径
     * @param string|null $v6Path IPv6数据库文件路径，为 null 时使用默认路径
     * @return void
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * // 设置自定义数据库路径
     * $searcher->setCustomDbPaths('/path/to/ipv4.xdb', '/path/to/ipv6.xdb');
     *
     * // 只设置IPv4路径
     * $searcher->setCustomDbPaths('/path/to/ipv4.xdb');
     *
     * // 重置为默认路径
     * $searcher->setCustomDbPaths();
     * ```
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
     *
     * 检查当前实例是否正在使用自定义数据库文件
     * 包括IPv4和IPv6的自定义数据库使用状态
     *
     * @return array 返回包含使用状态的数组
     *  格式：[
     *    'v4' => bool,    // IPv4是否使用自定义数据库
     *    'v6' => bool     // IPv6是否使用自定义数据库
     *  ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $searcher->setCustomDbPaths('/path/to/ipv4.xdb');
     * $status = $searcher->isUsingCustomDb();
     * echo "IPv4使用自定义数据库: " . ($status['v4'] ? '是' : '否') . "\n";
     * ```
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
     *
     * 清理系统临时目录中的所有缓存文件
     * 这是一个私有方法，由 clearCache 静态方法调用
     *
     * @return void
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $searcher->clearCacheFiles(); // 清理所有缓存文件
     * ```
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
     *
     * 获取指定数据库文件的详细信息，包括路径、大小、修改时间等
     * 用于检查数据库文件的状态和属性
     *
     * @param string|null $filePath 数据库文件路径
     * @return array|null 返回包含文件信息的数组，文件不存在时返回 null
     *      格式：[
     *        'path' => string,      // 文件路径
     *        'size' => int,        // 文件大小（字节）
     *        'modified' => int,    // 修改时间（Unix时间戳）
     *        'exists' => bool      // 文件是否存在
     *      ]
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $info = $searcher->getDbFileInfo('/path/to/database.xdb');
     * if ($info) {
     *     echo "文件大小: " . $info['size'] . " 字节\n";
     *     echo "修改时间: " . date('Y-m-d H:i:s', $info['modified']) . "\n";
     * }
     * ```
     */
    private function getDbFileInfo($filePath)
    {
        if ($filePath === null || !file_exists($filePath)) {
            return null;
        }

        return array(
            'path'     => $filePath,
            'size'     => filesize($filePath),
            'modified' => filemtime($filePath),
            'exists'   => true
        );
    }

    /**
     * 检查自定义数据库是否存在
     *
     * 检查指定的自定义数据库文件是否存在且可读
     * 用于验证自定义数据库路径的有效性
     *
     * @param string|null $filePath 数据库文件路径
     * @return bool 返回 true 表示文件存在且可读，false 表示不存在或不可读
     *
     * @example
     * ```php
     * $searcher = new Ip2Region();
     * $exists = $searcher->isCustomDbExists('/path/to/database.xdb');
     * if ($exists) {
     *     echo "自定义数据库文件存在\n";
     * } else {
     *     echo "自定义数据库文件不存在或不可读\n";
     * }
     * ```
     */
    private function isCustomDbExists($filePath)
    {
        return $filePath !== null && file_exists($filePath);
    }
}
