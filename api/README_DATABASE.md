# EasyDo 数据库设置指南

## 数据库结构说明

EasyDo 应用使用以下三个主要数据表：

1. **users 表** - 用户信息
   ```sql
   CREATE TABLE `users` (
       `id` INT AUTO_INCREMENT PRIMARY KEY,
       `username` VARCHAR(50) NOT NULL UNIQUE,
       `password` VARCHAR(255) NOT NULL,
       `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
   ```

2. **tasks 表** - 用户任务
   ```sql
   CREATE TABLE `tasks` (
       `id` INT AUTO_INCREMENT PRIMARY KEY,
       `user_id` INT NOT NULL,
       `text` VARCHAR(255) NOT NULL,
       `color_index` INT DEFAULT 0,
       `completed` BOOLEAN DEFAULT FALSE,
       `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
   ```

3. **settings 表** - 用户设置
   ```sql
   CREATE TABLE `settings` (
       `id` INT AUTO_INCREMENT PRIMARY KEY,
       `user_id` INT NOT NULL,
       `setting_key` VARCHAR(50) NOT NULL,
       `setting_value` TEXT,
       `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       `modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
       UNIQUE KEY `user_setting` (`user_id`, `setting_key`),
       FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
   ```

## 重置和初始化数据库

我们提供了一个自动化脚本来重置和初始化数据库。该脚本将：

1. 删除所有现有表（如果存在）
2. 创建全新的表结构
3. 创建一个测试用户
4. 添加示例任务和设置

### 运行初始化脚本

访问以下 URL 来运行数据库初始化脚本：

```
http://45.207.194.163/api/setup_database.php
```

### 初始测试账户

初始化后将创建以下测试账户：

- **用户名**: test
- **密码**: test123

## 重要说明

1. 此脚本将**删除**所有现有数据。请确保在运行前备份任何重要数据。
2. 所有表都使用 `users` 表作为用户表，不再使用 `user` 表。
3. 表名和字段名与模型代码保持一致，避免外键约束错误。

## 手动数据库设置（如需要）

如果你需要手动创建或修改数据库，请按照上述 SQL 语句进行操作，并确保：

- 所有表使用正确的字段名
- 所有外键正确引用 `users` 表
- 字符集使用 utf8mb4

## 故障排除

如果遇到数据库问题，可以使用以下 URL 来检查数据库状态：

```
http://45.207.194.163/api/debug_db.php
```

这将显示表结构和部分数据，帮助诊断问题。 