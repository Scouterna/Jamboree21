import requests, json, datetime, os, schedule, time, logging
from datetime import datetime, timedelta

header = {
    "Content-Type":"application/json",
}

uri = os.environ["webhook_url"]

class WikiConnectionError(Exception):
    pass


def sendhook():
    logging.info(f'Running sendhook, time is now {datetime.now().isoformat()}')
    logging.info("Getting updates from Mediawiki...")

    response = requests.get("http://localhost:5000/?d=0.020833", headers={"secret": os.environ["wikiupdate_api_secret"]})
    if response.status_code != 200:
        logging.error(f'got http {response.status_code} error from Mediawiki: {response.text}')
        raise WikiConnectionError

    j = json.loads(response.text)

    logging.info(f'got {len(j["entries"])} updates from Mediawiki')
    if len(j["entries"]) == 0:
           logging.info(f'got no entries from Mediawiki, returning')
           logging.info(f'Mediawiki response was: {response.text}')
           return

    out = {
        "@type": "MessageCard",
        "@context": "https://schema.org/extensions",
        "summary": "Summary",
        "title": "Ändringar på Wikin senaste 30 minuterna:",
        "sections": []
    }

    out["sections"].append(
            {
                "text": ("<pre><b>" + "Författare".ljust(25) + "Artikelns namn".ljust(30) + "Länk till ändringen".ljust(20) +  "Senast ändrad".rjust(20) + "</b></pre>")
            }
        )

    logging.info("Appending entries...")
    for entry in j["entries"]:
        t = datetime.strptime(entry["updated"], "%Y-%m-%dT%H:%M:%SZ")
        
        logging.info(f'This post is {datetime.now() - t} old.')
        if (datetime.now() - t) <= timedelta(minutes=30):
            out["sections"].append(
                {
                    "text": ("<pre>" + entry["author"].ljust(25) + entry["title"].ljust(30) + "<a href=" + entry["link"]+ ">Se differens</a>".ljust(20) +  datetime.strftime(t, "%H:%M:%S, %d %b").rjust(20) + "</pre>")
                }
            )

    logging.info(f'Length of sections is {len(out["sections"])}')
    logging.info(f'will send update: {json.dumps(out)}')

    if len(out["sections"]) > 1:
        logging.info("Sending Teams webhook...")
        r  = requests.post(uri, data=json.dumps(out), headers=header)
        logging.info("Teams webhook sent, status: ", r.status_code)


def main():
    logging.debug("Hello from thread!")
    logging.info("Scheduling Teams update to send every 30 minutes...")
    schedule.every(30).minutes.do(sendhook)
    while True:
        try:
            schedule.run_pending()
        except requests.exceptions.ConnectionError, WikiConnectionError:
            logging.error("Connection error when sending webhook, retrying in 60s")
            continue
        time.sleep(1)
