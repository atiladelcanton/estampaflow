.PHONY: setup upgrade hosts add-host start stop test quality e2e shell logs reset

setup:
	./scripts/setup.sh

upgrade:
	./scripts/upgrade-sprint-1.sh

hosts:
	./scripts/configure-hosts.sh

add-host:
	@if [ -z "$(DOMAIN)" ]; then echo "Informe DOMAIN=nome.estamparia.test"; exit 1; fi
	./scripts/add-tenant-host.sh "$(DOMAIN)"

start:
	./scripts/start.sh

stop:
	./scripts/stop.sh

test:
	./scripts/test.sh

quality:
	./scripts/quality.sh

e2e:
	docker compose up -d mysql app
	docker compose run --rm node npx playwright install chromium
	docker compose run --rm node npm run test:e2e

shell:
	docker compose run --rm app bash

logs:
	docker compose logs -f app

reset:
	docker compose down -v
	rm -rf vendor node_modules public/build .env
