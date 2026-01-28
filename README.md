# Random Chat

匿名 1v1 隨機聊天室（Laravel 12 + Inertia + Vue 3）。

## 開發環境需求

- PHP 8.4
- Composer
- Node.js + npm
- Redis（配對佇列與排程清理使用）
- Pusher 相容服務（Pusher Cloud 或 Soketi）

## 安裝

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

或使用專案內建腳本：

```bash
composer run setup
```

## 開發啟動

```bash
composer run dev
```

## 測試

```bash
php artisan test --compact
```

## 心跳機制

- 前端每 8 秒呼叫 `POST /api/chat/heartbeat`
- 後端會刷新 `chat:presence:{user_key}` TTL（預設 25 秒）
- 若判定對方離線，後端會關閉房間並廣播離開事件

## 排程（清理無心跳房間）

排程指令：`chat:rooms:cleanup`  
已在 `routes/console.php` 設定每小時執行一次。

在本機開發可用：

```bash
php artisan schedule:work
```

## 重要指令

```bash
php artisan chat:rooms:cleanup
php artisan queue:listen --tries=1 --timeout=0
```

## 部署重點

- 設定 `.env`（APP_KEY、DB、CACHE、QUEUE、BROADCAST 等）。
- 執行 `php artisan migrate --force`。
- 編譯前端：`npm run build`。
- 啟動 queue worker（`queue:work` 或 Supervisor）。
- 啟動排程（推薦使用系統 cron + `php artisan schedule:run`，或 `schedule:work`）。

## 廣播（Pusher / Echo）設定

後端 `.env` 範例：

```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=ap1
```

使用 Soketi（Pusher 相容）時可追加：

```
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

前端 `resources/js` 中已使用 `laravel-echo` + `pusher-js`，只要確保：

- `VITE_PUSHER_APP_KEY`、`VITE_PUSHER_APP_CLUSTER` 有對應到 `.env`
- `Broadcast::routes()` 已啟用（目前在 `routes/web.php`）
