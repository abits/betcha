# Makefile for betcha app.
.PHONY: clean pull update build init build-all ant ant-default

console = php www/app/console
wwwuser = apache
consoleuser = chm

clean:
	rm -rf www/app/cache/*
	rm -rf www/app/logs/*

ant:
	php bin/make2ant.php

ant-default: ant update

update-db:
	${console} doctrine:schema:update --force
	${console} assets:install www/web

build-db:
	${console} doctrine:database:drop --force
	${console} doctrine:database:create
	${console} doctrine:schema:create
	${console} fos:user:create admin betcha@localhost password

assets:
	${console} assets:install www/web

init:
	cp www/app/config/parameters.yml.dist www/app/config/parameters.yml

permissions:
	sudo chown -R ${consoleuser}:${wwwuser} www
	chmod -R 0775 www/app/cache
	chmod -R 0775 www/app/logs

build: clean init build-db  permissions

update: clean update-db permissions

