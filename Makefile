# Makefile for betcha app.
.PHONY: clean

console = php www/app/console

clean:
	${console} cache:clear --env=dev --no-warmup
	${console} cache:clear --env=prod --no-warmup