FROM golang

#ENV RUN_USER=gouser \
#    RUN_GROUP=gogroup \
#    RUN_HOME=/home/${RUN_USER}

#RUN groupadd -r ${RUN_GROUP} && useradd -g ${RUN_GROUP} -d ${RUN_HOME} -s /bin/bash ${RUN_USER}
RUN mkdir /go/src/web

COPY ./src/web/web.go /go/src/web/web.go

#RUN ls ./src/web

RUN cd /go/src/web \
    && go build
#    && GOBIN=/go/bin go install
    #&& echo ${PATH}

CMD ["sleep","infinity"]
#CMD ["web"]
