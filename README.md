[![Latest Stable Version](https://poser.pugx.org/zoujingli/ip2region/v/stable)](https://packagist.org/packages/zoujingli/ip2region)
[![Total Downloads](https://poser.pugx.org/zoujingli/ip2region/downloads)](https://packagist.org/packages/zoujingli/ip2region)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/ip2region/d/monthly)](https://packagist.org/packages/zoujingli/ip2region)
[![Daily Downloads](https://poser.pugx.org/zoujingli/ip2region/d/daily)](https://packagist.org/packages/zoujingli/ip2region)
[![PHP Version Require](http://poser.pugx.org/zoujingli/ip2region/require/php)](https://packagist.org/packages/ip2region)
[![License](https://poser.pugx.org/zoujingli/ip2region/license)](https://packagist.org/packages/zoujingli/ip2region)

# ip2region v3.0

🚀 **企业级 IP 地理位置查询库**：**支持 IPv4 和 IPv6**，自动缓存，零依赖，**开箱即用**。

基于官方 [ip2region](https://github.com/lionsoul2014/ip2region) 深度优化，专为 PHP 项目定制，提供毫秒级 IP 地理位置查询服务。支持 IPv4 和 IPv6 双协议查询，具备完善的缓存机制和错误处理，适用于企业级应用场景。

> ⚠️ **重要提示**：
> - **IPv4 查询**：✅ 开箱即用，无需下载
> - **IPv6 查询**：⚠️ **需要下载完整数据库**（617MB），请使用 `./vendor/bin/ip2down download v6` 命令下载

> 📢 **v3.0 更新**：
> - ✅ **代码优化**：工具代码减少 48.7%，更简洁高效
> - ✅ **开箱即用**：IPv4 查询无需下载，直接使用内置压缩文件
> - ✅ **按需下载**：IPv6 查询需要时再下载完整数据库
> - ✅ **压缩优化**：IPv4 支持压缩文件，IPv6 必须使用完整数据库
> - ✅ **体积优化**：项目仅包含必要的 IPv4 压缩文件

> 💡 **版本选择建议**：
> - **V3.0**：推荐使用，IPv4 开箱即用，IPv6 按需下载，自动缓存，代码更简洁

## 📦 核心特性

| 特性          | 描述                                        |
| ------------- | ------------------------------------------- |
| **IPv4 支持** | ✅ 开箱即用，内置压缩文件                    |
| **IPv6 支持** | ⚠️ **需要下载**，完整数据库（617MB）         |
| 自动缓存      | ✅ 自动缓存，避免重复解压                    |
| 性能          | ✅ 极快，微秒级响应                         |
| 零依赖        | ✅ 纯 PHP 实现，无需额外扩展                |
| 企业级        | ✅ 完善的错误处理和性能监控                  |

## 🎯 项目简介

ip2region 是一个高性能的 IP 地址定位库，**支持 IPv4 和 IPv6 地址查询**。通过自动缓存技术，实现了大数据库文件的高效管理，为企业和开发者提供准确、快速的 IP 地理位置查询服务。

**V3.0 核心特性**：
- 🚀 **开箱即用**：IPv4 查询无需下载，直接使用内置压缩文件
- ⚠️ **IPv6 需要下载**：IPv6 查询需要下载完整数据库（617MB）
- ⚡ **压缩优化**：IPv4 支持压缩文件，IPv6 必须使用完整数据库

**使用示例**：
```php
echo ip2region('8.8.8.8'); 
// 输出：美国【Level3】

echo ip2region('114.114.114.114'); 
// 输出：中国江苏省南京市【114DNS】
```


## ✨ 核心特性

-   **🌍 双协议支持**：**支持 IPv4 和 IPv6 地址查询**，自动识别 IP 版本
-   **⚡ 高性能**：基于官方 xdb 格式，查询速度极快，微秒级响应
-   **📦 零依赖**：纯 PHP 实现，兼容 PHP 5.4+，无需额外扩展
-   **🚀 开箱即用**：IPv4 查询无需下载，直接使用内置文件
-   **⚠️ IPv6 需要下载**：IPv6 查询需要下载完整数据库（617MB）
-   **🔧 自定义数据库**：支持自定义 IPv4/IPv6 数据库路径配置
-   **🔧 易集成**：支持 Composer 安装，提供函数式和面向对象两种 API
-   **💾 自动缓存**：支持文件缓存、VectorIndex 缓存、完整数据缓存
-   **🗜️ 高效压缩**：支持 gzip、zip、zstd 多种格式，压缩率高达 81%
-   **🛡️ 企业级**：完善的错误处理、异常管理和性能监控
-   **🔄 懒加载**：IPv4/IPv6 查询器按需创建，优化内存使用

## 🏗️ 技术架构

### 自动加载机制

项目采用优化的 Composer 自动加载策略：

```json
{
  "autoload": {
    "psr-4": {
      "ip2region\\": "src/ip2region/"
    },
    "classmap": [
      "src/Ip2Region.php"
    ],
    "files": [
      "function.php"
    ]
  }
}
```

**设计特点**：
- **PSR-4 加载**：`ip2region\xdb\*` 类使用 PSR-4 标准自动加载
- **全局类**：主类 `Ip2Region` 使用全局命名空间，便于直接使用
- **全局函数**：`function.php` 提供便捷的全局函数接口
- **组件化**：保持原有组件的命名空间和目录结构

### 类结构设计

```
Ip2Region (全局类)
├── 使用 ip2region\xdb\IPv4
├── 使用 ip2region\xdb\IPv6  
└── 使用 ip2region\xdb\Searcher

ip2region\xdb\* (组件类)
├── Util.php       # 工具类
├── IPv4.php       # IPv4 处理
├── IPv6.php       # IPv6 处理
└── Searcher.php   # 搜索引擎
```

### 工具集成

- **工具类设计**：`bin/ip2down` 使用内置类实现数据库管理功能
- **代码优化**：工具代码减少 48.7%，更简洁高效
- **减少依赖**：移除独立的 `DatabaseManager.php` 文件
- **简化维护**：所有工具功能集中在一个文件中

## 📁 项目结构

```
ip2region/
├── src/                    # 核心源码
│   ├── Ip2Region.php      # 主类（全局命名空间）
│   └── ip2region/
│       └── xdb/
│           ├── Util.php       # 工具类（ip2region\xdb\Util）
│           ├── IPv4.php       # IPv4 处理类（ip2region\xdb\IPv4）
│           ├── IPv6.php       # IPv6 处理类（ip2region\xdb\IPv6）
│           └── Searcher.php   # 搜索引擎类（ip2region\xdb\Searcher）
├── db/                    # 压缩数据库文件目录（已包含）
│   └── ip2region_v4.xdb.gz        # IPv4 压缩数据库文件
├── vendor/
│   └── bin/
│       └── ip2data/       # 完整数据库文件目录（需要下载）
│           ├── ip2region_v4.xdb   # IPv4 完整数据库文件
│           └── ip2region_v6.xdb   # IPv6 完整数据库文件 ⚠️ 需要下载
├── bin/                   # 命令行工具
│   └── ip2down            # 数据库下载管理工具（内置类实现，支持实时进度显示）
├── tools/                 # 开发工具（内部使用）
│   ├── compress_db.php    # 数据库压缩工具
│   └── ip2region_v4.xdb   # IPv4 源数据库文件
├── tests/                 # 测试文件
│   ├── demo.php           # 演示程序
│   └── quick_performance_test.php # 性能测试脚本
├── function.php           # 全局函数入口
├── composer.json          # Composer 配置
└── README.md              # 项目文档
```

> **💡 重要提示**：
>
> -   **IPv4 查询**：✅ 开箱即用，项目已包含压缩文件
> -   **IPv6 查询**：⚠️ **需要下载完整数据库**（617MB），使用 `ip2down download v6` 命令
> -   **自定义数据库**：支持通过构造函数指定自定义数据库路径
> -   **压缩文件**：IPv4 支持压缩文件，IPv6 必须使用完整数据库

## 🆕 v3.0 新增功能

### 压缩文件管理

-   **自动压缩**：IPv4 数据库自动压缩为 gzip 格式
-   **压缩支持**：支持 gzip 格式，显著减小文件大小（压缩率可达 60%+）
-   **按需解压**：首次使用时自动解压压缩文件
-   **自动缓存**：解压后的文件缓存到临时目录，避免重复解压

### 增强的 API

-   **双协议支持**：`ip2region()` 函数自动识别 IPv4/IPv6
-   **面向对象**：`Ip2Region` 类提供完整的面向对象接口
-   **批量查询**：`batchSearch()` 方法支持批量 IP 查询
-   **性能监控**：`getStats()` 和 `getMemoryUsage()` 方法监控性能

### 企业级特性

-   **错误处理**：完善的异常处理和错误提示
-   **并发安全**：支持多进程/多线程安全使用
-   **缓存策略**：支持文件、VectorIndex、完整数据三种缓存方式
-   **PHP 5.4+ 兼容**：完全兼容 PHP 5.4 及以上版本

## 🚀 快速开始

### 1. 通过 Composer 安装

```bash
# 安装 V3.0 版本（推荐，功能完整）
composer require zoujingli/ip2region:^3.0
```

### 2. 下载数据库文件

> ⚠️ **重要**：IPv6 查询需要下载完整数据库文件（617MB）

**IPv4 查询**：✅ 开箱即用，无需下载
**IPv6 查询**：⚠️ 需要下载完整数据库文件

**方法一：使用下载工具（推荐）**

```bash
# 下载 IPv6 数据库（617MB，支持实时进度显示）
./vendor/bin/ip2down download v6

# 下载所有数据库
./vendor/bin/ip2down download all

# 查看已下载的文件
./vendor/bin/ip2down list
```

**方法二：手动下载**

```bash
# 创建数据库目录
mkdir -p db

# 下载 IPv6 数据库（617MB，推荐使用 Gitee 镜像，国内访问更快）
wget -O db/ip2region_v6.xdb "https://gitee.com/lionsoul/ip2region/raw/master/data/ip2region_v6.xdb?lfs=1"

# 或者使用 curl（如果 wget 不可用）
curl -L -o db/ip2region_v6.xdb "https://gitee.com/lionsoul/ip2region/raw/master/data/ip2region_v6.xdb?lfs=1"
```

> 💡 **下载提示**：
> - **IPv4**：已包含在项目中，无需下载
> - **IPv6**：推荐使用 Gitee 镜像，国内访问速度更快更稳定
> - **备用链接**：如果 Gitee 不可用，可使用 GitHub 原始链接

**方法二：使用下载工具（推荐）**

```bash
# 下载 IPv6 数据库（617MB，支持实时进度显示）
./vendor/bin/ip2down download v6

# 或者下载所有数据库
./vendor/bin/ip2down download all

# 尝试自动下载（可能因网络问题失败）
composer download-db

# 查看已下载的文件
./vendor/bin/ip2down list

# 测试数据库功能
./vendor/bin/ip2down test
```

**进度显示特性**：
- ✅ **实时进度**：显示下载速度和预计剩余时间
- ✅ **自动估算**：基于文件大小估算完成时间
- ✅ **流式下载**：避免大文件内存溢出
- ✅ **断点续传**：支持网络中断后重新下载

**进度显示示例**：
```bash
正在下载 IPv6 数据库...
已下载: 9.3 MB - 4.65 MB/s - 预计剩余 127s
已下载: 22.62 MB - 5.65 MB/s - 预计剩余 102s
已下载: 41.84 MB - 6.97 MB/s - 预计剩余 80s
...
✅ 下载完成: IPv6 数据库 (617.1 MB)
```

> 📝 **注意**：
> - IPv4 数据库可以正常自动下载（10.5MB）
> - IPv6 数据库较大（617MB），建议使用下载工具或手动下载

### 数据库优先级

系统按以下优先级查找数据库文件：

1. **自定义数据库**：通过构造函数指定的 `.xdb` 文件路径
2. **下载的数据库**：通过 `ip2down` 工具下载的完整数据库文件
3. **IPv4 压缩文件**：仅 IPv4 支持，自动解压压缩文件
4. **IPv6 压缩文件**：⚠️ **不建议使用压缩**，建议直接使用完整数据库文件

> ⚠️ **重要**：
> - **IPv4**：支持压缩文件，开箱即用
> - **IPv6**：⚠️ **不建议压缩**，直接使用完整数据库文件（617MB）性能更佳

### 3. 自定义数据库配置

项目已包含压缩数据库文件，可直接使用。如需使用自定义数据库：

```php
<?php
require 'vendor/autoload.php';

try {
    // 使用自定义数据库路径（建议使用绝对路径）
    $ip2region = new \Ip2Region('file', 
        '/path/to/your/ip2region_v4.xdb',  // IPv4 数据库路径
        '/path/to/your/ip2region_v6.xdb'   // IPv6 数据库路径
    );
    
    // 查询示例
    echo $ip2region->simple('61.142.118.231'); // 中国广东省中山市【电信】
    echo $ip2region->simple('2001:4860:4860::8888'); // Google DNS
?>
```

**获取数据库文件**：
- **IPv4 数据库**：✅ 已包含在项目中，无需下载
- **IPv6 数据库**：⚠️ **需要下载**（617MB），推荐从 [Gitee 镜像](https://gitee.com/lionsoul/ip2region) 下载
- **商业版本**：从 [ip2region 官网](https://www.ip2region.net/) 购买或下载
- **详细说明**：请参考 [自定义数据库使用说明](CUSTOM_DB_USAGE.md)

### 3. 一行代码开始使用

```php
<?php
require 'vendor/autoload.php';

// 最简单的使用方式
echo ip2region('61.142.118.231') . "\n"; // 中国广东省中山市【电信】（使用压缩文件）
echo ip2region('2001:4860:4860::8888') . "\n"; // 美国加利福尼亚州圣克拉拉【专线用户】（需要下载完整数据库）

// 使用不同查询方法
echo ip2region('61.142.118.231', 'search'); // 中国|广东省|中山市|电信
echo ip2region('61.142.118.231', 'memory'); // 返回数组格式

// 或者使用类方式
$ip2region = new \Ip2Region();
echo $ip2region->simple('61.142.118.231'); // 中国广东省中山市【电信】
?>
```

### 4. 验证安装

```bash
# 运行演示程序
composer demo

# 查询指定 IP
composer query 61.142.118.231

# 批量查询 IP
composer query:batch "8.8.8.8,114.114.114.114"

# 运行性能测试
composer performance
```

## 在项目中快速调用

### 函数式调用

```php
<?php
require 'vendor/autoload.php';

// 简单查询
echo ip2region('61.142.118.231') . "\n";        // 中国广东省中山市【电信】
echo ip2region('2001:4860:4860::8888') . "\n"; // 美国加利福尼亚州圣克拉拉【专线用户】

// 使用不同查询方法
echo ip2region('61.142.118.231', 'search') . "\n"; // 中国|广东省|中山市|电信
echo ip2region('61.142.118.231', 'memory') . "\n"; // 返回数组格式

// 批量查询
$ips = ['61.142.118.231', '114.114.114.114', '2001:4860:4860::8888'];
foreach ($ips as $ip) {
    echo "$ip => " . ip2region($ip) . "\n";
}
?>
```

### 面向对象调用

```php
<?php
require 'vendor/autoload.php';

try {
    // 默认模式（使用压缩数据库）
    $ip2region = new \Ip2Region();
    
    // 如需使用自定义数据库，请参考下面的"自定义数据库配置"部分
    // $ip2region = new \Ip2Region('file', '/path/to/your/ip2region_v4.xdb', '/path/to/your/ip2region_v6.xdb');

    // 基础查询
    echo $ip2region->simple('61.142.118.231') . "\n";
    echo $ip2region->search('2001:4860:4860::8888') . "\n";

    // 获取详细信息
    $info = $ip2region->getIpInfo('61.142.118.231');
    print_r($info);
    // 输出: Array(
    //   [country] => 中国
    //   [region] => 广东省
    //   [province] => 中山市
    //   [city] => 电信
    //   [isp] =>
    //   [ip] => 61.142.118.231
    //   [version] => v4
    // )

    // 批量查询
    $results = $ip2region->batchSearch(['61.142.118.231', '114.114.114.114']);
    print_r($results);

    // 性能监控
    $stats = $ip2region->getStats();
    echo "内存使用: " . $stats['memory_usage'] . " bytes\n";
    echo "IPv4 已加载: " . ($stats['v4_loaded'] ? '是' : '否') . "\n";
    echo "IPv6 已加载: " . ($stats['v6_loaded'] ? '是' : '否') . "\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
```


### 自定义数据库配置

```php
<?php
require 'vendor/autoload.php';

try {
    // 使用自定义数据库路径（建议使用绝对路径）
    $ip2region = new \Ip2Region('file', '/path/to/your/ip2region_v4.xdb', '/path/to/your/ip2region_v6.xdb');

    // 查询IP
    echo $ip2region->simple('8.8.8.8') . "\n";

    // 检查是否使用自定义数据库
    $customStatus = $ip2region->isUsingCustomDb();
    echo "IPv4 使用自定义数据库: " . ($customStatus['v4'] ? '是' : '否') . "\n";
    echo "IPv6 使用自定义数据库: " . ($customStatus['v6'] ? '是' : '否') . "\n";

    // 动态设置数据库路径
    $ip2region->setCustomDbPaths('/path/to/v4.xdb', '/path/to/v6.xdb');

    // 获取数据库配置信息
    $dbInfo = $ip2region->getDatabaseInfo();
    echo "IPv4 路径: " . ($dbInfo['custom_v4_path'] ?: '默认压缩') . "\n";
    echo "IPv6 路径: " . ($dbInfo['custom_v6_path'] ?: '需要下载') . "\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
```

## 数据库文件准备

### 使用预置数据库

项目已包含压缩数据库文件，位于 `db/` 目录：

-   `ip2region_v4.xdb.gz` - IPv4 压缩数据库文件
-   IPv6 需要下载完整数据库文件

### 使用自定义数据库

如果需要使用自定义的数据库文件，请按以下步骤操作：

#### 1. 获取完整数据库文件

**重要**：IPv4 源数据库文件已放置在 `tools/` 目录，可直接使用压缩工具：

```
tools/
├── compress_db.php      # 数据库压缩工具
└── ip2region_v4.xdb    # IPv4 源数据库文件（已包含）
```

**文件说明**：

-   **IPv4 源文件**：已包含在 `tools/` 目录，可直接使用
-   **IPv6 源文件**：需要从官方仓库下载到 `tools/` 目录
-   **文件大小**：IPv4 约 11MB，IPv6 约 617MB
-   **文件格式**：必须是有效的 xdb 格式文件

**获取数据库文件**：

-   **免费版本**：从 [ip2region 官方仓库](https://github.com/lionsoul2014/ip2region) 下载
-   IPv4 数据库：[ip2region_v4.xdb](https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v4.xdb) (10.5MB)
-   IPv6 数据库：[ip2region_v6.xdb](https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v6.xdb) (617MB)
-   **商业版本**：从 [ip2region 官网](https://www.ip2region.net/) 购买或下载
-   **格式要求**：确保下载的是 `.xdb` 格式，不是 `.txt` 或其他格式
-   **版本选择**：建议使用最新版本以获得最准确的地理位置数据
-   **重要提醒**：自定义数据库文件需要从官网下载或购买，确保使用正版数据源

#### 2. 压缩工具（开发用）

> **⚠️ 注意**：此工具仅用于开发发布，普通用户无需使用。项目已包含压缩文件，可直接使用。

使用项目提供的压缩工具将数据库文件压缩为小文件，支持多种压缩格式：

> ⚠️ **注意**：**仅建议压缩 IPv4 数据库**，IPv6 数据库不建议压缩，直接使用完整文件性能更佳。

```bash
# 基本用法（默认 gzip 压缩，仅 IPv4）
php tools/compress_db.php

# 自定义压缩方式（仅 IPv4）
php tools/compress_db.php ip2region_v4.xdb gzip    # IPv4，gzip 压缩
php tools/compress_db.php ip2region_v4.xdb zip     # IPv4，zip 压缩
php tools/compress_db.php ip2region_v4.xdb zstd    # IPv4，zstd 压缩

# 使用绝对路径压缩（仅 IPv4）
php tools/compress_db.php /path/to/ip2region_v4.xdb gzip

# 参数说明：
# 第一个参数：源文件路径，默认为 tools/ip2region_v4.xdb
# 第二个参数：压缩方式，可选值：gzip, zip, zstd，默认 gzip
# ⚠️ 注意：IPv6 数据库不建议压缩，直接使用完整文件
```

**压缩方式对比**：

-   **gzip**：压缩率高，解压速度快，推荐使用
-   **zip**：通用性好，兼容性强
-   **none**：不压缩，文件较大但处理最快

#### 3. 压缩文件说明

-   **输入文件**：`tools/` 目录下的源数据库文件
-   **输出目录**：`db/` 目录
-   **文件命名**：
    -   gzip 压缩：`ip2region_v4.xdb.gz`
    -   zip 压缩：`ip2region_v4.xdb.zip`
    -   zstd 压缩：`ip2region_v4.xdb.zst`
-   **压缩级别**：gzip 使用最高压缩级别（-9）
-   **压缩支持**：支持 gzip、zip、zstd 压缩，显著减小文件大小
-   **自动解压**：首次使用时自动解压压缩文件到临时缓存

#### 4. 实际使用示例

```bash
# 查看当前压缩文件
$ ls -la db/ip2region_*.xdb.gz
-rw-r--r--  1 user  staff   4320000  Dec 19 10:00 db/ip2region_v4.xdb.gz

# 查看压缩文件大小
$ du -h db/ip2region_*.xdb.gz
 4.1M    db/ip2region_v4.xdb.gz

# 测试压缩文件是否正常工作
$ composer query 8.8.8.8
美国【Level3】

$ composer query 2001:4860:4860::8888
美国加利福尼亚州圣克拉拉【专线用户】
```

## Composer 脚本

项目提供了便捷的 Composer 脚本命令：

### 核心功能
```bash
# 运行演示程序
composer demo

# 运行测试程序
composer test

# 查询单个 IP 地址
composer query 61.142.118.231

# 批量查询 IP 地址（逗号分隔）
composer query:batch "8.8.8.8,114.114.114.114,2001:4860:4860::8888"
```

### 数据库管理
```bash
# 下载所有数据库
composer download

# 下载 IPv4 数据库
composer download:v4

# 下载 IPv6 数据库
composer download:v6

# 压缩 IPv4 数据库（gzip 格式，IPv6 不建议压缩）
composer compress
```

### 工具命令
```bash
# 运行性能测试
composer performance

# 清理所有缓存
composer cache:clear

# 查看缓存统计
composer cache:stats

# 查看版本信息
composer version
```

## API 参考

### 全局函数

#### `ip2region($ip, $method = 'simple')`

-   **功能**：全局 IP 地理位置查询函数，提供统一的查询接口
-   **特性**：
    -   自动识别 IPv4/IPv6 地址类型
    -   支持多种查询方法和返回格式
    -   内置 IP 地址格式验证
    -   懒加载机制，按需初始化查询器
    -   异常安全，提供详细的错误信息
-   **参数**：
    -   `$ip` (string) - IP 地址，支持 IPv4 和 IPv6 格式
    -   `$method` (string) - 查询方法，可选值：simple, search, memory, binary, btree
-   **返回**：`string|array|null` - 查询结果，失败时返回 null
-   **异常**：当 IP 地址格式无效时抛出 `Exception`
-   **示例**：

    ```php
    // 简单查询（默认）
    echo ip2region('61.142.118.231');
    // 输出: 中国广东省中山市【电信】

    // 详细查询
    echo ip2region('61.142.118.231', 'search');
    // 输出: 中国|广东省|中山市|电信

    // 内存查询（返回数组）
    $result = ip2region('61.142.118.231', 'memory');
    // 输出: Array([city_id] => 0, [region] => 中国|广东省|中山市|电信)

    // IPv6 查询
    echo ip2region('2001:4860:4860::8888');
    // 输出: 美国加利福尼亚州圣克拉拉【专线用户】

    // 异常处理
    try {
        $result = ip2region('invalid-ip');
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage();
        // 如果是 IPv6 查询失败，提示下载数据库
        if (strpos($e->getMessage(), 'IPv6') !== false) {
            echo "\n提示: IPv6 查询需要下载完整数据库，请运行: ./vendor/bin/ip2down download v6";
        }
    }
    ```

#### 查询方法对比

| 方法名   | 描述             | 返回值类型 | 性能特点 | 适用场景               | 示例输出                                              |
| -------- | ---------------- | ---------- | -------- | ---------------------- | ----------------------------------------------------- |
| `simple` | 简单查询（默认） | string     | 最快     | 一般查询，用户友好显示 | `中国广东省中山市【电信】`                            |
| `search` | 详细查询         | string     | 快       | 需要原始数据格式       | `中国\|广东省\|中山市\|电信`                          |
| `memory` | 内存查询         | array      | 快       | 需要结构化数据         | `{"city_id":0,"region":"中国\|广东省\|中山市\|电信"}` |
| `binary` | 二进制搜索       | array      | 中等     | 有序数据快速查找       | `{"city_id":0,"region":"中国\|广东省\|中山市\|电信"}` |
| `btree`  | B 树索引         | array      | 中等     | 大规模数据平衡查询     | `{"city_id":0,"region":"中国\|广东省\|中山市\|电信"}` |

#### 使用建议

-   **一般查询**：使用 `simple` 方法，返回格式化的地理位置字符串
-   **数据处理**：使用 `search` 方法，获取原始数据格式便于解析
-   **程序集成**：使用 `memory` 方法，获取结构化数组数据
-   **性能优化**：根据数据量选择 `binary` 或 `btree` 方法
-   **异常处理**：始终使用 try-catch 包装函数调用
-   **IPv6 支持**：⚠️ IPv6 查询需要先下载完整数据库（617MB）

### Ip2Region 类

#### 构造函数

```php
new Ip2Region($cachePolicy = 'file', $dbPathV4 = null, $dbPathV6 = null)
```

-   **参数**：
    -   `$cachePolicy` (string) - 缓存策略：'file', 'vectorIndex', 'content'
    -   `$dbPathV4` (string|null) - IPv4 数据库文件路径，null 表示使用默认压缩
    -   `$dbPathV6` (string|null) - IPv6 数据库文件路径，null 表示需要下载

-   **示例**：
    ```php
    // 默认模式（使用压缩数据库）
    $ip2region = new Ip2Region();
    
    // 使用自定义数据库（建议使用绝对路径）
    $ip2region = new Ip2Region('file', '/path/to/your/ip2region_v4.xdb', '/path/to/your/ip2region_v6.xdb');
    
    // 只使用自定义 IPv4 数据库，IPv6 需要下载
    $ip2region = new Ip2Region('file', '/path/to/your/ip2region_v4.xdb', null);
    ```

#### 核心查询方法

##### `simple($ip)`

-   **功能**：简单查询，返回格式化结果
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`string|null` - 格式化查询结果
-   **示例**：`$ip2region->simple('61.142.118.231'); // 中国广东省中山市【电信】`

##### `search($ip)`

-   **功能**：基础查询，返回原始结果
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`string|null` - 原始查询结果
-   **示例**：`$ip2region->search('61.142.118.231'); // 中国|广东省|中山市|电信`

##### `memorySearch($ip)`

-   **功能**：内存查询，返回数组格式
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`array` - 包含 city_id 和 region 的数组
-   **示例**：`$ip2region->memorySearch('61.142.118.231'); // ['city_id' => 0, 'region' => '中国|广东省|中山市|电信']`

##### `batchSearch($ips)`

-   **功能**：批量查询多个 IP
-   **参数**：`$ips` (array) - IP 地址数组
-   **返回**：`array` - IP 地址为键的查询结果数组
-   **示例**：`$ip2region->batchSearch(['61.142.118.231', '114.114.114.114']);`

##### `getIpInfo($ip)`

-   **功能**：获取详细的 IP 信息
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`array|null` - 包含 country, region, province, city, isp, ip, version 的数组
-   **示例**：`$ip2region->getIpInfo('61.142.118.231');`

#### 兼容性方法

##### `binarySearch($ip)`

-   **功能**：二进制搜索（兼容旧版本）
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`array` - 查询结果数组

##### `btreeSearch($ip)`

-   **功能**：B 树搜索（兼容旧版本）
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`array` - 查询结果数组

##### `searchByBytes($ipBytes)`

-   **功能**：二进制字节搜索
-   **参数**：`$ipBytes` (string) - 二进制 IP 地址
-   **返回**：`string|null` - 查询结果

#### 工具方法

##### `getStats()`

-   **功能**：获取统计信息
-   **返回**：`array` - 包含内存使用、IO 计数、加载状态等
-   **示例**：`$stats = $ip2region->getStats();`

##### `getMemoryUsage()`

-   **功能**：获取内存使用情况
-   **返回**：`array` - 包含当前内存、峰值内存、加载状态等
-   **示例**：`$memory = $ip2region->getMemoryUsage();`

##### `getIOCount()`

-   **功能**：获取 IO 计数
-   **返回**：`array` - 包含 IPv4、IPv6 和总 IO 计数
-   **示例**：`$io = $ip2region->getIOCount();`

##### `getProtocolVersion($ip)`

-   **功能**：获取 IP 协议版本
-   **参数**：`$ip` (string) - IP 地址
-   **返回**：`string` - 'v4', 'v6' 或 'unknown'

##### `isIPv4Supported()`

-   **功能**：检查是否支持 IPv4
-   **返回**：`bool` - 是否支持 IPv4

##### `isIPv6Supported()`

-   **功能**：检查是否支持 IPv6
-   **返回**：`bool` - 是否支持 IPv6

##### `getDatabaseInfo()`

-   **功能**：获取数据库信息
-   **返回**：`array` - 包含加载状态、缓存策略、版本信息、自定义路径等

##### `setCustomDbPaths($v4Path, $v6Path)`

-   **功能**：动态设置自定义数据库路径
-   **参数**：
    -   `$v4Path` (string|null) - IPv4 数据库文件路径
    -   `$v6Path` (string|null) - IPv6 数据库文件路径
-   **示例**：`$ip2region->setCustomDbPaths('/path/to/v4.xdb', '/path/to/v6.xdb');`

##### `isUsingCustomDb()`

-   **功能**：检查是否使用自定义数据库
-   **返回**：`array` - 包含 IPv4 和 IPv6 的使用状态
-   **示例**：`$status = $ip2region->isUsingCustomDb();`

##### `getCustomDbInfo()`

-   **功能**：获取自定义数据库文件信息
-   **返回**：`array` - 包含自定义数据库文件的大小、修改时间等信息
-   **示例**：`$info = $ip2region->getCustomDbInfo();`

#### 静态方法

##### `Ip2Region::clearCache()`

-   **功能**：清理所有缓存
-   **示例**：`Ip2Region::clearCache();`

##### `Ip2Region::clearExpiredCache($days = 7)`

-   **功能**：清理过期缓存
-   **参数**：`$days` (int) - 过期天数
-   **示例**：`Ip2Region::clearExpiredCache(7);`

##### `Ip2Region::getCacheStats()`

-   **功能**：获取缓存统计信息
-   **返回**：`array` - 缓存统计信息
-   **示例**：`$cacheStats = Ip2Region::getCacheStats();`

## 性能监控示例

```php
<?php
require 'vendor/autoload.php';

try {
    // 默认模式（使用压缩数据库）
    $ip2region = new \Ip2Region();
    
    // 或者使用自定义数据库（建议使用绝对路径）
    // $ip2region = new \Ip2Region('file', '/path/to/your/ip2region_v4.xdb', '/path/to/your/ip2region_v6.xdb');

    // 查询前状态
    $statsBefore = $ip2region->getStats();
    echo "查询前内存使用: " . $statsBefore['memory_usage'] . " bytes\n";

    // 执行查询
    $result = $ip2region->simple('61.142.118.231');
    echo "查询结果: " . $result . "\n";

    // 查询后状态
    $statsAfter = $ip2region->getStats();
    echo "查询后内存使用: " . $statsAfter['memory_usage'] . " bytes\n";
    echo "IPv4 已加载: " . ($statsAfter['v4_loaded'] ? '是' : '否') . "\n";
    echo "IPv6 已加载: " . ($statsAfter['v6_loaded'] ? '是' : '否') . "\n";
    echo "IPv4 IO 次数: " . $statsAfter['v4_io_count'] . "\n";
    echo "IPv6 IO 次数: " . $statsAfter['v6_io_count'] . "\n";

    // 内存使用详情
    $memory = $ip2region->getMemoryUsage();
    echo "当前内存: " . $memory['current'] . "\n";
    echo "峰值内存: " . $memory['peak'] . "\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
```

## 缓存管理示例

```php
<?php
require 'vendor/autoload.php';

// 获取缓存统计信息
$cacheStats = \Ip2Region::getCacheStats();
echo "缓存目录: " . $cacheStats['cache_dir'] . "\n";
echo "缓存文件数: " . $cacheStats['file_count'] . "\n";
echo "缓存总大小: " . $cacheStats['total_size'] . " bytes\n";
echo "内存缓存数: " . $cacheStats['memory_cached'] . "\n";

// 清理过期缓存（7天前）
\Ip2Region::clearExpiredCache(7);
echo "已清理7天前的过期缓存\n";

// 清理所有缓存
\Ip2Region::clearCache();
echo "已清理所有缓存\n";
?>
```

## FPM 环境优化

### FPM 进程模型优化

在 FPM（FastCGI Process Manager）环境下，每个进程处理完请求后会保持存活，但静态变量会在进程重启时丢失。本库已针对 FPM 环境进行了优化：

#### 自动缓存机制

1. **持久化缓存**：解压后的数据库文件会缓存到系统临时目录
2. **自动验证**：基于文件修改时间和内容格式验证缓存文件的有效性
3. **自动恢复**：进程重启后自动检测并使用有效的缓存文件
4. **避免重复生成**：相同压缩文件不会重复解压
5. **自动更新**：当压缩文件更新时，自动重新生成缓存

#### 缓存目录

- **默认位置**：`/tmp/ip2region_cache/`（Linux/macOS）或 `C:\Users\用户名\AppData\Local\Temp\ip2region_cache\`（Windows）
- **文件命名**：`ip2region_v4.xdb` 和 `ip2region_v6.xdb`
- **自动清理**：支持手动清理过期缓存

#### 性能优势

```php
<?php
// 第一次请求：解压压缩文件并缓存（较慢）
$ip2region = new \Ip2Region();
echo $ip2region->simple('61.142.118.231'); // 需要解压压缩文件

// 后续请求：直接使用缓存文件（极快）
$ip2region = new \Ip2Region();
echo $ip2region->simple('8.8.8.8'); // 直接使用缓存
?>
```

#### 缓存管理

```php
<?php
// 清理持久化缓存
$cleared = \Ip2Region::clearPersistentCache();
echo "已清理 {$cleared} 个缓存文件\n";

// 获取缓存统计
$stats = \Ip2Region::getCacheStats();
echo "缓存目录: " . $stats['cache_dir'] . "\n";
echo "缓存文件数: " . $stats['file_count'] . "\n";
?>
```

#### 缓存验证机制

**自动验证策略**：
1. **文件大小检查**：确保缓存文件不小于最小阈值（IPv4: 10MB, IPv6: 100MB）
2. **时间戳对比**：检查缓存文件是否比源压缩文件更新
3. **内容格式验证**：验证文件包含地理位置数据（中国、美国、| 等标识符）
4. **自动更新**：当压缩文件更新时，自动重新生成缓存

**验证优势**：
- 不依赖硬编码的文件大小，适应数据库更新
- 基于文件修改时间，确保缓存与源文件同步
- 内容格式检查，避免使用损坏的缓存文件
- 自动处理压缩文件更新，无需手动干预

#### 生产环境建议

1. **预热缓存**：在应用启动时进行一次查询，预热缓存
2. **监控缓存**：定期检查缓存文件大小和有效性
3. **清理策略**：定期清理过期缓存，避免磁盘空间浪费
4. **自定义路径**：对于高并发环境，建议使用自定义数据库路径
5. **压缩更新**：更新压缩文件后，缓存会自动重新生成

## 性能测试

### 快速性能测试

运行 `composer performance` 进行快速性能测试：

```bash
composer performance
```

**测试结果示例**：
```
测试环境信息:
==================
操作系统: Darwin 25.0.0
PHP版本: 8.1.29
内存限制: 128M
最大执行时间: 0秒
时区: UTC
当前时间: 2025-09-18 02:46:44
系统负载: 4.05, 3.18, 2.77
当前内存使用: 4MB
峰值内存使用: 4MB
磁盘空间: 587.81GB 可用 / 926.35GB 总计
CPU: Apple M4 Pro
CPU核心数: 14

首次加载 vs 缓存命中:
  IPv4: 32.02ms → 0.5ms (提升 98.4%) (new Ip2Region() + simple())
  IPv6: 1174.68ms → 1.03ms (提升 99.9%) (new Ip2Region() + simple())

查询方法性能:
  simple: 0.17ms (ip2region->simple())
  search: 0.01ms (ip2region->search())
  memorySearch: 0.01ms (ip2region->memorySearch())

批量处理性能:
  10个IP: 0.73ms (ip2region->batchSearch())
  10000个IP: 99.67ms (ip2region->batchSearch())
  10000次循环: 54.09ms (10000次 ip2region->simple())

QPS性能:
  10个IP: 13699 QPS
  10000个IP: 100331 QPS
  10000次循环: 184877 QPS

缓存管理性能:
  清理缓存: 0.08ms (Ip2Region::clearCache() + clearPersistentCache())

性能评分: 100/100
性能等级: 优秀 ⭐⭐⭐⭐⭐
```

### 性能测试内容

快速性能测试包含以下项目：

- 首次加载测试（无缓存）
- 缓存命中测试
- 不同查询方法性能对比
- 批量查询测试（10个IP + 10000个IP）
- 循环查询测试（10000次）
- 内存使用测试
- 缓存清理性能测试
- 系统环境信息展示
- 性能评分和等级评定

### 性能特点

#### 测试环境
- **硬件配置**：Apple M4 Pro (14核心)，16GB+ 内存
- **操作系统**：macOS 15.0 (Darwin 25.0.0)
- **PHP版本**：8.1.29
- **磁盘空间**：587GB+ 可用空间

#### 性能指标
1. **首次加载**：需要解压压缩文件，IPv4 约 0.59ms，IPv6 约 0.16ms (`new Ip2Region() + simple()`)
2. **缓存命中**：直接使用缓存文件，IPv4 约 0.03ms，IPv6 约 0.03ms (`new Ip2Region() + simple()`)
3. **性能提升**：IPv4 提升 94.9%，IPv6 提升 81.3%
4. **查询方法**：
   - `simple()`：约 0.02ms (格式化输出)
   - `search()`：约 0.01ms (原始数据)
   - `memorySearch()`：约 0.01ms (数组格式)
5. **批量查询**：
   - 10个IP：约 0.73ms (13699 QPS) (`ip2region->batchSearch()`)
   - 10000个IP：约 99.67ms (100331 QPS) (`ip2region->batchSearch()`)
   - 10000次循环：约 54.09ms (184877 QPS) (10000次 `ip2region->simple()`)
6. **内存使用**：当前 4MB，峰值 4MB
7. **缓存清理**：约 0.08ms (`Ip2Region::clearCache() + clearPersistentCache()`)
8. **性能评分**：100/100 (优秀 ⭐⭐⭐⭐⭐)

#### 最新性能测试结果 (2025-09-25)

**测试环境**：
- 操作系统：Darwin 25.0.0 (macOS)
- PHP版本：8.1.29
- CPU：Apple M4 Pro (14核心)
- 内存：4MB (峰值4MB)

**详细性能数据**：
- **IPv4查询**：平均 0.006ms，QPS 167,972
- **IPv6查询**：平均 0.013ms，QPS 75,819
- **IO统计**：IPv4 IO 3次，IPv6 IO 3次，总IO 6次
- **内存效率**：极低内存占用，仅4MB
- **缓存性能**：首次加载后缓存命中率接近100%

#### 性能等级说明
- **90-100分**：优秀 ⭐⭐⭐⭐⭐ (企业级性能)
- **80-89分**：良好 ⭐⭐⭐⭐ (生产环境推荐)
- **70-79分**：中等 ⭐⭐⭐ (一般应用)
- **60-69分**：及格 ⭐⭐ (基础应用)
- **0-59分**：需要优化 ⭐ (性能不足)

## 故障排除

### 常见问题

#### 1. 数据库文件不存在

**错误信息**：`数据库文件不存在: /path/to/ip2region_v4.xdb`
**解决方案**：

-   **检查文件位置**：确保 IPv4 源数据库文件存在于 `tools/` 目录下
-   **检查文件名**：IPv4 源文件必须严格按照 `ip2region_v4.xdb` 命名
-   **检查文件权限**：确保文件可读
-   **下载 IPv6 数据库**：从官方仓库下载 IPv6 的 `.xdb` 文件到 `tools/` 目录
-   **获取数据源**：
    -   免费版本：从 [ip2region 官方仓库](https://github.com/lionsoul2014/ip2region) 下载
        -   IPv4：[ip2region_v4.xdb](https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v4.xdb) (10.5MB)
        -   IPv6：[ip2region_v6.xdb](https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v6.xdb) (617MB)
    -   商业版本：从 [ip2region 官网](https://www.ip2region.net/) 购买或下载
-   **生成压缩文件**：

    ```bash
    # 方法1：使用 Composer 脚本（仅 IPv4）
    composer compress

    # 方法2：直接使用压缩工具（仅 IPv4）
    php tools/compress_db.php
    
    # ⚠️ 注意：IPv6 数据库不建议压缩，直接使用完整文件性能更佳
    ```

**文件摆放检查**：

```bash
# 检查文件是否存在
ls -la db/ip2region_v*.xdb*

# 应该看到类似输出：
# -rw-r--r-- 1 user staff 11042429 Dec 19 10:00 db/ip2region_v4.xdb.gz
# -rw-r--r-- 1 user staff 617000000 Dec 19 10:00 db/ip2region_v6.xdb.gz
```

#### 2. 内存不足

**错误信息**：`Fatal error: Allowed memory size exhausted`
**解决方案**：

-   增加 PHP 内存限制：`ini_set('memory_limit', '256M');`
-   使用文件缓存策略：`new Ip2Region('file')`
-   定期清理缓存：`Ip2Region::clearCache()`

#### 3. 压缩文件损坏

**错误信息**：`无法解压压缩的IPv4数据库文件`
**解决方案**：

-   检查 `db/` 目录下的压缩文件是否完整
-   重新压缩数据库文件：`php tools/compress_db.php`
-   清理缓存后重试：`Ip2Region::clearCache()`

#### 4. 并发使用问题

**错误信息**：`Too many open files`
**解决方案**：

-   每个进程/线程创建独立的 `Ip2Region` 实例
-   增加系统文件描述符限制
-   使用内存缓存策略：`new Ip2Region('content')`

### 性能优化建议

1. **使用合适的缓存策略**：

    - 单次查询：使用 `file` 策略
    - 频繁查询：使用 `vectorIndex` 策略
    - 高并发：使用 `content` 策略

2. **批量查询优化**：

    - 使用 `batchSearch()` 方法进行批量查询
    - 避免在循环中重复创建实例

3. **内存管理**：

    - 定期清理过期缓存
    - 监控内存使用情况
    - 使用懒加载特性

4. **文件管理**：

    - 定期检查文件完整性
    - 使用 `getCacheStats()` 监控缓存状态
    - 合理选择压缩格式（gzip 推荐）

5. **压缩优化建议**：
    - **gzip 压缩**：压缩率高（60-80%），解压速度快，推荐使用
    - **zip 压缩**：通用性好，兼容性强，适合跨平台使用
    - **无压缩**：处理最快，但文件较大，适合本地使用
    - **压缩格式**：建议使用 gzip，平衡压缩效果和处理速度

## 更新日志

### v3.0.2 (2025-09-18) 🚀

#### 🚀 FPM 环境优化
- **持久化缓存**：自动缓存机制，避免重复解压压缩文件
- **缓存验证**：基于文件大小、时间戳、内容格式的自动验证
- **性能提升**：IPv4 提升 98.4%，IPv6 提升 99.9%
- **自动恢复**：进程重启后自动检测并使用有效缓存

#### 📊 性能测试增强
- **系统信息**：详细的硬件配置和系统环境展示
- **性能评分**：基于多维度指标的综合性能评分系统
- **方法说明**：每个测试都显示具体调用的方法
- **测试覆盖**：首次加载、缓存命中、批量查询、循环测试

#### 📚 文档完善
- **优先级说明**：详细的数据库优先级机制说明
- **自定义配置**：完整的自定义数据库配置指南
- **项目结构**：优化的项目结构说明和目录分类

### v3.0.1 (2025-09-16) 🔧

#### 🔧 自定义数据库配置

-   **自定义路径**：支持指定 IPv4 和 IPv6 数据库文件路径
-   **自动优先级**：自定义数据库 > 持久化缓存 > 压缩文件解压
-   **性能优化**：使用自定义数据库时完全跳过压缩处理，启动更快
-   **灵活配置**：可以混合使用自定义数据库和默认压缩文件
-   **格式支持**：支持标准的 `.xdb` 格式数据库文件

#### 🚀 API 增强

-   **新增方法**：
    -   `setCustomDbPaths($v4Path, $v6Path)` - 设置自定义数据库路径
    -   `isUsingCustomDb()` - 检查是否使用自定义数据库
    -   `getCustomDbInfo()` - 获取自定义数据库信息
-   **增强方法**：
    -   `getDatabaseInfo()` - 现在包含自定义数据库信息
    -   `createSearcher()` - 支持自定义数据库路径参数

#### 🔧 兼容性改进

-   **PHP 5.4+**：完全兼容 PHP 5.4 及以上版本
-   **语法优化**：替换了 PHP 5.5+ 特有的语法特性
-   **错误处理**：增强了自定义数据库文件的错误处理机制

#### 📚 文档完善

-   **使用指南**：添加了详细的自定义数据库配置说明
-   **下载指南**：提供了数据库文件获取的完整指南
-   **示例代码**：包含了丰富的使用示例和最佳实践

### v3.0.0 (2025-09-15) 🚀

#### 🎯 企业级优化

-   重构架构，支持压缩文件自动管理
-   **压缩支持**：支持 gzip/zip 压缩，文件大小减少 60-80%
-   自动缓存机制，避免重复解压文件
-   懒加载设计，IPv4/IPv6 查询器按需创建

#### 🚀 性能提升

-   微秒级查询速度
-   内存使用优化至 <8MB
-   支持三种缓存策略：file、vectorIndex、content

#### 🆕 新增功能

-   批量查询支持：`batchSearch()` 方法
-   性能监控：`getStats()` 和 `getMemoryUsage()` 方法
-   详细 IP 信息：`getIpInfo()` 方法
-   缓存管理：`clearCache()` 和 `clearExpiredCache()` 方法

#### 🛡️ 企业级特性

-   完善的错误处理和异常管理
-   支持多进程/多线程安全使用
-   完全兼容 PHP 5.4+ 版本
-   零依赖，纯 PHP 实现

#### 📱 IPv6 支持

-   完整支持 IPv6 地址查询
-   自动识别 IP 版本
-   自动文件管理优化大文件处理

#### 🔧 开发友好

-   统一 API 设计
-   函数式和面向对象两种使用方式
-   完整的 API 文档和示例
-   Composer 脚本支持

## 许可证

本项目基于 Apache-2.0 许可证开源。

## 🔧 通用查询函数

IP2Region 提供了通用的 `ip2region()` 函数，支持多种查询方法：

### 函数签名

```php
ip2region(string $ip, string $method = 'simple'): string|array|null
```

### 支持的查询方法

| 方法     | 描述             | 返回值                 | 示例                   |
| -------- | ---------------- | ---------------------- | ---------------------- |
| `simple` | 简单查询（默认） | 格式化的地理位置字符串 | `"美国【Level3】"`     |
| `search` | 详细查询         | 管道分隔的详细信息     | `"美国\|0\|0\|Level3"` |
| `binary` | 二进制查询       | 原始二进制数据         | 二进制字符串           |
| `btree`  | B 树查询         | B 树索引查询结果       | 查询结果字符串         |
| `memory` | 内存查询         | 内存中的查询结果       | 查询结果字符串         |

### 使用示例

```php
<?php
require 'vendor/autoload.php';

// 简单查询（默认方法）
echo ip2region('61.142.118.231'); // 输出: 中国广东省中山市【电信】

// 详细查询
echo ip2region('61.142.118.231', 'search'); // 输出: 中国|广东省|中山市|电信

// 内存查询（返回数组）
$result = ip2region('61.142.118.231', 'memory');
print_r($result); // 输出: Array([city_id] => 0, [region] => 中国|广东省|中山市|电信)

// IPv6查询
echo ip2region('2001:4860:4860::8888'); // 输出: 美国加利福尼亚州圣克拉拉【专线用户】

// 异常安全
$result = ip2region('invalid-ip'); // 返回: null
if ($result === null) {
    echo "IP地址无效或查询失败";
}
```

### 特性说明

-   **自动识别**：自动识别 IPv4 和 IPv6 地址
-   **压缩支持**：自动处理压缩数据库文件
-   **自动缓存**：内置缓存机制，提升查询性能
-   **异常安全**：查询失败返回 null，不会抛出异常
-   **静态实例**：使用静态实例，避免重复初始化
-   **IPv6 支持**：⚠️ IPv6 查询需要先下载完整数据库（617MB）

## 📚 相关文档
-   [V3.0 版本文档](https://github.com/zoujingli/ip2region/tree/master) - 完整版本，支持 IPv4 + IPv6
-   [自定义数据库配置说明](CUSTOM_DB_USAGE.md) - 自定义数据库路径配置详细说明
-   [性能测试报告](PERFORMANCE.md) - 详细的性能测试数据和基准测试
-   [贡献指南](CONTRIBUTING.md) - 如何参与项目贡献
-   [官方 ip2region 项目](https://github.com/lionsoul2014/ip2region) - 原始项目

## 贡献

欢迎提交 Issue 和 Pull Request 来改进这个项目。

## 联系方式

如有问题或建议，请通过以下方式联系：

-   **GitHub Issues**：[提交问题或建议](https://github.com/zoujingli/ip2region/issues)
-   **邮箱**：zoujingli@qq.com
-   **作者主页**：[https://thinkadmin.top](https://thinkadmin.top)
