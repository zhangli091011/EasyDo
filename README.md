# EasyDo 项目部署文档

## 1. 系统要求

### 前端要求
- Node.js 14.x 或更高版本
- npm 6.x 或更高版本
- Vue.js 3.x

### 后端要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache 或 Nginx web服务器
- PHP扩展：PDO, PDO_MySQL, JSON

## 2. 前端部署（Vue应用）

### 2.1 构建生产版本
```bash
# 克隆代码库（如果尚未克隆）
git clone <repository-url>
cd vue-login-register

# 安装依赖
npm install

# 构建生产版本
npm run build
```

构建完成后，`dist` 目录中会包含所有前端静态文件。

### 2.2 部署到Web服务器

#### 使用Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/vue-login-register/dist;
    index index.html;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # 配置API请求代理（如有需要）
    location /api/ {
        proxy_pass http://your-backend-server/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

#### 使用Apache
创建或修改`.htaccess`文件：
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

## 3. 后端部署（PHP API）

### 3.1 配置数据库连接

编辑`api/config/Database.php`文件，设置数据库连接参数：

```php
<?php
class Database {
    private $host = "localhost";      // 修改为您的数据库主机
    private $db_name = "easydo_db";   // 修改为您的数据库名
    private $username = "username";   // 修改为您的数据库用户名
    private $password = "password";   // 修改为您的数据库密码
    private $conn;
    
    public function getConnection() {
        // 现有代码...
    }
}
?>
```

### 3.2 配置CORS（跨域资源共享）

确保`api/config/cors.php`文件中的设置符合您的生产环境：

```php
<?php
// 允许来自特定域名的请求
header("Access-Control-Allow-Origin: https://yourdomain.com");

// 其他CORS设置...
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
?>
```

### 3.3 API部署到Web服务器

将`api`目录上传到您的web服务器，确保PHP能够正确执行。

#### 使用Apache
创建或修改`.htaccess`文件：
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>
```

#### 使用Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /api/index.php?$args;
    # PHP设置
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    }
}
```

## 4. 数据库设置

### 4.1 创建数据库
```sql
CREATE DATABASE easydo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4.2 初始化数据库
数据表会由应用程序自动创建，但您也可以手动运行初始化脚本：

```bash
# 设置数据库表结构
php api/setup_database.php
```

或直接访问：
```
https://yourdomain.com/api/setup_database.php
```

## 5. 环境配置

### 5.1 前端环境变量
创建`.env.production`文件配置生产环境变量：

```
VUE_APP_API_URL=https://yourdomain.com/api
```

### 5.2 后端环境变量
如果使用Coze API等外部服务，设置相应的环境变量：

```bash
# Apache - 在.htaccess文件中
SetEnv COZE_API_TOKEN your_token_here

# Nginx - 在server配置中
fastcgi_param COZE_API_TOKEN "your_token_here";
```

## 6. 安全设置

### 6.1 禁用PHP错误显示
在生产环境中，修改php.ini或使用.htaccess：
```
php_flag display_errors off
php_flag display_startup_errors off
```

### 6.2 设置SSL证书
强烈建议为您的网站配置SSL证书，可以使用Let's Encrypt免费获取。

## 7. 维护与更新

### 7.1 前端更新
```bash
# 拉取最新代码
git pull

# 安装可能的新依赖
npm install

# 重新构建
npm run build
```

### 7.2 后端更新
```bash
# 拉取最新代码
git pull

# 如有数据库更改，运行迁移脚本
php api/migrate_to_users.php
```

## 8. 故障排除

### 8.1 API连接问题
- 检查CORS设置是否正确
- 确认API URL配置无误
- 检查网络请求中是否有错误(通过浏览器开发者工具)

### 8.2 数据库连接问题
- 验证数据库连接参数
- 确认数据库用户有足够权限
- 检查PHP错误日志

### 8.3 登录/注册问题
- 确认数据库users表创建成功
- 验证API登录/注册端点是否正常工作

### 8.4 任务推荐功能问题
- 检查Coze API令牌是否正确设置
- 查看PHP错误日志获取详细信息

## 9. 联系支持

如有任何部署或技术问题，请联系：
- 技术支持邮箱：support@adventurex.space
- 项目GitHub仓库：[https://github.com/yourusername/easydo](https://github.com/zhangli091011/EasyDo)
