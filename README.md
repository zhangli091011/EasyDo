# EasyDo 登录注册界面

基于 Vue 3 实现的 EasyDo 应用的登录和注册页面。

## 功能特点

- 登录和注册切换
- 密码显示/隐藏切换
- 表单验证
- 社交媒体登录选项（微信、QQ、Apple）
- 响应式布局设计
- 使用 Tailwind CSS 进行样式设计
- Font Awesome 图标集成

## 项目设置

### 安装依赖
```
npm install
```

### 开发环境编译和热重载
```
npm run serve
```

### 生产环境编译和压缩
```
npm run build
```

### 代码风格检查和修复
```
npm run lint
```

## 项目结构

- `src/views/Auth.vue` - 登录和注册页面
- `src/views/Home.vue` - 成功登录后的主页
- `src/router/index.js` - 路由配置
- `src/App.vue` - 根组件

## 后续开发计划

1. 集成后端 API 进行实际的用户认证
2. 添加忘记密码功能
3. 实现社交媒体登录功能
4. 增加用户资料设置页面 