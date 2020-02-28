FROM debian:stable

ARG MAXIMA_VERSION=5.41.0
ARG SBCL_VERSION=1.4.11

ENV SRC=/opt/src
  
RUN SBCL_ARCH=$(dpkg --print-architecture); if [ $SBCL_ARCH = amd64 ]; then SBCL_ARCH=x86-64; fi; echo $SBCL_ARCH > /SBCL_ARCH

# Prerequisites for compiling
RUN apt-get update \
    && apt-get install -y \
    bzip2 \
    make \
    wget \
    python3 \
#    ca-certificates \
#    curl \
    texinfo

RUN mkdir -p ${SRC}
RUN wget https://sourceforge.net/projects/maxima/files/Maxima-source/${MAXIMA_VERSION}-source/maxima-${MAXIMA_VERSION}.tar.gz -O ${SRC}/maxima-${MAXIMA_VERSION}.tar.gz
RUN wget https://sourceforge.net/projects/sbcl/files/sbcl/${SBCL_VERSION}/sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux-binary.tar.bz2 -O ${SRC}/sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar.bz2

# Compile sbcl
RUN cd ${SRC} \
&& bzip2 -d sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar.bz2 \
&& tar -xf sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar \
&& rm sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar \
&& ls \
&& cd sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux \
&& ./install.sh

# Compile maxima
RUN cd ${SRC} \
&& tar -xf maxima-${MAXIMA_VERSION}.tar.gz \
&& rm maxima-${MAXIMA_VERSION}.tar.gz \
&& cd maxima-${MAXIMA_VERSION} \
&& ./configure \
&& make \
&& make install \
&& make clean

RUN rm -r ${SRC} /SBCL_ARCH
RUN apt-get purge -y wget python3 make bzip2 texinfo
