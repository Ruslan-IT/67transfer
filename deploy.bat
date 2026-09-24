@echo off
echo 🚀 Автодеплой запущен...



REM --- Git ---
echo 📦 Отправка изменений...
git add .
git commit -m "Update admin panel category"
git push origin master

REM --- Backend (Laravel) ---
echo ⚙️ Установка PHP зависимостей...
composer install --no-dev --optimize-autoloader

echo 🔑 Кеширование конфигов...
php artisan config:cache
php artisan route:cache
php artisan view:cache

REM --- Frontend (Vite + React) ---
echo 🎨 Установка npm зависимостей...
npm install

echo 🏗️ Сборка фронта...
npm run build

echo ✅ Деплой завершен
pause
