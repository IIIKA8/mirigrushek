#!/usr/bin/env bash
set -euo pipefail

REPO_URL="${MIRIGRUSHEK_REPO:-https://github.com/IIIKA8/mirigrushek.git}"
DEST="/opt/mirigrushek"

if [[ $EUID -ne 0 ]]; then
    echo "Нужны права root. Запустите:" >&2
    echo " curl -fsSL https://raw.githubusercontent.com/IIIKA8/mirigrushek/main/install.sh | sudo bash" >&2
    exit 1
fi

echo "==> Установка git…"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y git

echo "==> Клонирование репозитория в $DEST…"
rm -rf "$DEST"
git clone --depth 1 "$REPO_URL" "$DEST"

echo "==> Запуск установки…"
cd "$DEST"
bash setup.sh
