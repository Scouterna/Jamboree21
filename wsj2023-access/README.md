# Data access for WSJ2023

This is a proxy service that makes it possible to read form information and
update statuses for participants in an activity in Scoutnet.

##  Configuration
Can be reused by setting the expected ENV (fetched from Secrets in /charts/wsj2023-access/templates/deployment.yaml)

    SCOUTNET_ACTIVITY_ID=<Activity ID>
    SCOUTNET_PARTICIPANTS_KEY=<Key for the participants webapi>
    SCOUTNET_QUESTIONS_KEY=<Key for the Questions webapi>

## Development

Create an .env with the info abouve and run

    docker run --rm -it -p 8000:80 --env-file=<path>/.env <container>

## Forms / Views

A view is a selection of the questions/answers that are presented per participant
A form is a specific form used for the participants registration (e.g. IST/Participant/CMT)

You have access to a specific view, but can always switch forms.
