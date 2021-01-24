import requests, feedparser, json, os
from flask import Flask,request, abort

app = Flask(__name__)
# for deployment in azure-k8s
settings = {
    "mw-url": os.environ("mw-url"),
    "apisecret": os.environ("wikiupdate_api_secret"),
    "cookie": ""
}

# For local testing
# settings = []
# with open('settings.cfg', 'r') as f:
#    settings = json.load(f)

url = settings["mw-url"] + "/api.php"

headers = {
    "Host": "wiki.internal.jamboree.se.webservices.scouterna.net",
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0",
    "Accept":"text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    "Connection": "keep-alive",
    "Cookie": settings["cookie"]
}

@app.route('/', methods=['GET'])
def index():
    params = {
        "hidebots":"1",
        "urlversion":"1",
        "days":"1",
        "limit": "200",
        "action": "feedrecentchanges",
        "feedformat":"atom"
    }

    print(request.url)
    try:
        if request.headers["secret"] != settings["apisecret"]:
            abort(400)
            return "No can dosville"
    except KeyError:
            abort(401)
    
    args = request.args
    try:
        if "d" in args and float(args.get("d")) <= 30:
            params["days"] = args.get("d")
        if "l" in args and int(args.get("l")) < 200:
            params["limit"] = args.get("l")
        if "m" in args:
            params["hideminor"] = args.get("m")
        if "b" in args:
            params["hidebots"] = args.get("b")
    except (KeyError, ValueError):
        abort(400)

    r = requests.get(url, headers=headers, params=params)
    print(r.request.url)
    feed = feedparser.parse(r.text)
    return feed


if __name__ == '__main__':
    app.run()