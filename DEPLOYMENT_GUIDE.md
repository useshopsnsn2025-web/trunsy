# TURNSY 生产环境部署指南

---

## 一、服务器环境要求

| 项目 | 最低要求 | 推荐 |
|------|---------|------|
| 操作系统 | CentOS 8+ / Ubuntu 20.04+ | CentOS 8 Stream |
| CPU | 2 核 | 4 核 |
| 内存 | 2 GB | 4 GB |
| 硬盘 | 40 GB | 80 GB SSD |
| 带宽 | 3 Mbps | 5 Mbps |
| PHP | 8.0+ | 8.0 |
| MySQL | 8.0+ | 8.0 |
| Nginx | 1.18+ | 最新稳定版 |
| Node.js | 18+ | 18.x（仅构建用） |
| Composer | 2.x | 2.9+ |

### PHP 必须扩展

- fileinfo
- mbstring
- pdo_mysql
- json
- openssl
- curl
- gd
- zip

### PHP 必须解除禁用的函数

- `putenv`
- `proc_open`
- `passthru`

---

## 二、域名规划

| 域名 | 用途 | 根目录 |
|------|------|--------|
| `www.turnsysg.com` | H5 前端 + API 后端 | H5: `/www/wwwroot/turnsy/bbo-app-h5` <br> API: `/www/wwwroot/turnsy/bbo-server/public` |
| `www.turnsysg.shop` | Admin 管理面板 | `/www/wwwroot/turnsy/bbo-admin/dist` |

---

## 三、部署步骤

### 3.1 安装宝塔面板

```bash
# CentOS
yum install -y wget && wget -O install.sh https://download.bt.cn/install/install_6.0.sh && sh install.sh ed8484bec
```

安装完成后在宝塔面板安装：
- Nginx（最新版）
- MySQL 8.0
- PHP 8.0

### 3.2 PHP 配置

**安装扩展：**
宝塔面板 → 软件商店 → PHP 8.0 → 设置 → 安装扩展：
- `fileinfo`（必装）

**解除禁用函数：**
宝塔面板 → PHP 8.0 → 设置 → 禁用函数 → 删除以下函数：
- `putenv`
- `proc_open`
- `passthru`

或命令行操作：
```bash
sed -i 's/putenv,//g' /www/server/php/80/etc/php.ini
sed -i 's/proc_open,//g' /www/server/php/80/etc/php.ini
sed -i 's/passthru,//g' /www/server/php/80/etc/php.ini
sed -i 's/putenv,//g' /www/server/php/80/etc/php-cli.ini
sed -i 's/proc_open,//g' /www/server/php/80/etc/php-cli.ini
sed -i 's/passthru,//g' /www/server/php/80/etc/php-cli.ini
/etc/init.d/php-fpm-80 restart
```

### 3.3 安装 Composer

```bash
/www/server/php/80/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
/www/server/php/80/bin/php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
```

### 3.4 安装 Node.js 18（构建 Admin 和 H5 用）

```bash
curl -fsSL https://rpm.nodesource.com/setup_18.x | bash -
yum install -y nodejs
# 验证
node -v
npm -v
```

### 3.5 克隆项目

```bash
cd /www/wwwroot/
git clone https://github.com/useshopsnsn2025-web/trunsy.git turnsy
```

### 3.6 后端配置

```bash
cd /www/wwwroot/turnsy/bbo-server

# 安装 PHP 依赖
/www/server/php/80/bin/php /usr/local/bin/composer install

# 复制环境配置
cp .env.example .env

# 编辑 .env（填入数据库信息和 JWT Secret）
vi .env
```

**.env 关键配置：**
```ini
APP_DEBUG = false

[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = 你的数据库名
USERNAME = 你的数据库用户名
PASSWORD = 你的数据库密码
HOSTPORT = 3306
CHARSET = utf8mb4
DEBUG = false

[JWT]
SECRET = 用 openssl rand -hex 32 生成的随机值
```

