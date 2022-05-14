import requests, json, datetime, os, schedule, time
from datetime import datetime

header = {
    "Content-Type":"application/json",
}

uri = os.environ["webhook_url"]

def sendhook():
    print("Welcome to sendhook")
    j = json.loads(requests.get("http://localhost:5000/?d=1", headers={"secret": os.environ["wikiupdate_api_secret"]}).text)

    out = {
        "@type": "MessageCard",
        "@context": "https://schema.org/extensions",
        "summary": "Summary",
        "title": "Ändringar på wikin senaste dygnet",
        "sections": []
    }

    out["sections"].append(
            {
                "text": ("<pre><b>" + "Författare".ljust(25) + "Artikelns namn".ljust(30) + "Länk till ändringen".ljust(20) +  "Senast ändrad".rjust(20) + "</b></pre>")
            }
        )

    for entry in j["entries"]:
        t = datetime.strptime(entry["updated"], "%Y-%m-%dT%H:%M:%SZ")
        out["sections"].append(
            {
                "text": ("<pre>" + entry["author"].ljust(25) + entry["title"].ljust(30) + "<a href=" + entry["link"]+ ">Se differens</a>".ljust(20) +  datetime.strftime(t, "%H:%M:%S, %d %b").rjust(20) + "</pre>")
            }
        )

    r  = requests.post(uri, data=json.dumps(out), headers=header)

    print(r.status_code)
    #print(str(r.headers))
    print(r.text)


def main():
    print("Hello from thread!")
    schedule.every().day.at("10:15").do(sendhook)  #Time in UTC
    schedule.every().day.at("12:15").do(sendhook)  #Time in UTC
    schedule.every().day.at("11:55").do(sendhook)  #Time in UTC
    while True:
        try:
            schedule.run_pending()
        except requests.exceptions.ConnectionError:
            print("Conn refused, trying in 60s")
            continue
        
        time.sleep(1)
