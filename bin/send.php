#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TelegramMonitor\ConfigManager;

echo "=== Telegram 手动发送消息工具 ===\n\n";

// 检查参数
if ($argc < 3) {
    echo "用法: php bin/send.php <群组ID> <消息内容>\n";
    echo "示例: php bin/send.php -1001234567890 \"测试消息\"\n\n";
    exit(1);
}

$chatId = $argv[1];
$message = $argv[2];

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
        exit(1);
    }
    
    // 检查会话文件
    $sessionFile = 'session.madeline';
    if (!file_exists($sessionFile)) {
        echo "❌ 未找到会话文件\n";
        echo "请先运行: php bin/setup.php 进行登录\n";
        exit(1);
    }
    
    // 创建 MadelineProto 实例
    $settings = $config->getMadelineProtoSettings();
    $MadelineProto = new \danog\MadelineProto\API($sessionFile, $settings);
    
    echo "🔄 发送消息到: $chatId\n";
    echo "📝 消息内容: $message\n\n";
    
    // 发送消息
    $result = $MadelineProto->messages->sendMessage([
        'peer' => $chatId,
        'message' => $message
    ]);
    
    echo "✅ 消息发送成功！\n";
    echo "📋 消息ID: " . $result['id'] . "\n";
    
} catch (\Exception $e) {
    echo "❌ 发送失败: " . $e->getMessage() . "\n";
    exit(1);
} 