#!/bin/bash

echo "=== MadelineProto 依赖修复脚本 ==="
echo

# 检查是否有 composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer 未安装"
    echo "请先安装 Composer: https://getcomposer.org/"
    exit 1
fi

echo "✅ Composer 已安装"

# 检查 PHP 版本
php_version=$(php -r "echo PHP_VERSION;")
echo "PHP 版本: $php_version"

if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);"; then
    echo "❌ PHP 版本过低，需要 8.2 或更高版本"
    exit 1
fi

echo "✅ PHP 版本符合要求"

# 清理旧的依赖
echo
echo "1. 清理旧的依赖..."
rm -rf vendor/
rm -f composer.lock

# 重新安装依赖
echo
echo "2. 重新安装依赖..."
composer install --no-dev --optimize-autoloader

# 重新生成自动加载文件
echo
echo "3. 重新生成自动加载文件..."
composer dump-autoload --optimize

# 检查关键文件
echo
echo "4. 检查关键文件..."
if [ -f "vendor/autoload.php" ]; then
    echo "✅ vendor/autoload.php 存在"
else
    echo "❌ vendor/autoload.php 不存在"
fi

if [ -f "vendor/danog/madelineproto/src/EventHandler.php" ]; then
    echo "✅ EventHandler.php 存在"
else
    echo "❌ EventHandler.php 不存在"
fi

# 运行测试
echo
echo "5. 运行测试..."
php diagnose.php

echo
echo "=== 修复完成 ==="
echo "如果问题仍然存在，请检查："
echo "1. 服务器 PHP 版本是否 >= 8.2"
echo "2. 文件权限是否正确"
echo "3. 是否有足够的内存和磁盘空间" 