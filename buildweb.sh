#!/bin/sh
# This script compiles the web server application.
set -e
export GOBIN="$(realpath bin)"
cd ./src/web
go build web
go install web

