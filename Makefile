db: docker-build
docker-build: build-back

build-back:
	docker-compose run --rm php sh -c "composer install"

build-zip:
	rm -rf inpostizi.zip
	cp -Ra $(PWD) /tmp/inpostizi
	rm -rf /tmp/inpostizi/composer.*
	rm -rf /tmp/inpostizi/config_*.xml
	rm -rf /tmp/inpostizi/.gitignore
	rm -rf /tmp/inpostizi/.git
	rm -rf /tmp/inpostizi/.php-cs-fixer.*
	rm -rf /tmp/inpostizi/_dev
	rm -rf /tmp/inpostizi/_admin_dev
	rm -rf /tmp/inpostizi/docker-compose.yml
	rm -rf /tmp/inpostizi/Makefile
	rm -rf /tmp/inpostizi/.gitlab-ci.yml
	mv -v /tmp/inpostizi $(PWD)/inpostizi
	zip -r inpostizi.zip inpostizi
	rm -rf $(PWD)/inpostizi

build-zip-prod: build-back build-zip
