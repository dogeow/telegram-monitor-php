#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TelegramMonitor\ConfigManager;
use danog\MadelineProto\API;
use danog\MadelineProto\Exception;

echo "=== Telegram 监听程序设置 ===\n\n";

// 检查 .env 文件
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    echo "❌ 未找到 .env 文件\n";
    echo "请复制 env.example 为 .env 并填写配置信息\n";
    echo "命令: cp env.example .env\n\n";
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
    
    echo "✅ 配置验证通过\n";
    echo "📱 手机号: " . $config->get('telegram.phone') . "\n";
    echo "🔑 API ID: " . $config->get('telegram.api_id') . "\n\n";
    
    // 创建 MadelineProto 实例
    $settings = $config->getMadelineProtoSettings();
    $MadelineProto = new API('session.madeline', $settings);
    
    echo "🔄 开始登录流程...\n";
    
    // 登录
    $MadelineProto->start();
    
    echo "✅ 登录成功！\n";
    echo "📋 获取对话列表...\n";
    
    // 获取对话列表
    $dialogs = $MadelineProto->messages->getDialogs();
    
    echo "\n=== 可用的群组和频道 ===\n";
    $groupCount = 0;
    
    foreach ($dialogs['chats'] as $chat) {
        if (isset($chat['title'])) {
            $chatId = $chat['id'];
            $title = $chat['title'];
            $type = $chat['_'] ?? 'unknown';
            
            // 处理群组ID格式
            if ($type === 'channel' || $type === 'channelForbidden') {
                $displayId = '-100' . $chatId;
            } elseif ($type === 'chat' || $type === 'chatForbidden') {
                $displayId = '-' . $chatId;
            } else {
                $displayId = $chatId;
            }
            
            echo sprintf("📢 %s\n", $title);
            echo sprintf("   ID: %s\n", $displayId);
            echo sprintf("   类型: %s\n", $type);
            echo "\n";
            
            $groupCount++;
        }
    }
    
    echo "总计找到 $groupCount 个群组/频道\n\n";
    
    echo "=== 设置完成 ===\n";
    echo "1. 复制上面的群组ID到 .env 文件的 TARGET_CHAT_ID 中\n";
    echo "2. 运行 'composer start' 或 'php bin/monitor.php' 开始监听\n";
    echo "3. 在监听的群组中发送包含关键词的消息进行测试\n\n";
    
} catch (Exception $e) {
    echo "❌ 设置失败: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
    exit(1);
} 