#!/bin/bash
docker build -t gotest .
docker run --rm -d --name gotest gotest
docker cp gotest:/go/src/web/web ../maxima/bin/web
docker stop gotest
