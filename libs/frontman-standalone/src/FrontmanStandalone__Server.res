// Standalone Bun HTTP server wrapping the core middleware
//
// Uses Bun.serve() which natively supports Web API Request/Response -
// no adapter bridge needed (unlike Node.js http.createServer).

module Bun = Bindings__Bun
module Core = FrontmanFrontmanCore
module CoreMiddleware = Core.FrontmanCore__Middleware
module CoreMiddlewareConfig = Core.FrontmanCore__MiddlewareConfig
module ToolRegistry = Core.FrontmanCore__ToolRegistry
module Config = FrontmanStandalone__Config

// Convert standalone config to core middleware config
let toMiddlewareConfig = (config: Config.t): CoreMiddlewareConfig.t => {
  projectRoot: config.projectRoot,
  sourceRoot: config.sourceRoot,
  basePath: config.basePath,
  serverName: config.serverName,
  serverVersion: config.serverVersion,
  // Production client URL - the standalone server always serves the production client
  clientUrl: "https://app.frontman.sh/frontman.es.js",
  clientCssUrl: Some("https://app.frontman.sh/frontman.css"),
  entrypointUrl: None,
  isLightTheme: false,
  frameworkLabel: "Standalone",
}

// Start the standalone server
let start = (config: Config.t): Bun.server => {
  let registry = ToolRegistry.coreTools()
  let middlewareConfig = toMiddlewareConfig(config)
  let middleware = CoreMiddleware.createMiddleware(~config=middlewareConfig, ~registry)

  let server = Bun.serve({
    port: config.port,
    hostname: config.hostname,
    fetch: async req => {
      let result = await middleware(req)
      switch result {
      | Some(response) => response
      | None =>
        // Not a frontman route - return 404
        WebAPI.Response.jsonR(
          ~data=JSON.Encode.object(
            Dict.fromArray([("error", JSON.Encode.string("Not found"))]),
          ),
          ~init={status: 404},
        )
      }
    },
  })

  server
}
