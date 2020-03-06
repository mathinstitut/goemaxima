ARG BASEIMAGE=maxima_base:latest

FROM $BASEIMAGE

ENV LIB=/opt/maxima/lib \
    LOG=/opt/maxima/log \
    TMP=/opt/maxima/tmp \
    PLOT=/opt/maxima/plot \
    ASSETS=/opt/maxima/assets \
    BIN=/opt/maxima/bin

RUN apt-get update \
    && apt-get install -y \
    gnuplot \
    gettext-base 

# specify here which stack or moodle to use:
# normally something like 'assStackQuestion/classes/stack/maxima'
ARG LIB_PATH

RUN echo ${LIB_PATH?Error \$LIB_PATH is not defined. Define it with 'docker build --build-arg LIB_PATH=assStackQuestion/classes/stack/maxima -t name . '}

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

# Add go webserver
COPY ./bin/web ${BIN}/goweb
CMD ["/opt/maxima/bin/goweb"]
