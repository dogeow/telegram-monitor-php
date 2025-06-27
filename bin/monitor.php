#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TelegramMonitor\ConfigManager;
use TelegramMonitor\TelegramMonitor;
use danog\MadelineProto\API;

echo "=== Telegram 消息监听程序 ===\n\n";

// 检查 .env 文件
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "❌ 未找到 .env 文件\n";
    echo "请先运行: php bin/setup.php\n";
    exit(1);
}

try {
    // 加载配置
    $config = new ConfigManager($envFile);
    
    // 验证配置
    $errors = $config->validate();
    if (!empty($errors)) {
        echo "❌ 配置验证失败:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        echo "\n请检查 .env 文件中的配置\n";
        exit(1);
    }
    
    echo "✅ 配置加载成功\n";
    echo "📱 手机号: " . $config->get('telegram.phone') . "\n";
    echo "🔍 监听关键词: " . $config->get('keywords') . "\n";
    echo "📤 目标群组: " . $config->get('target_chats') . "\n\n";
    
    // 检查会话文件
    $sessionFile = 'session.madeline';
    if (!file_exists($sessionFile)) {
        echo "❌ 未找到会话文件\n";
        echo "请先运行: php bin/setup.php 进行登录\n";
        exit(1);
    }
    
    // 创建 MadelineProto 实例
    $settings = $config->getMadelineProtoSettings();
    $MadelineProto = new API($sessionFile, $settings);
    
    echo "🔄 启动监听程序...\n";
    
    // 设置事件处理器
    $MadelineProto->setEventHandler(TelegramMonitor::class, $config->get());
    
    echo "✅ 监听程序已启动！\n";
    echo "💡 程序正在运行，按 Ctrl+C 停止监听\n";
    echo "📝 日志文件: " . $config->get('log_file') . "\n\n";
    
    // 开始监听
    $MadelineProto->loop();
    
} catch (\Exception $e) {
    echo "❌ 启动失败: " . $e->getMessage() . "\n";
    echo "📋 错误详情: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} 