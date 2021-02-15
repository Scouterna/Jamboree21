from flask import Flask, render_template
import os
from datetime import date, datetime
app = Flask(__name__)


@app.route('/', methods=['GET'])
def index():
    print(os.getcwd())
    diff = daysleft(date(2022, 7, 31))
    now = datetime.strftime(datetime.now(), "%Y-%m-%d, %H:%M")
    return render_template("index.html", jmb_name = "Jamboree22", daysleft=diff.days, now=now)

def daysleft(d2):
    td = date.today()
    return d2 - td

def main():
    app.run()

if __name__ == "__main__":
    main()