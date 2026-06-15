#!/usr/bin/env bash
set -euo pipefail

DB_NAME="mirigrushek"
DB_PASS="123"
WEBROOT="/var/www/html"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
    echo "Запустите через sudo: sudo bash setup.sh" >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

log() { echo "==> $*"; }

# Сломанные mods-enabled (файл вместо симлинка) ломают postinst apache2 на Ubuntu.
fix_apache_symlinks() {
    [[ -d /etc/apache2/mods-enabled ]] || return 0
    local f name
    for f in /etc/apache2/mods-enabled/*; do
        [[ -e "$f" ]] || continue
        if [[ -f "$f" && ! -L "$f" ]]; then
            name=$(basename "$f")
            log "Исправление Apache: mods-enabled/$name"
            rm -f "$f"
            if [[ -f "/etc/apache2/mods-available/$name" ]]; then
                ln -sf "../mods-available/$name" "/etc/apache2/mods-enabled/$name"
            fi
        fi
    done
}

repair_dpkg() {
    fix_apache_symlinks
    dpkg --configure -a 2>/dev/null || true
    apt-get install -f -y 2>/dev/null || true
}

mysql_cmd() {
    if mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
        mysql -u root "$@"
    elif mysql -u root -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; then
        mysql -u root -p"$DB_PASS" "$@"
    else
        mysql "$@"
    fi
}

install_packages() {
    local attempt
    for attempt in 1 2 3; do
        log "[1/7] Установка пакетов (попытка $attempt/3)…"
        repair_dpkg
        if apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-gd; then
            repair_dpkg
            return 0
        fi
        log "Повтор после исправления dpkg…"
        repair_dpkg
    done
    echo "ОШИБКА: не удалось установить пакеты." >&2
    exit 1
}

configure_apache_php() {
    fix_apache_symlinks
    a2dismod mpm_event 2>/dev/null || true
    a2dismod mpm_worker 2>/dev/null || true
    a2enmod mpm_prefork 2>/dev/null || true

    local modfile modname
    for modfile in /etc/apache2/mods-available/php*.load; do
        [[ -f "$modfile" ]] || continue
        modname=$(basename "$modfile" .load)
        a2enmod "$modname" 2>/dev/null || true
        break
    done

    if [[ -f /etc/apache2/mods-enabled/dir.conf ]]; then
        if ! grep -q 'index.php' /etc/apache2/mods-enabled/dir.conf; then
            sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf
        fi
    fi
}

verify_install() {
    local code count
    systemctl is-active --quiet apache2 || { echo "Apache не запущен" >&2; exit 1; }
    systemctl is-active --quiet mysql || { echo "MySQL не запущен" >&2; exit 1; }
    code=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/index.php 2>/dev/null || echo "000")
    if [[ "$code" != "200" && "$code" != "302" ]]; then
        echo "Сайт не отвечает (HTTP $code)" >&2
        exit 1
    fi
    count=$(mysql_cmd -N -e "USE $DB_NAME; SELECT COUNT(*) FROM Products;" 2>/dev/null || echo "0")
    if [[ "$count" -lt 1 ]]; then
        echo "База данных пуста или недоступна" >&2
        exit 1
    fi
}

# --- main ---
repair_dpkg
install_packages

log "[2/7] Запуск служб…"
systemctl enable apache2 mysql 2>/dev/null || true
systemctl start mysql 2>/dev/null || true
systemctl start apache2 2>/dev/null || true

log "[3/7] Настройка MySQL…"
CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [[ -f "$CNF" ]]; then
    if grep -q '^bind-address' "$CNF"; then
        sed -i 's/^[[:space:]]*bind-address.*/bind-address = 0.0.0.0/' "$CNF"
    else
        echo 'bind-address = 0.0.0.0' >> "$CNF"
    fi
fi
systemctl restart mysql

log "[4/7] Импорт базы данных…"
mysql_cmd --default-character-set=utf8mb4 < "$HERE/init.sql"

log "[5/7] Развёртывание сайта в $WEBROOT…"
rm -f "$WEBROOT/index.html"
mkdir -p "$WEBROOT/images"
cp -rf "$HERE/web/." "$WEBROOT/"
if [[ -d "$HERE/images" ]]; then
    cp -rf "$HERE/images/." "$WEBROOT/images/"
fi
chown -R www-data:www-data "$WEBROOT"
chmod -R a+rX "$WEBROOT"

log "[6/7] Настройка Apache + PHP…"
configure_apache_php
apache2ctl configtest 2>/dev/null || true
systemctl restart apache2

log "[7/7] Фаервол и проверка…"
if command -v ufw >/dev/null 2>&1 && ufw status 2>/dev/null | grep -q "Status: active"; then
    ufw allow 80/tcp 2>/dev/null || true
    ufw allow 3306/tcp 2>/dev/null || true
fi
verify_install

IP="$(hostname -I | awk '{print $1}')"
echo
echo "============================================================"
echo " ГОТОВО."
echo " Сайт:      http://$IP/"
echo " MySQL:     host=$IP  port=3306  user=root  pass=$DB_PASS"
echo " База:      $DB_NAME"
echo " Вход:      94d5ous@gmail.com / uzWC67"
echo "============================================================"
