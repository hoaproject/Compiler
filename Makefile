.PHONY: all
all: test

.PHONY: test
test: cs
	php vendor/bin/atoum -ncc -d atoum/Unit/
	php vendor/bin/phpunit --coverage-text

.PHONY: cs
cs: vendor/autoload.php
	php vendor/bin/php-cs-fixer fix -v

vendor/autoload.php: composer.json
	composer update
