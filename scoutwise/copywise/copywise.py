
import requests
import pandas as pd
import OpenSSL
from OpenSSL import crypto
import base64
import sqlalchemy as sa
import os
from dotenv import load_dotenv
from datetime import date

load_dotenv()

def df_upsert(data_frame, table_name, engine, schema=None, match_columns=None):
    """
    Perform an "upsert" on a PostgreSQL table from a DataFrame.
    Constructs an INSERT … ON CONFLICT statement, uploads the DataFrame to a
    temporary table, and then executes the INSERT.
    Parameters
    ----------
    data_frame : pandas.DataFrame
        The DataFrame to be upserted.
    table_name : str
        The name of the target table.
    engine : sqlalchemy.engine.Engine
        The SQLAlchemy Engine to use.
    schema : str, optional
        The name of the schema containing the target table.
    match_columns : list of str, optional
        A list of the column name(s) on which to match. If omitted, the
        primary key columns of the target table will be used.
    """
    table_spec = ""
    if schema:
        table_spec += '"' + schema.replace('"', '""') + '".'
    table_spec += '"' + table_name.replace('"', '""') + '"'

    df_columns = list(data_frame.columns)
    if not match_columns:
        insp = sa.inspect(engine)
        match_columns = insp.get_pk_constraint(table_name, schema=schema)["constrained_columns"]
    columns_to_update = [col for col in df_columns if col not in match_columns]
    insert_col_list = ", ".join([f'"{col_name}"' for col_name in df_columns])
    stmt = f"INSERT INTO {table_spec} ({insert_col_list})\n"
    stmt += f"SELECT {insert_col_list} FROM temp_table\n"
    match_col_list = ", ".join([f'"{col}"' for col in match_columns])
    stmt += f"ON CONFLICT ({match_col_list}) DO UPDATE SET\n"
    stmt += ", ".join([f'"{col}" = EXCLUDED."{col}"' for col in columns_to_update])

    with engine.begin() as conn:
        conn.exec_driver_sql("DROP TABLE IF EXISTS temp_table")
        conn.exec_driver_sql(f"CREATE TEMPORARY TABLE temp_table AS SELECT * FROM {table_spec} WHERE false")
        data_frame.to_sql("temp_table", conn, if_exists="append", index=False)
        conn.exec_driver_sql(stmt)

