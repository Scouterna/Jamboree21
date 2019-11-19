# Service directory template

When creating a new service, you can use this directory as a template. Either
look in `.azure-pipelines` and `.k8s` and write your own specs with those in
there as inspiration, or make a copy of this directory and make (at least) the
following updates:

* Change `<service name>` in `.k8s/build.yaml` to the name of
	your service (preferably the exact name of the directory the service is in).
	This is a very bare service spec - you'll probably need some more info in your
	service. If you need help or pointers to get this right, contact
	e-tjänstgruppen who can help.

* If you need to receive traffic from the Internet, make a pull request to the
	`Scouterna/deploy` repo on Github with an Ingress for your service.
