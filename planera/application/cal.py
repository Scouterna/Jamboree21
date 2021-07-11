from O365 import Account, FileSystemTokenBackend
import datetime as dt
from dateutil.relativedelta import relativedelta

credentials = ('b791f64f-5373-4418-be01-2c41bd06fb08','')
acc = Account(credentials)
redirect_uri = "http://localhost:5000"
scope = ["basic", "User.Read", "Calendars.Read"]


def get_cal(tok_file):
    tok_be = FileSystemTokenBackend(token_filename=tok_file)
    print( acc.is_authenticated )
    sched = acc.schedule()
    calendar = sched.get_default_calendar()
    q = calendar.new_query('start').greater_equal(dt.datetime.today())
    q.chain('and').on_attribute('end').less_equal(dt.datetime.today() + relativedelta(months = 1))
    events = calendar.get_events(query=q,include_recurring=True)
    evlist = []
    for event in events:
        evlist.append(event)

    evlist.sort(key=lambda x: x.start)

    return evlist
