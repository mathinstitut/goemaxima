#!/bin/sh
# This script compiles the web server application.

export GOBIN="$(realpath bin)"
cd ./src/web && go install web

