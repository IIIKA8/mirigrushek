#!/usr/bin/env bash
set -euo pipefail

DB_NAME="mirigrushek"
DB_PASS="Xmpl123!"
WEBROOT="/var/www/html"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Запустите через sudo: sudo bash setup.sh" >&2
    exit 1
fi

echo "==> [1/6] Установка пакетов (Apache, MySQL, PHP)…"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-gd

echo "==> [2/6] Запуск служб…"
systemctl enable --now apache2
systemctl enable --now mysql

echo "==> [3/6] Настройка MySQL (bind-address 0.0.0.0)…"
CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [[ -f "$CNF" ]]; then
    sed -i 's/^[[:space:]]*bind-address.*/bind-address = 0.0.0.0/' "$CNF"
    grep -q '^bind-address' "$CNF" || echo 'bind-address = 0.0.0.0' >> "$CNF"
fi
systemctl restart mysql

echo "==> [4/6] Создание базы данных…"
if mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
    mysql -u root --default-character-set=utf8mb4 < "$HERE/init.sql"
else
    mysql -u root -p"$DB_PASS" --default-character-set=utf8mb4 < "$HERE/init.sql"
fi

echo "==> [5/6] Развёртывание сайта в $WEBROOT…"
rm -f "$WEBROOT/index.html"
mkdir -p "$WEBROOT/images"
cp -rf "$HERE/web/." "$WEBROOT/"
if [[ -d "$HERE/images" ]]; then
    cp -rf "$HERE/images/." "$WEBROOT/images/"
fi
chown -R www-data:www-data "$WEBROOT"
a2enmod php* >/dev/null 2>&1 || true
if ! grep -q 'DirectoryIndex index.php' /etc/apache2/mods-enabled/dir.conf 2>/dev/null; then
    sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf || true
fi
systemctl reload apache2

echo "==> [6/6] Фаервол (порты 80, 3306)…"
if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    ufw allow 80/tcp || true
    ufw allow 3306/tcp || true
fi

IP="$(hostname -I | awk '{print $1}')"
echo
echo "============================================================"
echo " ГОТОВО."
echo " Сайт: http://$IP/"
echo " MySQL: host=$IP port=3306 user=root pass=$DB_PASS db=$DB_NAME"
echo " Пример входа: 94d5ous@gmail.com / uzWC67"
echo "============================================================"
