<?php
/**
 * 服务器环境诊断脚本
 * 用于诊断 MadelineProto EventHandler 加载问题
 */

echo "=== 服务器环境诊断 ===\n\n";

// 1. PHP 版本检查
echo "1. PHP 版本信息:\n";
echo "   版本: " . PHP_VERSION . "\n";
echo "   最低要求: 8.2\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "   ✅ PHP 版本符合要求\n";
} else {
    echo "   ❌ PHP 版本过低，需要 8.2 或更高版本\n";
}

// 2. 检查必需的扩展
echo "\n2. 检查必需的 PHP 扩展:\n";
$required_extensions = ['json', 'xml', 'dom', 'hash', 'fileinfo', 'filter', 'zlib'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext\n";
    } else {
        echo "   ❌ $ext (缺失)\n";
    }
}

// 3. 检查文件路径
echo "\n3. 检查关键文件路径:\n";
$current_dir = __DIR__;
echo "   当前目录: $current_dir\n";

$autoload_path = $current_dir . '/vendor/autoload.php';
echo "   Autoload: $autoload_path\n";
if (file_exists($autoload_path)) {
    echo "   ✅ autoload.php 存在\n";
} else {
    echo "   ❌ autoload.php 不存在\n";
}

$eventhandler_path = $current_dir . '/vendor/danog/madelineproto/src/EventHandler.php';
echo "   EventHandler: $eventhandler_path\n";
if (file_exists($eventhandler_path)) {
    echo "   ✅ EventHandler.php 存在\n";
} else {
    echo "   ❌ EventHandler.php 不存在\n";
}

// 4. 尝试加载 autoload
echo "\n4. 尝试加载 Composer autoload:\n";
try {
    if (file_exists($autoload_path)) {
        require_once $autoload_path;
        echo "   ✅ autoload.php 加载成功\n";
    } else {
        echo "   ❌ autoload.php 文件不存在\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ autoload.php 加载失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. 检查类是否存在
echo "\n5. 检查关键类:\n";
$classes = [
    'danog\\MadelineProto\\EventHandler',
    'danog\\MadelineProto\\API',
    'TelegramMonitor\\TelegramMonitor'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "   ✅ $class\n";
    } else {
        echo "   ❌ $class (不存在)\n";
    }
}

// 6. 尝试创建简单的继承类
echo "\n6. 测试 EventHandler 继承:\n";
try {
    if (class_exists('danog\\MadelineProto\\EventHandler')) {
        // 创建一个测试类
        eval('
        class TestEventHandler extends danog\\MadelineProto\\EventHandler {
            // 空类用于测试
        }
        ');
        echo "   ✅ EventHandler 继承测试成功\n";
    } else {
        echo "   ❌ EventHandler 类不存在，无法继承\n";
    }
} catch (Exception $e) {
    echo "   ❌ EventHandler 继承测试失败: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   ❌ EventHandler 继承测试出错: " . $e->getMessage() . "\n";
}

// 7. 检查 include_path
echo "\n7. PHP include_path:\n";
echo "   " . get_include_path() . "\n";

// 8. 检查 composer.json
echo "\n8. 检查 composer.json:\n";
$composer_path = $current_dir . '/composer.json';
if (file_exists($composer_path)) {
    echo "   ✅ composer.json 存在\n";
    $composer_data = json_decode(file_get_contents($composer_path), true);
    if (isset($composer_data['require']['danog/madelineproto'])) {
        echo "   ✅ MadelineProto 依赖已配置: " . $composer_data['require']['danog/madelineproto'] . "\n";
    } else {
        echo "   ❌ MadelineProto 依赖未配置\n";
    }
} else {
    echo "   ❌ composer.json 不存在\n";
}

echo "\n=== 诊断完成 ===\n";
echo "\n如果发现问题，请运行以下命令修复:\n";
echo "1. composer install --no-dev --optimize-autoloader\n";
echo "2. composer dump-autoload\n";
echo "3. 确保 PHP 版本 >= 8.2\n";
echo "4. 检查文件权限\n"; 