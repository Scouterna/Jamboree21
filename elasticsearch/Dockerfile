FROM docker.elastic.co/elasticsearch/elasticsearch:6.5.4

LABEL maintainer="zentabit@gmail.com"

COPY --chown=elasticsearch:elasticsearch elasticsearch.yml /usr/share/elasticsearch/config/

EXPOSE 9200
EXPOSE 9300