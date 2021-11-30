# Elasticsearch install

# Service directory template

When creating a new service, you can use this directory as a template. For a
basic Golang service, three steps are required:

1. Copy this directory. You can put the new directory anywhere in this repo
2. Copy the `charts/template` directory. The new chart should be `charts/[name
	 of your new service]`
3. Add your new service to `.azure-pipelines.yml` - use the entry for the
	 `template` service as guidance.
	 
If you're using another programming language, or you need a more complex build:
Update or replace the `Dockerfile` to suit your needs. The only requirement is
that the app should listen to ports `8080` for http or `8443` for https - if
you need another setup, you need to make the corresponding changes to your chart
in `charts`.
