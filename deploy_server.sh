#!/bin/bash
set -e

echo "🚀 Деплой начался..."

# --- Git ---
echo "📦 Обновление кода..."
git pull origin master

# --- PHP (Laravel) ---
echo "⚙️ Установка зависимостей..."
php8.3 composer.phar install --no-dev --optimize-autoloader

# --- Очистка старого кеша ---
echo "🧹 Очистка кеша..."
php8.3 artisan optimize:clear

# --- Production кеш (ВАЖНО) ---
echo "⚡ Кеширование..."
php8.3 artisan cache:clear        # Очистить общий кэш
php8.3 artisan config:clear       # Очистить кеш конфигураций
php8.3 artisan route:clear        # Очистить кеш маршрутов
php8.3 artisan view:clear         # Очистить кеш Blade шаблонов
php8.3 artisan event:clear        # Очистить кеш событий (если используется)

# --- Frontend (Vite + React) ---
echo "🎨 Сборка фронта не выполняется ........"


echo "✅ Деплой завершён"


#sh deploy_server.sh


