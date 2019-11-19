FROM ubuntu:18.04

LABEL maintainer="johan@mrmanner.eu"

RUN mkdir ~/.gnupg
RUN echo "disable-ipv6" >> ~/.gnupg/dirmngr.conf

# Parsoid setup
RUN set -x \
    # Install required packages
    && apt update && apt -y upgrade \
	&& apt -y install dirmngr dumb-init curl php-curl software-properties-common \
	&& apt-key advanced --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys AF380A3036A03444 \
	&& apt-add-repository "deb https://releases.wikimedia.org/debian jessie-mediawiki main" \
	&& apt -y install apt-transport-https \
	&& apt -y install parsoid 

COPY config.yaml /etc/mediawiki/parsoid/config.yaml
COPY config.yaml /config.yaml
COPY config.yaml /usr/lib/parsoid/src/config.yaml

EXPOSE 8000
EXPOSE 8001
EXPOSE 8142

CMD ["/usr/bin/node", "/usr/lib/parsoid/src/bin/server.js"]
