FROM debian:stable

# e.g. 5.41.0
ARG MAXIMA_VERSION
# e.g. 2.0.22.0.2
ARG SBCL_VERSION

ENV SRC=/opt/src \
    LIB=/opt/maxima/lib \
    LOG=/opt/maxima/log \
    TMP=/opt/maxima/tmp \
    PLOT=/opt/maxima/plot \
    ASSETS=/opt/maxima/assets \
    BIN=/opt/maxima/bin

COPY ./src/maxima_fork.c /
COPY ./buildscript.sh /

RUN bash /buildscript.sh

# e.g. stack/20200701/maxima
ARG LIB_PATH

RUN echo ${LIB_PATH?Error \$LIB_PATH is not defined}
# Copy Libraries
COPY ${LIB_PATH} ${LIB}

# Copy optimization scripts
COPY assets/maxima-fork.lisp assets/optimize.mac.template ${LIB_PATH}/../maximalocal.mac.template ${ASSETS}/

RUN grep stackmaximaversion ${LIB}/stackmaxima.mac | grep -oP "\d+" >> /opt/maxima/stackmaximaversion \
    && sh -c 'envsubst < ${ASSETS}/maximalocal.mac.template > ${ASSETS}/maximalocal.mac \
    && envsubst < ${ASSETS}/optimize.mac.template > ${ASSETS}/optimize.mac ' \
    && cat ${ASSETS}/maximalocal.mac && cat ${ASSETS}/optimize.mac \
    && cd ${ASSETS} \
    && maxima -b optimize.mac \
    && mv maxima-optimised ${BIN}/maxima-optimised \
    && for i in $(seq 16); do \
           useradd -M "maxima-$i"; \
    done

# Add go webserver
COPY ./bin/web ${BIN}/goweb

ENV GOEMAXIMA_LIB_PATH=/opt/maxima/assets/maximalocal.mac
ENV LANG C.UTF-8

EXPOSE 80

# rm /dev/tty because we do not want it to be opened by maxima for security reasons,
# and clear tmp because when kubernetes restarts a pod, it keeps the /tmp content even if it's tmpfs,
# which means that on a restart caused by an overfull tmpfs, it will keep restarting in a loop
CMD rm /dev/tty && cd /tmp && rm --one-file-system -rf * && exec tini ${BIN}/goweb ${BIN}/maxima-optimised