生成 JWT Secret：
```bash
openssl rand -hex 32
```

**创建目录和权限：**
```bash
mkdir -p runtime/cache runtime/log runtime/export public/storage
chmod -R 777 runtime public/storage
```

**修复 ThinkPHP 服务发现：**

如果 `composer install` 时 `service:discover` 报错 `Call to undefined method think\Validate::maker()`，执行：

```bash
cat > /www/wwwroot/turnsy/bbo-server/vendor/topthink/framework/src/think/service/ValidateService.php << 'EOF'
<?php
declare (strict_types = 1);

namespace think\service;

use think\Service;
use think\Validate;

class ValidateService extends Service
{
    public function boot()
    {
        // Validate::maker not available in think-validate v2
    }
}
EOF
```

确认 `runtime/services.php` 存在：
```bash
cat /www/wwwroot/turnsy/bbo-server/runtime/services.php
```

如果不存在，手动创建：
```bash
cat > /www/wwwroot/turnsy/bbo-server/runtime/services.php << 'EOF'
<?php
return array (
  0 => 'think\app\Service',
);
EOF
```

**修复 open_basedir：**

```bash
# 解除文件锁定
chattr -i /www/wwwroot/turnsy/bbo-server/public/.user.ini

# 修改为允许整个项目目录
echo 'open_basedir=/www/wwwroot/turnsy/bbo-server/:/tmp/' > /www/wwwroot/turnsy/bbo-server/public/.user.ini

# 重启 PHP
/etc/init.d/php-fpm-80 restart
```

### 3.7 创建数据库并导入

**宝塔面板 → 数据库 → 添加数据库**

记住数据库名、用户名、密码，填入 `.env` 文件。

**导入数据库：**

本地导出：
```bash
# Windows（在 MySQL bin 目录）
.\mysqldump -u bbo -p123456 bbo > d:\bbo_dump.sql
```

上传 `bbo_dump.sql` 到服务器 `/www/wwwroot/turnsy/`，然后导入：
```bash
mysql -u 数据库用户名 -p 数据库名 < /www/wwwroot/turnsy/bbo_dump.sql
```

验证：
```bash
mysql -u 数据库用户名 -p 数据库名 -e "SHOW TABLES;" | head -20
```

### 3.8 构建 Admin 前端

```bash
cd /www/wwwroot/turnsy/bbo-admin
npm install
npm run build
```

构建完成后 `dist/` 目录即为 Admin 站点文件。

### 3.9 构建 H5 前端

**方式一：服务器构建（需要 Node.js）**
```bash
cd /www/wwwroot/turnsy/bbo-app
npm install
npx uni build -p h5
mkdir -p /www/wwwroot/turnsy/bbo-app-h5
cp -r dist/build/h5/* /www/wwwroot/turnsy/bbo-app-h5/
```

**方式二：本地构建后上传（推荐）**

在本地 HBuilderX 中：发行 → 网站-H5手机版 → 网站域名填 `www.turnsysg.com` → 发行

构建完成后将 `dist/build/h5/` 目录打包上传到服务器 `/www/wwwroot/turnsy/bbo-app-h5/`。

### 3.10 上传 Storage 文件

本地的图片存储目录需要上传到服务器：

```bash
# 本地打包
cd d:\phpstudy_pro\WWW\bbo\bbo-server\public
# 压缩 storage 目录上传到服务器
```

通过宝塔文件管理上传并解压到 `/www/wwwroot/turnsy/bbo-server/public/storage/`。

---

## 四、Nginx 配置

### 4.1 创建站点

在宝塔面板创建两个站点：
1. `www.turnsysg.com` → 根目录 `/www/wwwroot/turnsy/bbo-server/public` → PHP 8.0
2. `www.turnsysg.shop` → 根目录 `/www/wwwroot/turnsy/bbo-admin/dist` → 纯静态

### 4.2 申请 SSL 证书

宝塔面板 → 网站 → 每个站点 → SSL → Let's Encrypt → 申请（文件验证或 DNS 验证）

