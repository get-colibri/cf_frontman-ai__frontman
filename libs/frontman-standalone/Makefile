.PHONY: build build-rescript build-binary clean

build-rescript:
	yarn rescript build

build: build-rescript

# Build standalone binary for current platform
build-binary: build-rescript
	bun build src/FrontmanStandalone__CLI.res.mjs --compile --outfile dist/frontman-standalone

# Build binaries for all platforms
build-all-binaries: build-rescript
	mkdir -p dist
	bun build src/FrontmanStandalone__CLI.res.mjs --compile --target=bun-darwin-arm64 --outfile dist/frontman-standalone-darwin-arm64
	bun build src/FrontmanStandalone__CLI.res.mjs --compile --target=bun-darwin-x64 --outfile dist/frontman-standalone-darwin-x64
	bun build src/FrontmanStandalone__CLI.res.mjs --compile --target=bun-linux-x64 --outfile dist/frontman-standalone-linux-x64
	bun build src/FrontmanStandalone__CLI.res.mjs --compile --target=bun-linux-arm64 --outfile dist/frontman-standalone-linux-arm64

clean:
	rm -rf dist
	yarn rescript clean
