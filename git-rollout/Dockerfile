from alpine:latest
run apk update && apk add git git-daemon jq && \
    wget "https://dl.k8s.io/release/$(wget -q -O- https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl" && \
    chmod a+x kubectl && \
    cp kubectl /bin/kubectl && \
    mkdir -p /git
copy update.sh /bin/update.sh
workdir /git
entrypoint ["/bin/update.sh"]

