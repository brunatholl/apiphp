# apiphp - v1
apiphp
# Instalar o Slim na versao 3 com composer e PHP 7.0.33
composer require slim/slim:3.*

# Ping local
http://localhost/apiphp/api/api.php/ping

# Ping Docker 
http://localhost:8000/api/api.php/ping

# FAZER DEPLOY NO FLY.IO
* 1 - SELECIONAR ARQUIVO POM.XML NO "INTELIJ"
* 2 - IR DO LADO DIREITO EM MAVEM E IR NA OPÇÃO "Execute Maven Goal" e selecionar "mvn deploy"
* 2.1 - Se for uma atualização, rodar "mvn clean" antes de "mvn deploy"
* 3 - Nome do app no fly, vai ser o nome que esta no arquivo fly.toml
* 4 - Fazer login no fly, usando o comando "fly auth login"
* 5 - Se for a primeira vez que vai fazer deploy, usar o comando abaixo
* ``` fly launch --now```
* 6 - Se for uma atualizacao, usar o comando abaixo:
* ``` fly deploy ```