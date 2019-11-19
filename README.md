# Jamboree21
En plats. En jamboree. Ett äventyr.

## Jamboree services

In this repo are all the services we are self-hosting or developing for the
[Jamboree21](https://jamboree.se) project. Each service has its own directory,
more or less.


## Contributing

Bug reports and suggestions are always welcome, and we're almost always happy to
merge your pull requests as well! Since this is a volunteer driven project the
backlog and wishlist is much longer than there's time for, so the smallest of
contributions will go a long way!

If you want to contribute frequently to this or other Scouterna IT projects,
plese reach out to [e-tjänstgruppen](https://etjanster.scout.se) who will be
happy to let you join them. If you're interested in working with Jamboree21
- with IT or with something else - send an email to jamboree@scouterna.se or
take a look at https://jamboree.se.


## Support infrastructure

### Build automation

There's an [Azure Pipelines project](https://dev.azure.com/scouterna/jamboree21)
set up to build all services that are included in `azure-pipelines.yaml`. To be
buildable a service must have a build file describing the steps necessary to
build it - take a look in the `template` directory (and, probably, copy it) when
creating or adding a new service.

### Deployment and hosting

The build pipeline contains no automated deployment, however the pipeline in the
standard `template` has some building blocks for simple deploying to our
Kubernetes environment:

* The `.k8s` directory contains templates for a Kubernetes Deployment and
	Service, the usual building blocks for a service in our cluster.
* The `build.yaml` template is set up to build a Docker container using your
	Dockerfile, publish the container to our registry at `scouterna.azurecr.io`,
	replace the image tag in the Kubernetes deployment speification and, finally,
	publish the `.k8s` directory as a build artifact.

The build artifact can easily be used in a Release Pipeline on [Azure
Pipelines](https://dev.azure.com/scouterna/jamboree21), to easily deploy the
right version of your service to our cluster.

If you need help with any of these steps, please reach out to e-tjänstgruppen.



