#!/bin/bash
set -e
for _ in $(seq 1 30); do
  if php /var/www/html/bin/criar-usuarios.php; then
    break
  fi
  sleep 2
done
exec apache2-foreground
