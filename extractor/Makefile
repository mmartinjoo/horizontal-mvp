build:
	docker build -t horizontal-extractor:1.0 .

run:
	docker run -p 9998:80 -v ./:/app horizontal-extractor:1.0

up: build run