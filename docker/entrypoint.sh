#!/bin/sh
set -eu

mkdir -p "$UPLOAD_DIR"
chown -R www-data:www-data "$UPLOAD_DIR"

exec apache2-foreground
