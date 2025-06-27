<?php

namespace TelegramMonitor;

use Dotenv\Dotenv;

class ConfigManager
{
    private array $config = [];

    public function __construct(?string $envPath = null)
    {
        $this->loadEnvironmentVariables($envPath);
        $this->buildConfig();
    }

    private function loadEnvironmentVariables(?string $envPath = null): void
    {
        if ($envPath && file_exists($envPath)) {
            $dotenv = Dotenv::createImmutable(dirname($envPath), basename($envPath));
            $dotenv->load();
        }
    }

    private function buildConfig(): void
    {
        $this->config = [
            'telegram' => [
                'api_id' => (int) ($_ENV['TELEGRAM_API_ID'] ?? 0),
                'api_hash' => $_ENV['TELEGRAM_API_HASH'] ?? '',
                'phone' => $_ENV['TELEGRAM_PHONE'] ?? '',
            ],
            'keywords' => $_ENV['KEYWORDS'] ?? 'test,关键词',
            'target_chats' => $_ENV['TARGET_CHAT_ID'] ?? '',
            'response_message' => $_ENV['RESPONSE_MESSAGE'] ?? '感谢您的消息！',
            'log_file' => $_ENV['LOG_FILE'] ?? 'logs/telegram.log',
            'log_level' => $_ENV['LOG_LEVEL'] ?? 'INFO',
            'proxy' => [
                'enabled' => filter_var($_ENV['PROXY_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'type' => $_ENV['PROXY_TYPE'] ?? 'socks5',
                'host' => $_ENV['PROXY_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['PROXY_PORT'] ?? 7890),
            ]
        ];
    }

    public function get(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->getNestedValue($this->config, $key);
    }

    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->config['telegram']['api_id'])) {
            $errors[] = 'TELEGRAM_API_ID 未设置或无效';
        }

        if (empty($this->config['telegram']['api_hash'])) {
            $errors[] = 'TELEGRAM_API_HASH 未设置';
        }

        if (empty($this->config['telegram']['phone'])) {
            $errors[] = 'TELEGRAM_PHONE 未设置';
        }

        if (empty($this->config['target_chats'])) {
            $errors[] = 'TARGET_CHAT_ID 未设置';
        }

        return $errors;
    }

    public function getMadelineProtoSettings()
    {
        $settings = new \danog\MadelineProto\Settings();
        
        // 设置应用信息
        $settings->getAppInfo()
            ->setApiId($this->config['telegram']['api_id'])
            ->setApiHash($this->config['telegram']['api_hash']);

        // 设置日志
        $settings->getLogger()
            ->setType(\danog\MadelineProto\Logger::ECHO_LOGGER)
            ->setLevel(\danog\MadelineProto\Logger::VERBOSE);

        // 代理设置
        if ($this->config['proxy']['enabled']) {
            // 设置系统代理环境变量
            putenv('http_proxy=socks5://' . $this->config['proxy']['host'] . ':' . $this->config['proxy']['port']);
            putenv('https_proxy=socks5://' . $this->config['proxy']['host'] . ':' . $this->config['proxy']['port']);
        }

        return $settings;
    }
} 