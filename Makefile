# Makefile for betcha app.
.PHONY: clean pull update build init build-all

console = php www/app/console
git = /bin/git
wwwuser = apache
consoleuser = chm

clean: permissions
	${console} cache:clear --env=dev --no-warmup
	${console} cache:clear --env=prod --no-warmup
	su -c "rm -rf www/app/cache/* www/app/logs/*"

pull:
	${git} pull

update: permissions
	${console} doctrine:schema:update --force
	${console} assets:install www/web

build: permissions
	${console} doctrine:database:drop --force
	${console} doctrine:database:create
	${console} doctrine:schema:update --force
	${console} fos:user:create admin betcha@localhost password
	${console} assets:install www/web

init: permissions
	cp www/app/config/parameters.yml.dist www/app/config/parameters.yml

permissions:
	su -c "chown -R ${consoleuser}:${wwwuser} www; chmod -R 0775 www/app/cache; chmod -R 0775 www/app/logs"

build-all: pull init build clean

update-all: pull update clean