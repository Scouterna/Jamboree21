#Create a ubuntu base image with python 3 installed.
FROM python:3
LABEL NAME=wikiupdate_api

#Set the working directory
WORKDIR /app

#copy all the files
COPY . /app/

#Install the dependencies
RUN apt-get -y update
RUN pip3 install -r requirements.txt

#Expose the required port
EXPOSE 5000

#Run the command
CMD [“python3”, “./main.py”]