package main

import (
	"fmt"
	"go.uber.org/zap"
	"net/http"
	"os"
)

var log zap.SugaredLogger

func handler(w http.ResponseWriter, r *http.Request) {
	log.Infow("received a request", "path", r.URL.Path, "headers", r.Header, "ip", r.RemoteAddr)
	target := os.Getenv("TARGET")
	if target == "" {
		target = "World"
	}
	fmt.Fprintf(w, "Hello %s!\n", target)
}

func main() {
	logger, err := zap.NewProduction()
	defer logger.Sync()
	log = *logger.Sugar().With("app", "hello-world")

	log.Infow("starting server")

	http.HandleFunc("/", handler)

	port := 8080

	log.Infof("listening on port %d", port)
	err = http.ListenAndServe(fmt.Sprintf(":%d", port), nil)
	log.Fatalw("error occured", "error", err)
}
