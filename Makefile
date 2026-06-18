.PHONY: build up down exec

build:
	chmod +x deploy/deploy.sh
	./deploy/deploy.sh

up:
	docker-compose up -d

down:
	docker-compose down

exec:
	docker-compose exec app sh

logs:
	docker-compose logs -f