api:
	php api/artisan serve

frontend:
	npm --prefix ./frontend run dev

worker:
	php api/artisan queue:work --timeout=300 --max-jobs=100 max-time=1800
