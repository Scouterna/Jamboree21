from flask import Flask, render_template
import os, json
from datetime import date, datetime
from io import StringIO
from html.parser import HTMLParser
import cal

app = Flask(__name__)

class MLStripper(HTMLParser):
    def __init__(self):
        super().__init__()
        self.reset()
        self.strict = False
        self.convert_charrefs= True
        self.text = StringIO()
    def handle_data(self, d):
        self.text.write(d)
    def get_data(self):
        return self.text.getvalue()

def strip_tags(html):
    s = MLStripper()
    s.feed(html)
    return s.get_data()

@app.route('/', methods=['GET'])
def index():
    diff = daysleft(date(2022, 7, 31))
    j = json.loads(open("data.json", 'r').read())
    s = jsontohtml(j)
    now = datetime.strftime(datetime.now(), "%Y-%m-%d, %H:%M")
    events = cal.get_cal()

    return render_template("index.html", jmb_name = "Jamboree22", daysleft=diff.days, now=now, wikilatest=s, events = events)


def jsontohtml(j):
    out = "<table>"
    out += "<tr><th>Författare</th><th>Artiklens namn</th><th>Länk till ändringen</th><th>Sammanfattning</th></tr>"

    for entry in j['entries']:
        out += f"<tr><td>{ entry['author'] }</td><td>{ entry['title'] }</td><td><a target='_blank' href='{ entry['link'] }'>Länk</a></td><td>{ strip_tags(entry['summary']) }</td></tr>"

    out += "</table>"

    return out

def daysleft(d2):
    td = date.today()
    return d2 - td

def main():
    app.run()

if __name__ == "__main__":
    main()
    