#!/bin/sh
# This script compiles the web server application.
set -e
export GOBIN="$(realpath bin)"
cd ./src/web
go mod download github.com/prometheus/client_golang
go install web

