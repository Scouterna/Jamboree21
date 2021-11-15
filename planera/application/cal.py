from O365 import Account, FileSystemTokenBackend
import datetime as dt
from dateutil.relativedelta import relativedelta
import os

credentials = (os.environ['MICROSOFT_GRAPH_UNAME'], os.environ['MICROSOFT_GRAPH_PASSWORD'])
acc = Account(credentials)
redirect_uri = 'https://' + os.environ['HOSTNAME']
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
