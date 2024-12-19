#!/bin/sh
# This script build SBCL and Maxima from source.
# It also compiles maxima_fork.c.

set -e
echo ${MAXIMA_VERSION?Error \$MAXIMA_VERSION is not defined} \
     ${SBCL_VERSION?Error \$SBCL_VERSION is not defined}

apt-get update
apt-get install -y bzip2 make wget python3 gcc texinfo curl libcap2-bin

mkdir -p ${SRC}
wget "https://github.com/mathinstitut/maxima-mirror/releases/download/${MAXIMA_VERSION}/maxima-${MAXIMA_VERSION}.tar.gz" -O "${SRC}/maxima-${MAXIMA_VERSION}.tar.gz"
wget "https://github.com/sbcl/sbcl/archive/refs/tags/sbcl-${SBCL_VERSION}.tar.gz" -O "${SRC}/sbcl-${SBCL_VERSION}.tar.gz"

# Compile sbcl (installs and removes debian sbcl for bootstrapping)
apt install -y sbcl
cd ${SRC}
tar -xzf sbcl-${SBCL_VERSION}.tar.gz
rm sbcl-${SBCL_VERSION}.tar.gz
cd sbcl-sbcl-${SBCL_VERSION}
echo "\"$SBCL_VERSION\"" > version.lisp-expr
./make.sh
apt remove -y sbcl
./install.sh

# Compile maxima
cd ${SRC}
tar -xf maxima-${MAXIMA_VERSION}.tar.gz
rm maxima-${MAXIMA_VERSION}.tar.gz
cd maxima-${MAXIMA_VERSION}
./configure
make
make install
make clean

# runtime dependencies
apt-get install -y gnuplot-nox gettext-base libbsd-dev tini

cd /
test -n "$MAX_USER" || MAX_USER=32
gcc -shared maxima_fork.c -lbsd -fPIC -Wall -Wextra -DN_SLOT="${MAX_USER}" -o libmaximafork.so
mv libmaximafork.so /usr/lib
rm -r ${SRC} /maxima_fork.c
mkdir -p ${LIB} ${LOG} ${TMP} ${PLOT} ${ASSETS} ${BIN}
apt-get purge -y bzip2 make wget python3 gcc texinfo
apt-get autoremove -y
