#!/bin/sh

# Este script deve ser rodado DENTRO do container da aplicação.
# Ele prepara a base de dados (instalando as dependências do MYSQL/Mariadb)
# e configurando a conta dos Super Admins para restaurar backups.

echo "==> Atualizando e Instalando MySQL CLI / MariaDB Connector..."
apk update && apk add mysql-client mariadb-connector-c

echo "==> Assumindo permissões seguras (www-data) para rodar o Laravel..."
su -s /bin/sh www-data -c "
  echo \"==> Rodando Migrações do zero...\"
  php artisan migrate:fresh --force
  
  echo \"==> Inserindo seus usuários Admin Angélica e Nando...\"
  php artisan db:seed --force
  
  echo \"==> Limpando Cache do Sistema...\"
  php artisan optimize:clear
"
echo "==> OK! Tudo finalizado com sucesso."
