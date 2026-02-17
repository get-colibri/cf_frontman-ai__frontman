// Configuration for the standalone Frontman server

module Bindings = FrontmanBindings

type t = {
  port: int,
  hostname: string,
  projectRoot: string,
  sourceRoot: string,
  basePath: string,
  serverName: string,
  serverVersion: string,
}

let defaultPort = 4321
let defaultHostname = "127.0.0.1"
let defaultBasePath = "frontman"
let defaultServerName = "frontman-standalone"
let defaultServerVersion = "0.1.0"

let make = (
  ~port=defaultPort,
  ~hostname=defaultHostname,
  ~projectRoot: string,
  ~sourceRoot: option<string>=?,
  ~basePath=defaultBasePath,
  ~serverName=defaultServerName,
  ~serverVersion=defaultServerVersion,
): t => {
  let resolvedSourceRoot = sourceRoot->Option.getOr(projectRoot)

  {
    port,
    hostname,
    projectRoot,
    sourceRoot: resolvedSourceRoot,
    basePath,
    serverName,
    serverVersion,
  }
}
