FROM debian:stable

# e.g. 5.41.0
ARG MAXIMA_VERSION
# e.g. 1.4.11
ARG SBCL_VERSION
# e.g. assStackQuestion/classes/stack/maxima
ARG LIB_PATH

RUN echo ${LIB_PATH?Error \$LIB_PATH is not defined} \
	 ${MAXIMA_VERSION?Error \$MAXIMA_VERSION is not defined} \
	 ${SBCL_VERSION?Error \$SBCL_VERSION is not defined}

ENV SRC=/opt/src \
    LIB=/opt/maxima/lib \
    LOG=/opt/maxima/log \
    TMP=/opt/maxima/tmp \
    PLOT=/opt/maxima/plot \
    ASSETS=/opt/maxima/assets \
    BIN=/opt/maxima/bin
  
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
RUN apt install -y gnuplot gettext-base

RUN mkdir -p ${LIB} ${LOG} ${TMP} ${PLOT} ${ASSETS} ${BIN}

# Copy Libraries
COPY ${LIB_PATH} ${LIB}

# Copy optimization scripts
COPY assets/optimize.mac.template assets/maximalocal.mac.template ${ASSETS}/

RUN grep stackmaximaversion ${LIB}/stackmaxima.mac | grep -oP "\d+" >> /opt/maxima/stackmaximaversion \
    && sh -c 'envsubst < ${ASSETS}/maximalocal.mac.template > ${ASSETS}/maximalocal.mac \
    && envsubst < ${ASSETS}/optimize.mac.template > ${ASSETS}/optimize.mac ' \
    && cat ${ASSETS}/maximalocal.mac && cat ${ASSETS}/optimize.mac \
    && cd ${ASSETS} \
    && maxima -b optimize.mac \
    && mv maxima-optimised ${BIN}/maxima-optimised \
    && rm -r ${LIB}

RUN apt-get purge -y wget python3 make bzip2 texinfo

# Add go webserver
COPY ./bin/web ${BIN}/goweb
CMD ["/opt/maxima/bin/goweb"]