if __name__ == "__main__":
    df = pd.DataFrame()
    engine = sa.create_engine(f"postgresql://postgres:{os.getenv('DB_PASSWORD')}@db:5432/scoutwise")

    cardholders_unit_name = {};
    cardholders_unit_id = {};
    cardholders_name = {};

    with engine.connect() as con:
        resproxy = con.execute('SELECT * FROM cardholders')
        for row in resproxy:
            cardholders_unit_name[row.cardname] = row.unit_name
            cardholders_unit_id[row.cardname] = row.unit_id
            cardholders_name[row.cardname] = row.name

    category_key = {};

    with engine.connect() as con:
        resproxy = con.execute('SELECT * FROM cardcategories')
        for row in resproxy:
            category_key[row.merchant_category] = row.accounting_category

    w_apitoken = os.getenv('WISE_APITOKEN')
    w_profileid = os.getenv('WISE_PROFILEID')
    w_accountid = os.getenv('WISE_ACCOUNTID')

    startdate = "2022-03-01"
    today = date.today()

    url = f"https://api.transferwise.com/v1/profiles/{w_profileid}/balance-statements/{w_accountid}/statement.json?intervalStart={startdate}T00:00:00.000Z&intervalEnd={today}T23:59:59.999Z&type=COMPACT"

    headers = {"Authorization" : "Bearer "+w_apitoken}
    response = requests.get(url, headers=headers)

    if response.status_code == 403:
        x2fa = str.encode(response.headers["x-2fa-approval"])
        key = os.getenv('PRIVATE_KEY')

        pkey = crypto.load_privatekey(crypto.FILETYPE_PEM, key)
        sign = OpenSSL.crypto.sign(pkey, x2fa, "sha256")
        sign_base64 = base64.b64encode(sign)

        headers = {"Authorization" : "Bearer "+w_apitoken, "X-2FA-Approval" : x2fa, "X-Signature" : sign_base64}
        response2 = requests.get(url, headers=headers)

        data = response2.json()

        for transaction in data['transactions']:
            if transaction['details']['type'] == "CARD" or "CARD_ORDER_CHECKOUT" in transaction['referenceNumber']:
                d = dict()

                try:
                    d['unique_reference'] = transaction['referenceNumber']+"_"+transaction['date']
                except:
                    d['unique_reference'] = ""
                try:
                    d['type'] = transaction['type']
                except:
                    d['type'] = ""
                try:
                    d['date'] = transaction['date']
                except:
                    d['date'] = ""
                try:
                    d['amount_sek'] = transaction['amount']['value']
                except:
                    d['amount_sek'] = 0.0
                try:
                    d['fees'] = transaction['totalFees']['value']
                except:
                    d['fees'] = 0.0
                try:
                    d['amount_local'] = transaction['details']['amount']['value']
                except:
                    d['amount_local'] = 0.0
                try:
                    d['local_currency'] = transaction['details']['amount']['currency']
                except:
                    d['local_currency'] = ""
                try:
                    d['merchant'] = transaction['details']['merchant']['name']
                except:
                    d['merchant'] = ""
                try:
                    d['merchant_category'] = transaction['details']['merchant']['category']
                except:
                    d['merchant_category'] = ""
                try:
                    d['city'] = transaction['details']['merchant']['city']
                except:
                    d['city'] = ""
                try:
                    d['country'] = transaction['details']['merchant']['country']
                except:
                    d['country'] = ""
                try:
                    d['cardholder'] = transaction['details']['cardHolderFullName']
                except:
                    d['cardholder'] = ""
                try:
                    d['to_amount'] = transaction['exchangeDetails']['toAmount']['value']
                except:
                    d['to_amount'] = 0.0
                try:
                    d['to_amount_currency'] = transaction['exchangeDetails']['toAmount']['currency']
                except:
                    d['to_amount_currency'] = ""
                try:
                    d['from_amount'] = transaction['exchangeDetails']['fromAmount']['value']
                except:
                    d['from_amount'] = 0.0
                try:
                    d['from_amount_currency'] = transaction['exchangeDetails']['fromAmount']['currency']
                except:
                    d['from_amount_currency'] = ""
                try:
                    d['exchangerate'] = transaction['exchangeDetails']['rate']
                except:
                    d['exchangerate'] = 1.0
                try:
                    d['reference'] = transaction['referenceNumber']
                except:
                    d['reference'] = ""
                try:
                    d['attachment'] = transaction['attachment']['downloadLink']
                except:
                    d['attachment'] = ""
                try:
                    d['note'] = transaction['attachment']['note']
                except:
                    d['note'] = ""
                try:
                    d['unit_name'] = cardholders_unit_name[d['cardholder']]
                except:
                    d['unit_name'] = "Unknown"
                try:
                    d['unit_id'] = cardholders_unit_id[d['cardholder']]
                except:
                    d['unit_id'] = 0
                try:
                    d['name'] = cardholders_name[d['cardholder']]
                except:
                    d['name'] = d['cardholder']
                try:
                    d['accounting_category'] = category_key[d['merchant_category']]
                except:
                    d['accounting_category'] = "6590 Okategoriserat"

                if d['attachment'] != "":
                    d['missingreceipt'] = 0
                else:
                    d['missingreceipt'] = 1

                if "CARD_ORDER_CHECKOUT" in transaction['referenceNumber']:
                    d['missingreceipt'] = 0
                    d['accounting_category'] = "6570 Bankkostnader"
                    d['unit_name'] = "CMT Admin"
                    d['unit_id'] = "232000"
                    d['cardholder'] = "CMT Admin"
                    d['name'] = "CMT Admin"
                    d['note'] = "Avgift Wisekort"


                df = df.append(d, ignore_index=True)

    df = df.loc[df['cardholder'] != "Peter Bernt Gustafsson"]

    df_upsert(df, "cardtransactions", engine)
