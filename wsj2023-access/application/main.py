import datetime
import json
from datetime import timedelta
from enum import Enum
from pathlib import Path
from typing import Any, Dict, Generic, List, Optional, Set, TypeVar, Union

from fastapi import Depends, FastAPI, HTTPException, Query, Request, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from fastapi_pagination import add_pagination, paginate
from fastapi_pagination.default import Page as BasePage
from fastapi_pagination.default import Params as BaseParams
from fastapi.responses import RedirectResponse
from pydantic import BaseModel, BaseSettings, EmailStr, constr
from requests_cache import CachedSession

T = TypeVar("T")
class Params(BaseParams):
    size: int = Query(50, ge=1, le=50_000, description="Page size")

class Page(BasePage[T], Generic[T]):
    __params_type__ = Params

session = CachedSession('access_cache', expire_after=timedelta(minutes=5), backend='memory')

class Settings(BaseSettings):
    app_name: str = "ScoutView"
    scoutnet_base: str = 'https://scoutnet.se/api'
    scoutnet_activity_id: int = 0
    scoutnet_participants_key: str = ''
    scoutnet_questions_key: str = ''
    scoutnet_checkin_key: str = ''
    scoutview_debug_email: Optional[str] = None
    scoutview_roles: Optional[Dict[str, Set[str]]] = {}

    class Config:
        env_file = ".env"

class View(str, Enum):
    pass

class User(BaseModel):
    email: str
    roles: Optional[List[str]] = []

    def has_role(self, role):
        res = (role in self.roles) or ('admin' in self.roles)
        print(f'{self.email} in roles {self.roles}, request {role}: {res}')
        return res

class Participant(BaseModel):
    member_no: int
    first_name: str
    last_name: str
    registration_date: datetime.datetime
    cancelled_date: Optional[datetime.datetime]
    sex: int
    date_of_birth: datetime.date
    primary_email: Union[EmailStr, constr(max_length=0)]
    questions: Any


class Question(BaseModel):
    id: int
    tab_id: int
    tab_title: Optional[str]
    tab_description: Optional[str]
    section_id: int
    section_title: Optional[str]
    filterable: bool
    status: Optional[bool]
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

def clean_participants_cache():
    session.remove_expired_responses(expire_after=0)

def matchingKeys(dictionary, searchString):
    return [key for key,val in dictionary.items() if any(searchString in s for s in val)]

def get_active_user(request: Request) -> User:
    if "x-oauth-email" not in request.headers:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect email or password",
        )

    email = request.headers["x-oauth-email"]
    roles = matchingKeys(settings.scoutview_roles, request.headers["x-oauth-email"])
    print(email, roles)
    user = User(
        email=email,
        roles = roles
    )
    return user

settings = Settings()
print(settings.scoutview_roles)

app = FastAPI(reload=True)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8080", "http://localhost:3000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/info")
async def info(request: Request, user: User = Depends(get_active_user)):
    headers = request.headers
    return {
        "app_name": settings.app_name,
        "activity": settings.scoutnet_activity_id,
        "headers": headers,
        "user": user
    }

@app.get("/participants", response_model=Page[Participant])
def participants(form: Optional[int] = None, q: Optional[Union[int, str]] = None, q_val: Optional[Union[int, str]] = None):

    def q_filter(q, q_val, x):
        if isinstance(q, str) and x[q].upper().startswith(q_val.upper()):
            return True
        else:
            return str(q) in x['questions'] and x['questions'][str(q)] == str(q_val)

    qualifier = None
    if form == 5085: # deltagare
        qualifier = "24549"
    elif form == 5734: # ist
        qualifier = "25654"
    elif form == 6676: # ledare
        qualifier = "31490"
    elif form == 7126: # cmt
        qualifier = "34445"
    else: # unknown form (yet)
        return paginate([])

    p = get_participants()
    p = list(filter(lambda x: qualifier in x['questions'], p))
    if (q and q != 0):
        p = list(filter(lambda x: q_filter(q, q_val, x), p))
    p = sorted(p, key=lambda x : f"{x['registration_date']} {x['member_no']}")
    return paginate(p)

@app.get("/questions", response_model=List[Question])
def questions(form_id: int, user: User = Depends(get_active_user)) -> Dict[int, Question]:
    url = f'{settings.scoutnet_base}/project/get/questions?id={settings.scoutnet_activity_id}&key={settings.scoutnet_questions_key}&form_id={form_id}'
    print(f'Fetching: {url}')
    r = session.get(url)
    data = json.loads(r.text)['questions']
    tabs = data['tabs']
    sections = data['sections']
    status_tabs = [v['id'] for (_,v) in data['tabs'].items() if v['title'] == 'Status']
    health_tabs = [v['id'] for (_,v) in data['tabs'].items() if v['title'] == 'Medicinsk information']
    del data['tabs']
    del data['sections']
    questions = []
    health_access = user.has_role('health')
    for id, v in data.items():
        if (v['tab_id'] in health_tabs) and not health_access: continue
        questions.append(dict({
            'id': id,
            'status': True if (v['tab_id'] in status_tabs) else False,
            'filterable': v['type'] == 'choice',
            'tab_title': tabs[str(v['tab_id'])]['title'] if str(v['tab_id']) in tabs else '',
            'tab_description': tabs[str(v['tab_id'])]['description'] if str(v['tab_id']) in tabs else '',
            'section_title': sections[str(v['section_id'])]['title'] if str(v['section_id']) in sections else '',
            }, **v))
    questions = sorted(questions, key=lambda x : f"{x['tab_id']} {x['section_id']}")
    return questions

@app.get("/forms", response_model=Dict[int, str])
def forms() -> Dict[int, str]:
    url = f'{settings.scoutnet_base}/project/get/questions?id={settings.scoutnet_activity_id}&key={settings.scoutnet_questions_key}'
    print(f'Fetching: {url}')
    r = session.get(url)
    data = json.loads(r.text)['forms']
    # print(data)
    res = {key: value['title'] for (key, value) in data.items()}
    # print(res)
    return res

@app.post("/update_status", response_model=bool)
def update_status(member_no: int, answers: Dict[int, Union[int, str]]) -> bool:
    url = f'{settings.scoutnet_base}/project/checkin?id={settings.scoutnet_activity_id}&key={settings.scoutnet_checkin_key}'
    ans = {k:{'value': v} for (k,v) in answers.items()}
    body = {str(member_no): {'questions': ans}}
    print(f'Posting {body} to {url}')
    r = session.post(url, json.dumps(body), headers={'Content-Type': 'application/json'})
    data = json.loads(r.text)
    clean_participants_cache()
    print(data)
    return r.ok

add_pagination(app)

# Place After All Other Routes
@app.get("/")
async def redirect():
    return RedirectResponse(url=f"/client/", status_code=303)

client_dir = Path(__file__).parent.resolve() / 'client'


templates = Jinja2Templates(directory=client_dir.as_posix())
@app.get("/client/{full_path:path}")
async def catch_all(request: Request, full_path: str):
    return templates.TemplateResponse("index.html", {"request": request})

app.mount('/', StaticFiles(directory=client_dir, html=True), name="static")

@app.middleware("http")
async def debug_user(request: Request, call_next):
    if settings.scoutview_debug_email:
        request.headers.__dict__["_list"].append(
            (
                'x-oauth-email'.encode(), settings.scoutview_debug_email.encode()
            )
        )
    response = await call_next(request)
    return response
