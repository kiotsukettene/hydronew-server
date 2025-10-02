setup:
	ddev start
	ddev composer install -v
	ddev artisan key:generate
	ddev artisan migrate
status:
	ddev status

