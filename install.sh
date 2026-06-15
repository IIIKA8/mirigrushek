#!/usr/bin/env bash
set -euo pipefail

REPO_URL="${MIRIGRUSHEK_REPO:-https://github.com/IIIKA8/mirigrushek.git}"
DEST="/opt/mirigrushek"

if [[ $EUID -ne 0 ]]; then
    echo "Нужны права root. Запустите:" >&2
    echo " curl -fsSL https://raw.githubusercontent.com/IIIKA8/mirigrushek/main/install.sh | sudo bash" >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

# Починка Apache до apt, если на ВМ остались битые конфиги от прошлой попытки.
if [[ -d /etc/apache2/mods-enabled ]]; then
    for f in /etc/apache2/mods-enabled/*; do
        [[ -f "$f" && ! -L "$f" ]] && rm -f "$f" && \
            [[ -f "/etc/apache2/mods-available/$(basename "$f")" ]] && \
            ln -sf "../mods-available/$(basename "$f")" "/etc/apache2/mods-enabled/$(basename "$f")"
    done
    dpkg --configure -a 2>/dev/null || true
    apt-get install -f -y 2>/dev/null || true
fi

echo "==> Установка git…"
apt-get update -y
apt-get install -y git curl

echo "==> Клонирование репозитория в $DEST…"
rm -rf "$DEST"
git clone --depth 1 "$REPO_URL" "$DEST"

echo "==> Запуск установки…"
cd "$DEST"
bash setup.sh
