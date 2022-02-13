FROM node:17 AS build

WORKDIR /app

COPY client/package.json ./
COPY client/package-lock.json ./
RUN npm install
COPY client ./
RUN npm run build

FROM python:3.9-buster

RUN mkdir app
WORKDIR /app

ENV PATH="${PATH}:/root/.local/bin"
ENV PYTHONPATH=.

COPY requirements.txt .
RUN pip install --upgrade pip
RUN pip install --no-cache-dir --upgrade -r requirements.txt

COPY ./application /app
COPY --from=build /app/public /client/public

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "80"]
