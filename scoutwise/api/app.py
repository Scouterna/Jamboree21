from flask import Flask, request, jsonify, make_response
from flask_restful import Resource, Api
from flask_cors import CORS, cross_origin
import sqlalchemy as sa
from sqlalchemy import create_engine, MetaData, Table, Column, Integer, String, Float, Boolean, DateTime, update, select, insert
from sqlalchemy.orm import sessionmaker
import json
import hashlib

from flask_jwt_extended import create_access_token
from flask_jwt_extended import get_jwt_identity
from flask_jwt_extended import jwt_required
from flask_jwt_extended import JWTManager

import decimal, datetime
import os
from dotenv import load_dotenv

load_dotenv(dotenv_path='../.env')

def alchemyencoder(obj):
	"""JSON encoder function for SQLAlchemy special classes."""
	if isinstance(obj, datetime.date):
		return obj.isoformat()
	elif isinstance(obj, decimal.Decimal):
		return float(obj)

app = Flask(__name__)
app.config["JWT_SECRET_KEY"] = os.getenv('JWT_SECRET_KEY')
jwt = JWTManager(app)

CORS(app, support_credentials=True)
api = Api(app)

engine = create_engine(
	f"postgresql://postgres:{os.getenv('DB_PASSWORD')}@db:5432/scoutwise",
	echo=True
)

Session = sessionmaker(bind = engine)
session = Session()

meta = MetaData(bind=engine)

cardtransactions = Table('cardtransactions', meta, autoload=True)
cardholders = Table('cardholders', meta, autoload=True)
cardcategories = Table('cardcategories', meta, autoload=True)
users = Table('users', meta, autoload=True)

@app.route('/update', methods=['POST'])
def updateRow():
	data = json.loads(request.data)
	id = data['row']['id']
	newRow = data['row']
	del newRow['id']

	if (data['table'] == "cardtransactions"):
		tablename = cardtransactions;

	if (data['table'] == "cardholders"):
		tablename = cardholders;

	if (data['table'] == "cardcategories"):
		tablename = cardcategories;

	with engine.connect() as con:
		u = update(tablename).where(tablename.c.id == id).values(newRow)
		con.execute(u)
	return 1

@app.route('/transactions', methods=['GET','OPTIONS'])
#@cross_origin(supports_credentials=True)
@jwt_required()
def getTransactions():
	with engine.connect() as con:
		res = con.execute('SELECT * FROM cardtransactions order by date desc')
		retdata = json.dumps([dict(r) for r in res], default=alchemyencoder)
		return json.loads(retdata)

@app.route('/cardholders', methods=['GET'])
def getCardholders():
	with engine.connect() as con:
		res = con.execute('select * from cardholders order by unit_id asc')
		retdata = json.dumps([dict(r) for r in res], default=alchemyencoder)
		return json.loads(retdata)

@app.route('/categories', methods=['GET'])
def getCategories():
	with engine.connect() as con:
		res = con.execute('select * from cardcategories order by merchant_category asc')
		retdata = json.dumps([dict(r) for r in res], default=alchemyencoder)
		return json.loads(retdata)

@app.route('/test', methods=['GET', 'POST'])
def test():
	return "Fungerar"

@app.route('/login', methods=['POST'])
def login():
	data = json.loads(request.data)
	form_user = data['user']
	form_pass = data['pass']
	#form_user = request.form.get("user")
	#form_pass = request.form.get("pass")
	phash = hashlib.sha1(str(form_pass).encode('utf-8')).hexdigest()
	check = session.query(users).filter(users.c.username.like(form_user),users.c.phash.like(phash)).count()

	if (check > 0):
		access_token = create_access_token(identity=form_user)
		return make_response(jsonify(access_token=access_token), 201)
	else:
		return make_response(jsonify({"message": "Wrong Credentials"}), 401)

@app.route('/validatekey', methods=['GET','POST','OPTIONS'])
@jwt_required()
def validatekey():
	return make_response(jsonify({"message": "Correct API Key"}), 200)

if __name__ == '__main__':
	app.run(debug=False, host="0.0.0.0")