app_name=integration_basecamp
build_dir=/tmp/build

.PHONY: appstore clean

appstore:
	rm -rf $(build_dir)/$(app_name)
	mkdir -p $(build_dir)/$(app_name)
	rsync -a \
		--exclude=.git \
		--exclude=.github \
		--exclude=.gitignore \
		--exclude=.idea \
		--exclude=.claude \
		--exclude=node_modules \
		--exclude=src \
		--exclude=tests \
		--exclude=composer.json \
		--exclude=composer.lock \
		--exclude=package.json \
		--exclude=package-lock.json \
		--exclude=vite.config.ts \
		--exclude=Makefile \
		--exclude=nc-dev.sh \
		--exclude=CLAUDE.md \
		--exclude=README.md \
		. $(build_dir)/$(app_name)/
	tar -czf $(build_dir)/$(app_name).tar.gz -C $(build_dir) $(app_name)
	@echo ""
	@echo "Archive created: $(build_dir)/$(app_name).tar.gz"

clean:
	rm -rf $(build_dir)/$(app_name) $(build_dir)/$(app_name).tar.gz
