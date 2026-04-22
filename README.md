### Installation

1. Open in cmd or terminal app and navigate to this folder
2. Run following commands

```bash
composer install
```
```bash
npm install && npm run build```
```bash
cp .env.example .env
```
```bash
APP_NAME='POS'
APP_ENV=[env(local/dev/production)]
APP_DEBUG=[true/false]
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost

DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=[username_database]
DB_PASSWORD=[password_database]
```
```bash
php artisan key:generate
```
```bash
php artisan migrate --seed
```
```bash
php artisan storage:link
```

### Copyright

(c)dianarifr 2026