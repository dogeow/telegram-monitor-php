#!/bin/bash

echo "=== Telegram 监听程序安装脚本 ==="
echo

# 检查 PHP 版本
echo "🔍 检查 PHP 版本..."
php_version=$(php -v | head -n 1 | cut -d " " -f 2 | cut -c 1-3)
if (( $(echo "$php_version < 8.1" | bc -l) )); then
    echo "❌ 需要 PHP 8.1 或更高版本，当前版本: $php_version"
    exit 1
fi
echo "✅ PHP 版本检查通过: $php_version"

# 检查 Composer
echo "🔍 检查 Composer..."
if ! command -v composer &> /dev/null; then
    echo "❌ 未找到 Composer，请先安装 Composer"
    echo "安装方法: https://getcomposer.org/download/"
    exit 1
fi
echo "✅ Composer 检查通过"

# 安装依赖
echo "📦 安装 PHP 依赖..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ 依赖安装失败"
    exit 1
fi

# 创建必要目录
echo "📁 创建目录..."
mkdir -p logs
mkdir -p cache

# 复制配置文件
echo "⚙️ 设置配置文件..."
if [ ! -f ".env" ]; then
    cp env.example .env
    echo "✅ 已创建 .env 文件"
else
    echo "⚠️ .env 文件已存在，跳过复制"
fi

# 设置权限
echo "🔐 设置文件权限..."
chmod +x bin/*.php
chmod 755 logs
chmod 755 cache

echo
echo "=== 安装完成 ==="
echo "📝 下一步操作："
echo "1. 编辑 .env 文件，填写你的 Telegram API 信息"
echo "2. 运行 'php bin/setup.php' 进行登录设置"
echo "3. 运行 'php bin/monitor.php' 开始监听"
echo
echo "�� 详细说明请查看 README.md" 