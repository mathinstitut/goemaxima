FROM debian

ENV MAXIMAPOOL=/opt/maximapool \
    STACK_MAXIMA=/opt/maxima \
    MAXIMA_LOCAL_PATH=assStackQuestion/classes/stack/maxima \
    MAXIMA_VERSION=5.41.0 \
    SBCL_VERSION=1.4.11

RUN SBCL_ARCH=$(dpkg --print-architecture); if [ $SBCL_ARCH = amd64 ]; then SBCL_ARCH=x86-64; fi; echo $SBCL_ARCH > /SBCL_ARCH

# Prerequisites for compiling
RUN apt-get update \
    && apt-get install -y \
    bzip2 \
    make \
    wget \
    python3 \
    gnuplot \
    gettext-base \
    ca-certificates \
    curl \
    texinfo

RUN mkdir -p ${MAXIMAPOOL}
RUN wget https://sourceforge.net/projects/maxima/files/Maxima-source/${MAXIMA_VERSION}-source/maxima-${MAXIMA_VERSION}.tar.gz -O ${MAXIMAPOOL}/maxima-${MAXIMA_VERSION}.tar.gz
RUN wget https://sourceforge.net/projects/sbcl/files/sbcl/${SBCL_VERSION}/sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux-binary.tar.bz2 -O ${MAXIMAPOOL}/sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar.bz2

# Compile sbcl
RUN cd ${MAXIMAPOOL} \
&& bzip2 -d sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar.bz2 \
&& tar -xf sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar \
&& rm sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux.tar \
&& ls \
&& cd sbcl-${SBCL_VERSION}-$(cat /SBCL_ARCH)-linux \
&& ./install.sh

# Compile maxima
RUN cd ${MAXIMAPOOL} \
&& tar -xf maxima-${MAXIMA_VERSION}.tar.gz \
&& rm maxima-${MAXIMA_VERSION}.tar.gz \
&& cd maxima-${MAXIMA_VERSION} \
&& ./configure \
&& make \
&& make install \
&& make clean

# deinstallieren: wget, curl, python3, make, bzip2,

COPY ${MAXIMA_LOCAL_PATH} ${STACK_MAXIMA}
COPY stack_util_maximapool maximapool-docker/assets/process.conf.template maximapool-docker/assets/optimize.mac maximapool-docker/assets/maximalocal.mac.template ${MAXIMAPOOL}/

RUN VER=$(grep stackmaximaversion ${STACK_MAXIMA}/stackmaxima.mac | grep -oP "\d+") \
    && mkdir -p ${MAXIMAPOOL}/${VER} \
    && mv ${STACK_MAXIMA} ${MAXIMAPOOL}/${VER}/maxima \
    && mkdir -p ${MAXIMAPOOL}/${VER}/tmp/plots/ \
    && mkdir -p ${MAXIMAPOOL}/${VER}/tmp/logs/ \
    && cd ${MAXIMAPOOL}/ \
    && echo "Configuring Maxima for STACK" \
      && VER=$VER sh -c 'envsubst < process.conf.template > ${VER}/process.conf \
       && envsubst < maximalocal.mac.template > ${VER}/maximalocal.mac \
       && echo "Successfully configured Maxima for STACK ${VER}" \
       && echo "Optimizing Maxima (There will be some warnings due to docker container restrictions and strictness of sbcl) ..." \
       && mv ${MAXIMAPOOL}/optimize.mac ${MAXIMAPOOL}/${VER} \
       && cd ${MAXIMAPOOL}/${VER} \
       && maxima -b optimize.mac \
       && echo "Successfully optimized Maxima."'