**注意：** 如果文件验证提示"配置文件被修改不支持"，需要先恢复标准配置（确保有 `#error_page 404/404.html;` 标识），申请完再改回来。

### 4.3 www.turnsysg.com 配置（H5 + API）

```nginx
server {
    listen 80;
    listen 443 ssl;
    http2 on;
    server_name www.turnsysg.com turnsysg.com;
    index index.html index.php;

    #error_page 404/404.html;
    # SSL 配置（宝塔自动生成）
    ssl_certificate    /www/server/panel/vhost/cert/www.turnsysg.com/fullchain.pem;
    ssl_certificate_key    /www/server/panel/vhost/cert/www.turnsysg.com/privkey.pem;
    ssl_protocols TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000";

    # 强制 HTTPS
    #HTTP_TO_HTTPS_START
    set $isRedcert 1;
    if ($server_port != 443) {
        set $isRedcert 2;
    }
    if ( $uri ~ /\.well-known/ ) {
        set $isRedcert 1;
    }
    if ($isRedcert != 1) {
        rewrite ^(/.*)$ https://$host$1 permanent;
    }
    #HTTP_TO_HTTPS_END
    error_page 497  https://$host$request_uri;

    # API 后端（/api 和 /admin 路径）
    location ~ ^/(api|admin)/ {
        root /www/wwwroot/turnsy/bbo-server/public;
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
        }
    }

    # PHP 处理
    location ~ \.php(.*)$ {
        root /www/wwwroot/turnsy/bbo-server/public;
        fastcgi_pass unix:/tmp/php-cgi-80.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $1;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # 上传文件目录
    location /storage {
        alias /www/wwwroot/turnsy/bbo-server/public/storage;
        expires 30d;
    }

    # H5 前端（默认路由）
    location / {
        root /www/wwwroot/turnsy/bbo-app-h5;
        try_files $uri $uri/ /index.html;
    }

    # 禁止访问敏感文件
    location ~ /\.(env|git|htaccess|user.ini) {
        deny all;
    }

    location ~ \.(log|sql)$ {
        deny all;
    }

    # 静态资源缓存（先查 H5 目录，再查后端 storage）
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|webp)$ {
        root /www/wwwroot/turnsy/bbo-app-h5;
        try_files $uri @backend_storage;
        expires 30d;
        access_log off;
    }

    location @backend_storage {
        root /www/wwwroot/turnsy/bbo-server/public;
        expires 30d;
    }

    access_log /www/wwwlogs/turnsysg.com.log;
    error_log /www/wwwlogs/turnsysg.com.error.log;
}
```

### 4.4 www.turnsysg.shop 配置（Admin 管理端）

```nginx
server {
    listen 80;
    listen 443 ssl;
    http2 on;
    server_name www.turnsysg.shop turnsysg.shop;
    root /www/wwwroot/turnsy/bbo-admin/dist;
    index index.html;

    #error_page 404/404.html;
    # SSL 配置（宝塔自动生成）
    ssl_certificate    /www/server/panel/vhost/cert/www.turnsysg.shop/fullchain.pem;
    ssl_certificate_key    /www/server/panel/vhost/cert/www.turnsysg.shop/privkey.pem;
    ssl_protocols TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000";

    # 强制 HTTPS
    #HTTP_TO_HTTPS_START
    set $isRedcert 1;
    if ($server_port != 443) {
        set $isRedcert 2;
    }
    if ( $uri ~ /\.well-known/ ) {
        set $isRedcert 1;
    }
    if ($isRedcert != 1) {
        rewrite ^(/.*)$ https://$host$1 permanent;
    }
    #HTTP_TO_HTTPS_END
    error_page 497  https://$host$request_uri;

    # Vue Router history 模式
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Admin API 反向代理
    location /admin {
        proxy_pass https://www.turnsysg.com;
        proxy_set_header Host www.turnsysg.com;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
    }

    # 图片存储代理
    location /storage {
        proxy_pass https://www.turnsysg.com;
        proxy_set_header Host www.turnsysg.com;
        expires 30d;
    }

    access_log /www/wwwlogs/turnsysg.shop.log;
    error_log /www/wwwlogs/turnsysg.shop.error.log;
}
```

