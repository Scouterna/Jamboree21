FROM python:3

WORKDIR /usr/src/planera

COPY application/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY ./application .

CMD [ "python", "./main.py" ]

EXPOSE 8080
