FROM docker.io/golang:buster AS base
RUN export GOPATH=/go && export PATH=$PATH:$GOPATH/bin
WORKDIR /build
COPY go.mod .
COPY go.sum .
RUN go mod download

FROM base AS build
COPY . .
RUN go get && go build 

FROM docker.io/debian:buster-slim
COPY --from=build /build/template /opt/
EXPOSE 8080
ENTRYPOINT ["/opt/template"]
