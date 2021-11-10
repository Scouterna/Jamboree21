from flask import Flask, render_template
from datetime import date, datetime
import requests,json
app = Flask(__name__)

weather_symb = {1: "Klart", 2: "Lätt molnighet", 3: "Halvklart", 4: "Molnigt", 5: "Mycket moln", 6: "Mulet",
                    7: "Dimma", 8: "Lätt regnskur", 9: "Regnskur", 10: "Kraftig regnskur", 11: "Åskskur",
                    12: "Lätt by av regn och snö", 13: "By av regn och snö", 14: "Kraftig by av regn och snö",
                    15: "Lätt snöby", 16: "Snöby", 17: "Kraftig snöby", 18: "Lätt regn", 19: "Regn",
                    20: "Kraftigt regn", 21: "Åska", 22: "Lätt snöblandat regn", 23: "Snöblandat regn",
                    24: "Kraftigt snöblandat regn", 25: "Lätt snöfall", 26: "Snöfall", 27: "Ymnigt snöfall"}
ts_values = [0,1,4,7,12, 24]

def tod_string():
    hour = datetime.now().time().hour
    if hour >= 6 and hour <= 8:
        return "morgon"
    elif hour >= 9 and hour <= 11:
        return "förmiddag"
    elif hour >= 12 and hour <= 16:
        return "eftermiddag"
    elif hour >= 17 and hour <= 22:
        return "kväll"
    else:
        return "natt"

def wind_dir(n):

    if n > 337.5 or n <= 22.5:
        return "N"
    elif n < 22.5 and n <= 67.5:
        return "NE"
    elif n < 67.5 and n <= 112.5:
        return "E"
    elif n < 112.5 and n <= 157.5:
        return "SE"
    elif n < 157.5 and n <= 202.5:
        return "S"
    elif n < 202.5 and n <= 247.5:
        return "SW"
    elif n < 247.5 and n <= 292.5:
        return "W"
    elif n < 292.5 and n <= 337.5:
        return "NW"
    else:
        return "RO"

def get_weather():
    url = "https://opendata-download-metfcst.smhi.se/api/category/pmp3g/version/2/geotype/point/lon/14.141990/lat/55.991130/data.json"
    data = json.loads(requests.get(url).content)
    ts = []
    for i in ts_values:
        ts.append(data['timeSeries'][i])
    result = []
    for t in ts:
        result.append({
            'time': t['validTime'][11:13],
            'T': next(item for item in t['parameters'] if item["name"] == "t")['values'][0],
            'ws': next(item for item in t['parameters'] if item["name"] == "ws")['values'][0],
            'wd': wind_dir(next(item for item in t['parameters'] if item["name"] == "wd")['values'][0]),
            'Wsymb2': int(next(item for item in t['parameters'] if item["name"] == "Wsymb2")['values'][0])
        })
    return result

@app.route("/")
def index():
    return render_template("index.html", 
    today=date.today().strftime("%Y-%m-%d"),
    tod_string=tod_string(),
    weather = get_weather(),
    weather_symb = weather_symb)

if __name__ == '__main__':
    app.run(debug = True)