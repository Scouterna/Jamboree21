from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware  # NEW
from pydantic import BaseSettings, BaseModel, EmailStr
from typing import Optional, List, Tuple, Dict, Any, Union
from fastapi.staticfiles import StaticFiles
from enum import Enum
from datetime import timedelta
from requests_cache import CachedSession

import datetime
import json

session = CachedSession('access_cache', expire_after=timedelta(minutes=5), backend='memory')

class Settings(BaseSettings):
    app_name: str = "ScoutView"
    scoutnet_base: str = 'https://scoutnet.se/api'
    scoutnet_activity_id: int = 0
    scoutnet_participants_key: str = ''
    scoutnet_questions_key: str = ''

    class Config:
        env_file = ".env"


class ApplicationType(str, Enum):
    deltagare = 'Deltagare'
    ist = 'IST'

    def form_id(self):
        return {
            ApplicationType.deltagare: "5085",
            ApplicationType.ist: "5734",
        }[self.value]

    def classifier(self):
        return {
            ApplicationType.deltagare: "24549",
            ApplicationType.ist: "25654",
        }[self.value]

    def status_tab(self):
        return {
            ApplicationType.deltagare: "0000",
            ApplicationType.ist: "6537",
        }[self.value]



class Participant(BaseModel):
    member_no: int
    first_name: str
    last_name: str
    registration_date: datetime.datetime
    cancelled_date: Optional[datetime.datetime]
    sex: int
    date_of_birth: datetime.date
    primary_email: EmailStr
    questions: Any


class Question(BaseModel):
    question: str
    description: str
    type: str
    default_value: Optional[str]
    choices: Optional[Any]


class ParticipantsOut(BaseModel):
    length: int
    participants: List[Participant]


def get_participants():
    url = f'{settings.scoutnet_base}/project/get/participants?id={settings.scoutnet_activity_id}&key={settings.scoutnet_participants_key}'
    print(f'Fetching: {url}')
    r = session.get(url)
    data = json.loads(r.text)
    return list(data['participants'].values())


def get_questions(type: ApplicationType):
    url = f'{settings.scoutnet_base}/project/get/questions?id={settings.scoutnet_activity_id}&key={settings.scoutnet_questions_key}&form_id={type.form_id()}'
    print(f'Fetching: {url}')
    r = session.get(url)
    data = json.loads(r.text)['questions']
    del data['tabs']
    del data['sections']
    return data

def filter_form(participants: List[Participant], type: ApplicationType):
    # print(f'filter_form: {type} ({type.classifier()})')
    a = list(filter(lambda x: type.classifier() in x['questions'], participants))
    # print(f'filter_form: {len(a)}/{len(participants)}')
    return a

settings = Settings()
app = FastAPI(reload=True)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8080"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/info")
async def info():
    return {
        "app_name": settings.app_name,
        "activity": settings.scoutnet_activity,
    }

@app.get("/participants", response_model=ParticipantsOut)
def participants(type: Optional[ApplicationType] = None):
    p = get_participants()
    # print(f'type {type}')
    if (type != None):
        p = filter_form(p, type)
    res = ParticipantsOut(participants = p, length = len(p))
    return res

@app.get("/questions", response_model=Dict[int, Question])
def questions(type: Optional[ApplicationType] = ApplicationType.deltagare):
    r = get_questions(type)
    # print(r)
    return r

@app.get("/internal_statuses", response_model=Dict[int, Question])
def internal(type: Optional[ApplicationType] = ApplicationType.deltagare):
    q = get_questions(type)
    # print(q)
    status_questions = {key: value for (key, value) in q.items() if value['tab_id'] == int(type.status_tab())}
    # print(status_questions)
    return status_questions


# Place After All Other Routes
app.mount('', StaticFiles(directory="../client/public/", html=True), name="static")