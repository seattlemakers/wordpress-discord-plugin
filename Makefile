.PHONY: build dev

PLUGIN_NAME := seattlemakers-discord-plugin

build:
	mkdir -p build
	cp -r src build/$(PLUGIN_NAME)
	rm -f build/$(PLUGIN_NAME).zip
	cd build && zip -r $(PLUGIN_NAME).zip $(PLUGIN_NAME)
	rm -rf build/$(PLUGIN_NAME)

clean:
	rm -rf build

dev:
	docker-compose up
