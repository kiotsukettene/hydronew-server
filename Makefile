setup:
	ddev start
	ddev composer install -v
	ddev artisan key:generate
	ddev migrate
status:
	ddev status

