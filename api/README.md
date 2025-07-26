# EasyDo API 文档

## 基础信息

- 基础URL: `/api`
- 所有API返回JSON格式数据
- 大多数API需要用户认证（通过用户ID）

## 用户认证 API

### 注册

- **URL:** `/auth/register.php`
- **方法:** `POST`
- **请求体:**
  ```json
  {
    "username": "用户名",
    "password": "密码",
    "email": "邮箱(可选)",
    "phone": "手机号(可选)"
  }
  ```
- **成功响应:**
  ```json
  {
    "message": "用户注册成功"
  }
  ```
- **错误响应:**
  ```json
  {
    "message": "用户名已存在"
  }
  ```

### 登录

- **URL:** `/auth/login.php`
- **方法:** `POST`
- **请求体:**
  ```json
  {
    "username": "用户名",
    "password": "密码"
  }
  ```
- **成功响应:**
  ```json
  {
    "message": "登录成功",
    "user": {
      "id": 1,
      "username": "用户名",
      "email": "邮箱",
      "phone": "手机号"
    },
    "token": "认证令牌"
  }
  ```
- **错误响应:**
  ```json
  {
    "message": "用户名或密码错误"
  }
  ```

## 任务 API

### 创建任务

- **URL:** `/task/create.php`
- **方法:** `POST`
- **请求体:**
  ```json
  {
    "user_id": 1,
    "text": "任务内容",
    "color_index": 0
  }
  ```
- **成功响应:**
  ```json
  {
    "message": "任务已创建",
    "id": 1
  }
  ```

### 获取任务列表

- **URL:** `/task/read.php?user_id=1`
- **方法:** `GET`
- **成功响应:**
  ```json
  {
    "records": [
      {
        "id": 1,
        "user_id": 1,
        "text": "任务内容",
        "color_index": 0,
        "completed": false,
        "created": "2023-01-01 00:00:00",
        "modified": "2023-01-01 00:00:00"
      }
    ]
  }
  ```

### 删除任务

- **URL:** `/task/delete.php`
- **方法:** `DELETE`
- **请求体:**
  ```json
  {
    "id": 1,
    "user_id": 1
  }
  ```
- **成功响应:**
  ```json
  {
    "message": "任务已删除"
  }
  ```

## 设置 API

### 更新设置

- **URL:** `/setting/update.php`
- **方法:** `POST`
- **请求体:**
  ```json
  {
    "user_id": 1,
    "setting_key": "difficulty_mode",
    "setting_value": "0"
  }
  ```
- **成功响应:**
  ```json
  {
    "message": "设置已更新"
  }
  ```

### 获取设置

- **URL:** `/setting/read.php?user_id=1`
- **方法:** `GET`
- **成功响应:**
  ```json
  {
    "records": [
      {
        "id": 1,
        "user_id": 1,
        "setting_key": "difficulty_mode",
        "setting_value": "0",
        "created": "2023-01-01 00:00:00",
        "modified": "2023-01-01 00:00:00"
      }
    ]
  }
  ```

### 获取特定设置

- **URL:** `/setting/read.php?user_id=1&setting_key=difficulty_mode`
- **方法:** `GET`
- **成功响应:**
  ```json
  {
    "id": 1,
    "user_id": 1,
    "setting_key": "difficulty_mode",
    "setting_value": "0",
    "created": "2023-01-01 00:00:00",
    "modified": "2023-01-01 00:00:00"
  }
  ```

## 数据库结构

本API使用MySQL数据库，包含以下表:

1. **users** - 用户信息
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - username (VARCHAR(50), UNIQUE)
   - password (VARCHAR(255))
   - email (VARCHAR(100))
   - phone (VARCHAR(20))
   - created (TIMESTAMP)

2. **tasks** - 用户任务
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - user_id (INT, FOREIGN KEY)
   - text (VARCHAR(255))
   - color_index (INT)
   - completed (BOOLEAN)
   - created (TIMESTAMP)
   - modified (TIMESTAMP)

3. **settings** - 用户设置
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - user_id (INT, FOREIGN KEY)
   - setting_key (VARCHAR(50))
   - setting_value (TEXT)
   - created (TIMESTAMP)
   - modified (TIMESTAMP)
   - UNIQUE KEY (user_id, setting_key) 