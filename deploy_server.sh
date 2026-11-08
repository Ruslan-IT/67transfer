#!/bin/bash
set -e

echo "🚀 Деплой начался..."

# --- Git ---
echo "📦 Обновление кода..."
git pull origin master

# --- PHP (Laravel) ---
echo "⚙️ Установка зависимостей..."
/opt/php83/bin/php  composer.phar install --no-dev --optimize-autoloader

# --- Очистка старого кеша ---
echo "🧹 Очистка кеша..."
/opt/php83/bin/php artisan optimize:clear

# --- Production кеш (ВАЖНО) ---
echo "⚡ Кеширование..."
/opt/php83/bin/php artisan cache:clear        # Очистить общий кэш
/opt/php83/bin/php artisan config:clear       # Очистить кеш конфигураций
/opt/php83/bin/php artisan route:clear        # Очистить кеш маршрутов
/opt/php83/bin/php artisan view:clear         # Очистить кеш Blade шаблонов
/opt/php83/bin/php artisan event:clear        # Очистить кеш событий (если используется)

# --- Frontend (Vite + React) ---
echo "🎨 Сборка фронта не выполняется ........"


echo "✅ Деплой завершён"


#sh deploy_server.sh


