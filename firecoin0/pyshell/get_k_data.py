#!/usr/bin/python
# -*- coding: utf-8 -*-
import pika
import sys
import time
import threading
from multiprocessing import Process, Pool
import redis
import pymysql
import json
import os
import time
import pandas as pd
import re
import ConfigParser
reload(sys)
sys.setdefaultencoding('utf-8')
conf = ConfigParser.ConfigParser()
conf.read("mq_server.conf")
sql = """select price,num,addtime from weike_trade_log where market = 'eos_cny'"""
conn=pymysql.connect(host=conf.get('db','host'),port=int(conf.get('db','port')),user=conf.get('db','user'),passwd=conf.get('db','passwd'),db=conf.get('db','db'),use_unicode=True, charset=conf.get('db','charset'))
data = pd.read_sql(sql=sql,con = conn)
conn.close()
print(data)