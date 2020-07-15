#!/bin/bash
set -e
echo ${MAXIMA_VERSION?Error \$MAXIMA_VERSION is not defined} \
     ${SBCL_VERSION?Error \$SBCL_VERSION is not defined}
  
SBCL_ARCH=$(dpkg --print-architecture)
if [ $SBCL_ARCH = amd64 ]; then
	SBCL_ARCH=x86-64
fi

apt-get update 
apt-get install -y bzip2 make wget python3 gcc texinfo

mkdir -p ${SRC}
wget https://sourceforge.net/projects/maxima/files/Maxima-source/${MAXIMA_VERSION}-source/maxima-${MAXIMA_VERSION}.tar.gz -O ${SRC}/maxima-${MAXIMA_VERSION}.tar.gz
wget https://sourceforge.net/projects/sbcl/files/sbcl/${SBCL_VERSION}/sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux-binary.tar.bz2 -O ${SRC}/sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux.tar.bz2

# Compile sbcl
cd ${SRC}
bzip2 -d sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux.tar.bz2
tar -xf sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux.tar
rm sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux.tar
ls
cd sbcl-${SBCL_VERSION}-${SBCL_ARCH}-linux
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
gcc -shared maxima_fork.c -lbsd -fPIC -Wall -Wextra -o libmaximafork.so
mv libmaximafork.so /usr/lib
rm -r ${SRC} /maxima_fork.c
mkdir -p ${LIB} ${LOG} ${TMP} ${PLOT} ${ASSETS} ${BIN}
apt-get purge -y bzip2 make wget python3 gcc texinfo
apt-get autoremove -y
