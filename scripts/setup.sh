#!/usr/bin/env bash
set -euo pipefail

echo "[setup] Iniciando setup de desenvolvimento..."

# Tenta iniciar MariaDB/MySQL
echo "[setup] tentando iniciar MariaDB/MySQL (se necessário)..."
service mariadb start 2>/dev/null || service mysql start 2>/dev/null || service mysqld start 2>/dev/null || true

# Espera até o serviço responder (se mysqladmin estiver disponível)
if command -v mysqladmin >/dev/null 2>&1; then
  echo "[setup] aguardando MariaDB (até 30s)..."
  i=0
  until mysqladmin ping --silent >/dev/null 2>&1; do
    i=$((i+1))
    if [ "$i" -ge 30 ]; then
      echo "[setup] timeout esperando MariaDB. Prosseguindo (verifique manualmente)."
      break
    fi
    sleep 1
  done
  echo "[setup] checagem MariaDB concluída (ou timeout)."
else
  echo "[setup] mysqladmin não encontrado; pulando wait."
fi

# Escolhe cliente SQL disponível
MYSQL_CMD=""
if command -v mysql >/dev/null 2>&1; then
  MYSQL_CMD="$(command -v mysql)"
elif command -v mariadb >/dev/null 2>&1; then
  MYSQL_CMD="$(command -v mariadb)"
fi

if [ -z "${MYSQL_CMD}" ]; then
  echo "[setup] cliente mysql/mariadb não encontrado no PATH. Instale 'mysql-client' ou 'mariadb-client'."
  exit 1
fi

echo "[setup] usando cliente: ${MYSQL_CMD}"

# Testa conexão como root (socket ou TCP).
# Se o `.devcontainer/init.sh` já configurou uma senha root, tentamos usá-la.
# A senha padrão usada no init.sh é _43690 (aplicada para dev). Primeiro
# tentamos sem senha, depois com a senha conhecida.
DB_ROOT_PASS='_43690'
ROOT_CONN_OPTS=""

if ${MYSQL_CMD} -u root -e "SELECT 1;" >/dev/null 2>&1; then
  ROOT_CONN_OPTS="-u root"
elif ${MYSQL_CMD} -u root -p"${DB_ROOT_PASS}" -e "SELECT 1;" >/dev/null 2>&1; then
  ROOT_CONN_OPTS="-u root -p${DB_ROOT_PASS}"
else
  echo "[setup] não foi possível conectar como root via ${MYSQL_CMD}. Tente executar 'bash .devcontainer/init.sh <repo>' ou ver logs do MariaDB."
  echo "[setup] finalizado."
  exit 0
fi

echo "[setup] conectado como root — criando banco/usuário de desenvolvimento..."
${MYSQL_CMD} ${ROOT_CONN_OPTS} <<'SQL'
CREATE DATABASE IF NOT EXISTS `jebusiness` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'jebusiness'@'127.0.0.1' IDENTIFIED BY '_43690';
GRANT ALL PRIVILEGES ON jebusiness.* TO 'jebusiness'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

echo "[setup] banco e usuário criados/atualizados. Rodando migrations..."
if command -v php >/dev/null 2>&1; then
  php scripts/migration.php || echo "[setup] migrations reportaram erro (veja saída acima)."
else
  echo "[setup] php não encontrado no PATH; não foi possível rodar migrations."
fi

# Garantir que git-lfs esteja disponível para permitir 'git push' quando houver LFS configurado
if ! command -v git-lfs >/dev/null 2>&1; then
  echo "[setup] git-lfs não encontrado; tentando instalar via apt (requer sudo)..."
  if sudo apt-get update -y >/dev/null 2>&1 && sudo apt-get install -y git-lfs >/dev/null 2>&1; then
    echo "[setup] git-lfs instalado com sucesso. Configurando localmente..."
    git lfs install --local >/dev/null 2>&1 || true
  else
    echo "[setup] falha ao instalar git-lfs automaticamente. Removendo hook pre-push como fallback (se existir)."
    if [ -f .git/hooks/pre-push ]; then
      rm -f .git/hooks/pre-push || true
      echo "[setup] hook .git/hooks/pre-push removido. Se o repositório usa LFS, instale git-lfs manualmente antes de push." 
    fi
  fi
else
  git lfs install --local >/dev/null 2>&1 || true
fi

echo "[setup] finalizado."
