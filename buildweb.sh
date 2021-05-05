#!/bin/sh
# This script compiles the web server application.

GOBIN=$(realpath bin) go install ./src/web

