<?php

namespace TelegramMonitor;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// 检查 EventHandler 类是否存在
if (!class_exists('danog\MadelineProto\EventHandler')) {
    throw new \Exception(
        "MadelineProto EventHandler 类未找到。请检查：\n" .
        "1. 是否正确安装了 MadelineProto (composer install)\n" .
        "2. PHP 版本是否 >= 8.2\n" .
        "3. 自动加载是否正确配置\n" .
        "4. 运行 'php diagnose.php' 进行详细诊断"
    );
}

use danog\MadelineProto\EventHandler;

class TelegramMonitor extends EventHandler
{
    private array $keywords;
    private array $targetChats;
    private string $responseMessage;
    private MonologLogger $logger;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->keywords = explode(',', $config['keywords']);
        $this->targetChats = explode(',', $config['target_chats']);
        $this->responseMessage = $config['response_message'];
        
        $this->setupLogger();
        
        parent::__construct();
    }

    private function setupLogger(): void
    {
        $this->logger = new MonologLogger('TelegramMonitor');
        
        // 控制台输出
        $this->logger->pushHandler(new StreamHandler('php://stdout', MonologLogger::INFO));
        
        // 文件日志
        if (!empty($this->config['log_file'])) {
            $this->logger->pushHandler(
                new RotatingFileHandler($this->config['log_file'], 0, MonologLogger::DEBUG)
            );
        }
    }

    /**
     * 处理新消息事件
     */
    public function onUpdateNewMessage(array $update): void
    {
        $message = $update['message'] ?? null;
        
        if (!$message || !isset($message['message'])) {
            return;
        }

        $messageText = $message['message'];
        $chatId = $this->getChatId($message);
        $senderId = $this->getSenderId($message);

        $this->logger->info("收到消息", [
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'text' => $messageText
        ]);

        // 检查是否包含关键词
        if ($this->containsKeywords($messageText)) {
            $this->handleKeywordMessage($message, $messageText, $chatId, $senderId);
        }
    }

    /**
     * 处理包含关键词的消息
     */
    private function handleKeywordMessage(array $message, string $messageText, int $chatId, int $senderId): void
    {
        $this->logger->info("检测到关键词消息", [
            'text' => $messageText,
            'chat_id' => $chatId,
            'sender_id' => $senderId
        ]);

        // 获取发送者信息
        $senderInfo = $this->getSenderInfo($senderId);
        $chatInfo = $this->getChatInfo($chatId);

        // 构造响应消息
        $responseText = $this->buildResponseMessage($messageText, $senderInfo, $chatInfo);

        // 发送到目标群组
        $this->sendToTargetChats($responseText);

        // 可选：回复原消息
        // $this->replyToMessage($message, "消息已记录");
    }

    /**
     * 检查消息是否包含关键词
     */
    private function containsKeywords(string $text): bool
    {
        $text = strtolower($text);
        
        foreach ($this->keywords as $keyword) {
            if (strpos($text, strtolower(trim($keyword))) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 发送消息到目标群组
     */
    private function sendToTargetChats(string $message): void
    {
        foreach ($this->targetChats as $chatId) {
            $chatId = trim($chatId);
            if (empty($chatId)) continue;

            try {
                $this->messages->sendMessage([
                    'peer' => $chatId,
                    'message' => $message
                ]);

                $this->logger->info("消息已发送到目标群组", ['chat_id' => $chatId]);
            } catch (\Exception $e) {
                $this->logger->error("发送消息失败", [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 构造响应消息
     */
    private function buildResponseMessage(string $originalText, array $senderInfo, array $chatInfo): string
    {
        $timestamp = date('Y-m-d H:i:s');
        
        return "🔔 检测到关键词消息\n\n" .
               "📝 原消息：{$originalText}\n" .
               "👤 发送者：{$senderInfo['name']} (@{$senderInfo['username']})\n" .
               "💬 来源群组：{$chatInfo['title']}\n" .
               "⏰ 时间：{$timestamp}\n\n" .
               "---\n" .
               $this->responseMessage;
    }

    /**
     * 获取聊天ID
     */
    private function getChatId(array $message): int
    {
        if (isset($message['peer_id']['channel_id'])) {
            return -100 . $message['peer_id']['channel_id'];
        }
        
        if (isset($message['peer_id']['chat_id'])) {
            return -$message['peer_id']['chat_id'];
        }
        
        return $message['peer_id']['user_id'] ?? 0;
    }

    /**
     * 获取发送者ID
     */
    private function getSenderId(array $message): int
    {
        return $message['from_id']['user_id'] ?? 0;
    }

    /**
     * 获取发送者信息
     */
    private function getSenderInfo(int $userId): array
    {
        try {
            $user = $this->getPwrChat($userId);
            return [
                'name' => $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
                'username' => $user['username'] ?? 'None'
            ];
        } catch (\Exception $e) {
            $this->logger->warning("获取用户信息失败", ['user_id' => $userId, 'error' => $e->getMessage()]);
            return ['name' => 'Unknown', 'username' => 'None'];
        }
    }

    /**
     * 获取聊天信息
     */
    private function getChatInfo(int $chatId): array
    {
        try {
            $chat = $this->getPwrChat($chatId);
            return [
                'title' => $chat['title'] ?? 'Private Chat',
                'type' => $chat['type'] ?? 'unknown'
            ];
        } catch (\Exception $e) {
            $this->logger->warning("获取聊天信息失败", ['chat_id' => $chatId, 'error' => $e->getMessage()]);
            return ['title' => 'Unknown Chat', 'type' => 'unknown'];
        }
    }

    /**
     * 回复消息
     */
    private function replyToMessage(array $message, string $replyText): void
    {
        try {
            $this->messages->sendMessage([
                'peer' => $this->getChatId($message),
                'message' => $replyText,
                'reply_to_msg_id' => $message['id']
            ]);
        } catch (\Exception $e) {
            $this->logger->error("回复消息失败", ['error' => $e->getMessage()]);
        }
    }

    /**
     * 手动发送消息到指定聊天
     */
    public function sendCustomMessage(string $chatId, string $message): void
    {
        try {
            $this->messages->sendMessage([
                'peer' => $chatId,
                'message' => $message
            ]);
            
            $this->logger->info("自定义消息已发送", ['chat_id' => $chatId]);
        } catch (\Exception $e) {
            $this->logger->error("发送自定义消息失败", [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取所有对话列表
     */
    public function listDialogs(): array
    {
        try {
            $dialogs = $this->messages->getDialogs();
            $result = [];
            
            foreach ($dialogs['chats'] as $chat) {
                $result[] = [
                    'id' => $chat['id'],
                    'title' => $chat['title'] ?? $chat['first_name'] ?? 'Unknown',
                    'type' => $chat['_'] ?? 'unknown'
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("获取对话列表失败", ['error' => $e->getMessage()]);
            return [];
        }
    }
} 