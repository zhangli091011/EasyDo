# EasyDo 后端设置指南

本指南介绍如何设置 EasyDo 应用的后端系统。

## 数据库设置

后端使用 MySQL 数据库，连接信息如下:

- 主机: 45.207.194.163
- 用户名: advx
- 密码: adventurex
- 数据库名称: advx

## API 结构

后端 API 采用 RESTful 架构，使用 PHP 实现。主要目录结构:

```
api/
├── config/
│   └── Database.php - 数据库连接配置
├── models/
│   ├── User.php - 用户模型
│   ├── Task.php - 任务模型
│   └── Setting.php - 设置模型
├── auth/
│   ├── login.php - 登录 API
│   └── register.php - 注册 API
├── task/
│   ├── create.php - 创建任务
│   ├── read.php - 读取任务
│   └── delete.php - 删除任务
└── setting/
    ├── update.php - 更新设置
    └── read.php - 读取设置
```

## 部署步骤

1. 确保服务器支持 PHP 7.2+ 和 MySQL 5.7+
2. 将 `api` 目录上传到服务器 web 根目录
3. 确保 PHP 有访问数据库的权限
4. 设置适当的文件权限:
   ```
   chmod -R 755 api
   ```
5. 测试 API 是否能正常访问:
   ```
   curl http://your-domain.com/api/auth/register.php
   ```

## 数据库表结构

系统会自动创建以下表:

### users 表 - 用户信息
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- username (VARCHAR(50), UNIQUE)
- password (VARCHAR(255)) - 使用 BCrypt 加密
- email (VARCHAR(100))
- phone (VARCHAR(20))
- created (TIMESTAMP)

### tasks 表 - 用户任务
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- text (VARCHAR(255))
- color_index (INT)
- completed (BOOLEAN)
- created (TIMESTAMP)
- modified (TIMESTAMP)

### settings 表 - 用户设置
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- setting_key (VARCHAR(50))
- setting_value (TEXT)
- created (TIMESTAMP)
- modified (TIMESTAMP)
- UNIQUE KEY (user_id, setting_key)

## 安全注意事项

1. 确保 PHP 版本最新，定期更新安全补丁
2. 配置数据库用户只有必要的权限
3. 考虑对关键 API 添加 rate limiting 防止滥用
4. 在生产环境中更改 CORS 头设置为特定域名，而非通配符 "*"
5. 考虑使用 HTTPS 加密传输数据

## 故障排除

### 数据库连接失败
- 检查数据库凭据是否正确
- 确认数据库服务器 IP 是否可达
- 查看 PHP 错误日志寻找详细信息

### API 返回 500 错误
- 检查 PHP 错误日志
- 确保 PHP 有写入权限（如需记录日志）
- 检查数据库表是否已正确创建

### 无法创建用户或任务
- 确保数据符合数据库表的约束条件
- 检查 API 请求格式是否正确
- 确认所有必要参数都已提供 