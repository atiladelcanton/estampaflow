.PHONY: setup upgrade upgrade-sprint1 upgrade-sprint2 hosts add-host start stop test quality e2e shell logs reset mailpit queue-logs queue-failed queue-retry provision-domains

setup:
	./scripts/setup.sh

upgrade:
	./scripts/upgrade-sprint-2.sh

upgrade-sprint1:
	./scripts/upgrade-sprint-1.sh

upgrade-sprint2:
	./scripts/upgrade-sprint-2.sh

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
	docker compose up -d mysql mailpit app queue
	docker compose run --rm node npx playwright install chromium
	docker compose run --rm node npm run test:e2e

shell:
	docker compose run --rm app bash

logs:
	docker compose logs -f app

mailpit:
	@echo "Mailpit: http://localhost:8025"

queue-logs:
	docker compose logs -f queue

queue-failed:
	docker compose run --rm app php artisan queue:failed

queue-retry:
	docker compose run --rm app php artisan queue:retry all

provision-domains:
	docker compose run --rm app php artisan domain:provision-pending --include-failed

reset:
	docker compose down -v
	rm -rf vendor node_modules public/build .env
