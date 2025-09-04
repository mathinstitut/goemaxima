FROM golang:1-trixie AS web
COPY ./buildweb.sh /
COPY ./src/web /src/web
WORKDIR /
RUN ./buildweb.sh

FROM debian:trixie

# e.g. 5.41.0
ARG MAXIMA_VERSION
# e.g. 2.0.2
ARG SBCL_VERSION
# e.g. https://github.com/maths/moodle-qtype_stack
ARG QTYPE_STACK_REMOTE
# e.g. 6aff282a
# e.g. v4.8.1
ARG QTYPE_STACK_COMMIT

# number of maxima-%d user names/maximum number of processes
ARG MAX_USER=32

ENV SRC=/opt/src \
    LIB=/opt/maxima/lib \
    LOG=/opt/maxima/log \
    TMP=/opt/maxima/tmp \
    PLOT=/opt/maxima/plot \
    ASSETS=/opt/maxima/assets \
    BIN=/opt/maxima/bin

COPY ./src/maxima_fork.c ./buildscript.sh ./versions /

# Copy optimization scripts and template generation script.
COPY ./assets/generate_maximalocal_template.php ${ASSETS}/

# If QTYPE_STACK_REMOTE and QTYPE_STACK_COMMIT were both specified, we clone the repo and check
# it out at the specified commit.
RUN if [ -n "$QTYPE_STACK_REMOTE" ] && [ -n "$QTYPE_STACK_COMMIT" ]; then \
    apt-get update && apt-get install -y git php \
    && git clone ${QTYPE_STACK_REMOTE} qtype_stack && cd qtype_stack && git checkout ${QTYPE_STACK_COMMIT}; \
fi

# If building from qtype_stack directly, once the libraries are cloned we copy everything in to
# the appropriate place and generate the maximalocal.mac.template file, also putting it in the
# correct place.
RUN if [ -n "$QTYPE_STACK_REMOTE" ] && [ -n "$QTYPE_STACK_COMMIT" ]; then \
    mkdir -p ${LIB} && mkdir -p ${ASSETS} \
    && cp -r qtype_stack/stack/maxima/* ${LIB} \
    && cp qtype_stack/stack/cas/casstring.units.class.php ./ \
    && sed -i 's/require_once/\/\/ require_once/g' casstring.units.class.php \
    && php ${ASSETS}/generate_maximalocal_template.php > ${ASSETS}/maximalocal.mac.template; \
fi

# If building from qtype_stack, We then determine the stackmaxima version, and look it up in
# our version matrix (similar to what the buildimage.sh script does) to find the appropriate
# Maxima and SBCL versions to use. If MAXIMA_VERSION and SBCL_VERSION were specified as build
# args, they trump any values determined here.
#
# Finally, we call buildscript (regardless of if QTYPE_STACK_REMOTE and QTYPE_STACK_COMMIT were set
# because the values can still be optained from the original build args; and if they are missing the
# build script will throw an error) to create the environment where we will ultimately configure Maxima.
RUN if [ -n "$QTYPE_STACK_REMOTE" ] && [ -n "$QTYPE_STACK_COMMIT" ]; then \
    stackver=$(tail -n1 qtype_stack/stack/maxima/stackmaxima.mac | cut -d ':' -f2 | tr -d '$') \
    && verstring=$(awk '$1 == "'"$stackver"'"{ print $0 }' versions) \
    && export MAXIMA_VERSION=${MAXIMA_VERSION:-$(echo "$verstring" | cut -f2)} \
    && export SBCL_VERSION=${SBCL_VERSION:-$(echo "$verstring" | cut -f3)}; \
fi && ./buildscript.sh

# e.g. stack/20200701/maxima
ARG LIB_PATH

# If QTYPE_STACK_REMOTE or QTYPE_STACK_COMMIT are empty, we cannot retrieve the libraries using Git and must enforce
# LIB_PATH is set so we can copy the libraries from there.
RUN if ([ -z "$QTYPE_STACK_REMOTE" ] || [ -z "$QTYPE_STACK_COMMIT" ]) && [ -z "$LIB_PATH" ]; then \
    echo "\$LIB_PATH is not defined" \
    && exit 1; \
fi

# Copy Libraries. If LIB_PATH was not specified, nothing is copied (because LIB_PATH will
# expand to DOESNOTEXIST, and DOESNOTEXIST* matches nothing in the build context) so the
# libraries cloned from Git that are already in place will be used.
COPY goemaxima_version ${LIB_PATH:-DOESNOTEXIST}* ${LIB}

# If LIB_PATH was not set, the maximalocal.mac.template file will not be copied and the one generated from
# the libraries cloned from Git will be in place to use.
COPY ${LIB_PATH}/../maximalocal.mac.template* assets/maxima-fork.lisp assets/optimize.mac.template ${ASSETS}/

RUN grep stackmaximaversion ${LIB}/stackmaxima.mac | grep -oP "\d+" >> /opt/maxima/stackmaximaversion \
    && sh -c 'envsubst < ${ASSETS}/maximalocal.mac.template > ${ASSETS}/maximalocal.mac \
    && envsubst < ${ASSETS}/optimize.mac.template > ${ASSETS}/optimize.mac ' \
    && cat ${ASSETS}/maximalocal.mac && cat ${ASSETS}/optimize.mac \
    && cd ${ASSETS} \
    && maxima -b optimize.mac \
    && mv maxima-optimised ${BIN}/maxima-optimised \
    && for i in $(seq $MAX_USER); do \
           useradd -M "maxima-$i"; \
    done

RUN useradd -M "goemaxima-nobody"

COPY --from=web /bin/web ${BIN}/goweb

ENV GOEMAXIMA_LIB_PATH=/opt/maxima/assets/maximalocal.mac
ENV GOEMAXIMA_NUSER=$MAX_USER
RUN sh -c 'echo $GOEMAXIMA_NUSER'
ENV LANG C.UTF-8

EXPOSE 8080

HEALTHCHECK --interval=1m --timeout=3s CMD curl -f 'http://localhost:8080/goemaxima?health=1'

ENTRYPOINT ["tini", "--"]
CMD exec "${BIN}/goweb" "${BIN}/maxima-optimised"
