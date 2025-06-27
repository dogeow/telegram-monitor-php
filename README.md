# Telegram 消息监听程序 (PHP版本)

这是一个基于 PHP 和 MadelineProto 的 Telegram 用户消息监听程序，可以监听群组消息并根据关键词自动发送响应。

## 功能特性

- ✅ 以真实用户身份发送消息（非机器人）
- 🔍 关键词监听和匹配
- 📤 自动转发到指定群组
- 📝 详细的日志记录
- ⚙️ 灵活的配置管理
- 🌐 代理支持
- 💻 命令行工具

## 安装步骤

### 1. 克隆项目
```bash
cd telegram-monitor-php
```

### 2. 安装依赖
```bash
composer install
```

### 3. 配置环境变量
```bash
# 复制配置文件
cp env.example .env

# 编辑配置文件
nano .env
```

### 4. 获取 Telegram API 凭证

访问 [my.telegram.org](https://my.telegram.org) 获取：
- `api_id`: 你的 API ID
- `api_hash`: 你的 API Hash

### 5. 配置 .env 文件

```env
# Telegram API 配置
TELEGRAM_API_ID=你的API_ID
TELEGRAM_API_HASH=你的API_HASH
TELEGRAM_PHONE=+86你的手机号

# 监听配置
KEYWORDS=关键词1,关键词2,需求,求购,出售
TARGET_CHAT_ID=-1001234567890
RESPONSE_MESSAGE=感谢您的消息，我们已经收到！

# 日志配置
LOG_LEVEL=INFO
LOG_FILE=logs/telegram.log

# 代理配置（可选）
PROXY_ENABLED=false
PROXY_TYPE=socks5
PROXY_HOST=127.0.0.1
PROXY_PORT=1080
```

## 使用方法

### 1. 初始化设置
```bash
# 登录并获取群组列表
php bin/setup.php
```

第一次运行时会要求输入手机验证码进行登录。

### 2. 启动监听
```bash
# 开始监听消息
php bin/monitor.php

# 或者使用 composer 脚本
composer start
```

### 3. 手动发送消息
```bash
# 发送测试消息
php bin/send.php -1001234567890 "测试消息"
```

## 配置说明

### 关键词配置
在 `.env` 文件中设置 `KEYWORDS`，多个关键词用逗号分隔：
```env
KEYWORDS=关键词1,关键词2,需求,求购,出售
```

### 目标群组配置
在 `.env` 文件中设置 `TARGET_CHAT_ID`，支持多个群组：
```env
TARGET_CHAT_ID=-1001234567890,-1001234567891
```

### 响应消息配置
自定义检测到关键词后的响应消息：
```env
RESPONSE_MESSAGE=感谢您的消息，我们已经收到并会及时处理！
```

## 群组ID获取方法

运行设置脚本后会显示所有可用的群组和频道：
```bash
php bin/setup.php
```

输出示例：
```
📢 测试群组
   ID: -1001234567890
   类型: channel

📢 另一个群组  
   ID: -1001234567891
   类型: chat
```

## 项目结构

```
telegram-monitor-php/
├── src/
│   ├── TelegramMonitor.php    # 主监听类
│   └── ConfigManager.php      # 配置管理类
├── bin/
│   ├── setup.php             # 设置脚本
│   ├── monitor.php           # 监听脚本
│   └── send.php              # 发送脚本
├── config/                   # 配置目录
├── logs/                     # 日志目录
├── composer.json             # 依赖配置
├── env.example              # 环境变量模板
└── README.md                # 说明文档
```

## 工作流程

1. **监听消息**: 程序监听你加入的所有群组消息
2. **关键词匹配**: 检查消息是否包含配置的关键词
3. **信息提取**: 提取发送者、群组、时间等信息
4. **构造响应**: 生成包含原消息信息的响应
5. **发送消息**: 将响应发送到指定的目标群组

## 消息格式

当检测到关键词时，会发送如下格式的消息：

```
🔔 检测到关键词消息

📝 原消息：用户发送的原始消息内容
👤 发送者：张三 (@username)
💬 来源群组：测试群组
⏰ 时间：2024-01-20 15:30:45

---
感谢您的消息，我们已经收到！
```

## 常见问题

### Q: 如何获取群组ID？
A: 运行 `php bin/setup.php` 会显示所有群组的ID。

### Q: 支持哪些代理类型？
A: 支持 socks5、socks4、http 代理。

### Q: 如何修改关键词？
A: 编辑 `.env` 文件中的 `KEYWORDS` 配置。

### Q: 可以监听多个群组吗？
A: 是的，程序会监听你账号加入的所有群组。

### Q: 如何停止监听？
A: 按 `Ctrl+C` 停止程序。

## 注意事项

1. **账号安全**: 请妥善保管 API 凭证和会话文件
2. **频率限制**: 注意 Telegram 的 API 调用频率限制
3. **群组权限**: 确保你的账号有权限在目标群组发送消息
4. **网络环境**: 如需要可配置代理访问

## 依赖库

- [MadelineProto](https://github.com/danog/MadelineProto): Telegram MTProto API 客户端
- [Monolog](https://github.com/Seldaek/monolog): 日志记录库
- [phpdotenv](https://github.com/vlucas/phpdotenv): 环境变量管理
- [Symfony Console](https://symfony.com/doc/current/components/console.html): 命令行工具

## 许可证

MIT License

## 支持

如有问题请提交 Issue 或联系开发者。 