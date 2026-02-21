// CLI entry point for the standalone Frontman server
//
// Usage: frontman-standalone --project-root /path/to/project [--port 19478] [--source-root /path]

module Bun = Bindings__Bun
module Config = FrontmanStandalone__Config
module Server = FrontmanStandalone__Server
module Path = FrontmanBindings.Path

// Parse a flag value from argv: --flag value
let getFlag = (args: array<string>, flag: string): option<string> => {
  let idx = args->Array.findIndex(arg => arg == flag)
  switch idx >= 0 {
  | true => args->Array.get(idx + 1)
  | false => None
  }
}

// Check if a flag exists in argv
let hasFlag = (args: array<string>, flag: string): bool => {
  args->Array.includes(flag)
}

// Parse integer from string
let parseInt = (str: string): option<int> => {
  let n = str->Int.fromString(~radix=10)
  n
}

// Main CLI logic
let run = () => {
  let args = Bun.argv

  // Help
  if hasFlag(args, "--help") || hasFlag(args, "-h") {
    Js.log(`Frontman Standalone Server

Usage:
  frontman-standalone --project-root <path> [options]

Options:
  --project-root <path>   Root directory of the project (required)
  --source-root <path>    Root for file path resolution (defaults to project-root)
  --port <number>         Port to listen on (default: ${Config.defaultPort->Int.toString})
  --hostname <host>       Hostname to bind to (default: ${Config.defaultHostname})
  --help, -h              Show this help message
  --version, -v           Show version

Examples:
  frontman-standalone --project-root /path/to/wordpress
  frontman-standalone --project-root . --port 8080`)
  } else if hasFlag(args, "--version") || hasFlag(args, "-v") {
    Js.log(`frontman-standalone ${Config.defaultServerVersion}`)
  } else {
    // Parse project root
    let projectRoot = switch getFlag(args, "--project-root") {
    | Some(root) => Path.resolve(root)
    | None =>
      // Default to current working directory
      switch Bun.env->Dict.get("PWD") {
      | Some(pwd) => pwd
      | None => "."
      }
    }

    let sourceRoot = getFlag(args, "--source-root")->Option.map(root => Path.resolve(root))

    let port = getFlag(args, "--port")
    ->Option.flatMap(parseInt)
    ->Option.getOr(Config.defaultPort)

    let hostname = getFlag(args, "--hostname")->Option.getOr(Config.defaultHostname)

    let config = Config.make(
      ~port,
      ~hostname,
      ~projectRoot,
      ~sourceRoot?,
    )

    let server = Server.start(config)

    Js.log("")
    Js.log("  Frontman Standalone Server")
    Js.log("")
    Js.log(`  URL:          http://${server.hostname}:${server.port->Int.toString}/frontman`)
    Js.log(`  Project root: ${config.projectRoot}`)
    Js.log(`  Source root:  ${config.sourceRoot}`)
    Js.log("")
    Js.log("  Waiting for connections...")
    Js.log("")
  }
}

// Run immediately
run()
