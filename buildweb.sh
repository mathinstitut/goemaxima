#!/bin/sh
# This script compiles the web server application.
set -e
export GOBIN="$(realpath bin)" CGO_ENABLED=0
cd ./src/web
go mod download all
go install web

