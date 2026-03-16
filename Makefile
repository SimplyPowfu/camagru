NAME = camagru

all: up

up:
	docker-compose up -d --build

down:
	docker-compose down

clean:
	docker-compose down -v --rmi all --remove-orphans

re: clean all

logs:
	docker-compose logs -f backend

shell:
	docker exec -it backend sh

.PHONY: all up stop down clean re logs shell