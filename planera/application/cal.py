from O365 import Account
import datetime as dt

credentials = ('b791f64f-5373-4418-be01-2c41bd06fb08','pkXwg--tSJ-01OhkrMot30-5~U_yX_g_33')
acc = Account(credentials)
redirect_uri = "http://localhost:5000"
scope = ["basic", "User.Read", "Calendars.Read"]


def get_cal():
    acc.authenticate(scopes = scope)
    sched = acc.schedule()
    calendar = sched.get_default_calendar()
    q = calendar.new_query('start').greater_equal(dt.datetime.today())
    q.chain('and').on_attribute('end').less_equal(dt.datetime(2022, 5, 24))
    events = calendar.get_events(query=q,include_recurring=True)

    for event in events:
        print(event.subject)
