#!/bin/bash
set -euo pipefail
base=http://localhost:8080
jar=$(mktemp)
trap 'rm -f "$jar"' EXIT

codigo=$(curl -s -o /dev/null -w "%{http_code}" "$base/login.php")
test "$codigo" = "200"

curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d "email=ivan@avisos.br&senha=aviso123" \
  "$base/login.php"

marca=$(date +%s)
curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d "titulo=Ensaio+$marca&corpo=Texto+do+ensaio+$marca" \
  "$base/publicar.php"

corpo=$(curl -s -c "$jar" -b "$jar" "$base/avisos.php")
printf '%s' "$corpo" | grep -q "Ensaio $marca"
printf '%s' "$corpo" | grep -q "Ivan"
echo "fluxo feliz ok"