---

## 五、Google OAuth 配置

Google Cloud Console → API 和服务 → 凭据 → OAuth 2.0 客户端 ID：

| 配置项 | 值 |
|--------|-----|
| 已获授权的 JavaScript 来源 | `https://www.turnsysg.com` |
| 已获授权的重定向 URI | `https://www.turnsysg.com` |

在 admin 后台 → 系统设置 中配置 Google OAuth Client ID 和 Client Secret。

---

## 六、验证清单

部署完成后逐项验证：

```bash
# 1. API 是否正常
curl https://www.turnsysg.com/api/system/languages

# 2. H5 首页
curl -I https://www.turnsysg.com

# 3. Admin 首页
curl -I https://www.turnsysg.shop

# 4. Admin API 代理
curl https://www.turnsysg.shop/admin/auth/login

# 5. 图片是否正常
curl -I https://www.turnsysg.com/storage/common/
```

浏览器访问验证：
- [ ] `https://www.turnsysg.com` — H5 首页正常加载
- [ ] `https://www.turnsysg.com` — 图片正常显示
- [ ] `https://www.turnsysg.shop` — Admin 登录页正常
- [ ] `https://www.turnsysg.shop` — 能正常登录
- [ ] Google OAuth 登录正常（如已配置）

---

## 七、日常维护

### 7.1 更新代码

```bash
cd /www/wwwroot/turnsy
git pull origin main

# 后端更新
cd bbo-server
/www/server/php/80/bin/php /usr/local/bin/composer install
rm -rf runtime/cache/*

# Admin 前端重新构建
cd ../bbo-admin
npm install
npm run build

# H5 前端需要本地重新构建后上传
```

### 7.2 清除缓存

```bash
rm -rf /www/wwwroot/turnsy/bbo-server/runtime/cache/*
```

### 7.3 查看日志

```bash
# PHP 错误日志
tail -100 /www/wwwroot/turnsy/bbo-server/runtime/log/$(date +%Y%m)/$(date +%d).log

# Nginx 日志
tail -100 /www/wwwlogs/turnsysg.com.error.log
```

### 7.4 SSL 证书续期

Let's Encrypt 证书 90 天过期，宝塔面板通常会自动续期。手动续期：
宝塔面板 → 网站 → SSL → 续签

---

## 八、安全注意事项

1. `.env` 文件不要提交到 Git（已在 .gitignore 中排除）
2. `APP_DEBUG` 必须为 `false`
3. `JWT SECRET` 使用强随机值
4. 数据库密码使用强密码
5. 定期更新服务器系统和软件包
6. 定期备份数据库
7. 开启宝塔面板的安全设置（修改默认端口、设置授权 IP）

---

## 九、目录结构

```
/www/wwwroot/turnsy/
├── bbo-server/              # 后端 API（ThinkPHP 8）
│   ├── app/                 # 应用代码
│   ├── config/              # 配置文件
│   ├── public/              # Web 入口
│   │   ├── index.php        # 入口文件
│   │   ├── storage/         # 上传文件存储
│   │   └── .user.ini        # PHP 配置
│   ├── runtime/             # 运行时缓存/日志
│   ├── vendor/              # Composer 依赖
│   └── .env                 # 环境配置（不提交 Git）
├── bbo-admin/               # Admin 前端（Vue 3）
│   ├── src/                 # 源代码
│   └── dist/                # 构建产物（Nginx 根目录）
├── bbo-app/                 # APP 源码（UniApp）
│   └── src/                 # 源代码
├── bbo-app-h5/              # H5 构建产物（Nginx 根目录）
│   ├── index.html
│   ├── assets/
│   └── static/
└── DEPLOYMENT_GUIDE.md      # 本文件
```
