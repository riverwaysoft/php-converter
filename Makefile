COMPOSE_FILE = ./docker/docker-compose.yml
DOCKER_COMPOSE = docker compose -f ${COMPOSE_FILE}
DOCKER_COMPOSE_PHP_FPM_EXEC = ${DOCKER_COMPOSE} exec php-converter

build:
	${DOCKER_COMPOSE} build

up:
	${DOCKER_COMPOSE} up -d --remove-orphans

down:
	${DOCKER_COMPOSE} down -v

down_force:
	${DOCKER_COMPOSE} down -v --rmi=all --remove-orphans

console:
	if ! ${DOCKER_COMPOSE} ps | grep -q raphael-php-fpm; then make up; fi
	${DOCKER_COMPOSE_PHP_FPM_EXEC} bash
