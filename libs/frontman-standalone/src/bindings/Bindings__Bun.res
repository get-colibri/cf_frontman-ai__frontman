// Minimal ReScript bindings for Bun's serve API

type server = {
  port: int,
  hostname: string,
}

type serveOptions = {
  port: int,
  hostname: string,
  fetch: WebAPI.FetchAPI.request => promise<WebAPI.FetchAPI.response>,
}

@val @scope("Bun") external serve: serveOptions => server = "serve"

// Bun.argv - command line arguments
@val @scope("Bun") external argv: array<string> = "argv"

// Process environment
@val @scope("process") external env: Dict.t<string> = "env"
