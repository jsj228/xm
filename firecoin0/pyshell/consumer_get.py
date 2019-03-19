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
class DirectConsume:
	##queue = routing_key
	def __init__(self,queue='weike_mycz',exchange='huocoin_csv',pid=''):
		self.pid = pid
		self.conf = ConfigParser.ConfigParser()
		self.conf.read("mq_server.conf")
		credentials = pika.PlainCredentials(self.conf.get('mq','user'),self.conf.get('mq','pass'));
		conn_param = pika.ConnectionParameters(self.conf.get('mq','host'),credentials=credentials)
		connection = pika.BlockingConnection(conn_param)
		channel = connection.channel()
		channel.exchange_declare(exchange=exchange,exchange_type='direct',passive=True,durable=True)
		#exclusive=True 消费者断开后删除队列
		#声明一个已存在的队列，如果不存在将抛出异常
		channel.queue_declare(queue=queue,exclusive=False,durable=True,passive=False)
		channel.queue_bind(exchange=exchange,queue='weike_mycz',routing_key=queue)
		channel.basic_consume(self.callback,queue=queue,no_ack=False)
		channel.start_consuming()

	def callback(self,ch, method, properties, body):
		param = json.loads(body)
		#print(" [x] %s %r:%r" % (self.tid,method.routing_key, body))
		if param['total'] == '1':
			sql = """select a.id,a.id AS uid,b.username,a.num,a.mum,(CASE WHEN a.type='alipay' THEN '支付宝支付' WHEN a.type='weixin' THEN '微信支付' WHEN a.type='bank' THEN '网银支付' ELSE '其它' END ) as type, (CASE WHEN  a.addtime > 0 THEN FROM_UNIXTIME(a.addtime) ELSE '0000/00/00 00:00:00' END ) AS addtime,(CASE  WHEN  a.endtime > 0 THEN FROM_UNIXTIME(a.endtime) ELSE '0000/00/00 00:00:00' END ) AS endtime,(CASE  WHEN a.status = 0 THEN '未付款'  WHEN a.status = 1 THEN '充值成功' WHEN a.status = 2 THEN '人工到账' WHEN a.status = 3 THEN '处理中' WHEN a.status = 4 THEN '已撤销' WHEN a.status = 5 THEN '花呗到账' ELSE '其它' END ) as status,a.czr,a.beizhu from weike_mycz a left join weike_user b on a.userid=b.id"""
		elif param['total'] == '0' and param['option']:
			for i in param['option'].split(','):
				try:
					if int(i) > 0:
						tag = True
					else:
						tag = False
				except:
					tag = False
			if tag:
				sql = """select a.id,a.id AS uid,b.username,a.num,a.mum,(CASE WHEN a.type='alipay' THEN '支付宝支付' WHEN a.type='weixin' THEN '微信支付' WHEN a.type='bank' THEN '网银支付' ELSE '其它' END ) as type, (CASE WHEN  a.addtime > 0 THEN FROM_UNIXTIME(a.addtime) ELSE '0000/00/00 00:00:00' END ) AS addtime,(CASE  WHEN  a.endtime > 0 THEN FROM_UNIXTIME(a.endtime) ELSE '0000/00/00 00:00:00' END ) AS endtime,(CASE  WHEN a.status = 0 THEN '未付款'  WHEN a.status = 1 THEN '充值成功' WHEN a.status = 2 THEN '人工到账' WHEN a.status = 3 THEN '处理中' WHEN a.status = 4 THEN '已撤销' WHEN a.status = 5 THEN '花呗到账' ELSE '其它' END ) as status,a.czr,a.beizhu from weike_mycz a left join weike_user b on a.userid=b.id where a.id in (%s)""" % param['option'] 
			else:		
				return ch.basic_ack(delivery_tag = method.delivery_tag)
			
		else :
			return ch.basic_ack(delivery_tag = method.delivery_tag)
		conn=pymysql.connect(host=self.conf.get('db','host'),port=int(self.conf.get('db','port')),user=self.conf.get('db','user'),passwd=self.conf.get('db','passwd'),db=self.conf.get('db','db'),use_unicode=True, charset=self.conf.get('db','charset'))
		data = pd.read_sql(sql=sql,con = conn)
		conn.close()
		data.set_index(data['id'],inplace=True)
		data = data.T.apply(self.default_format).T
		data = self.data_format_total(data)
		data = data.groupby(['date_time']).apply(self.data_format)
		columns = ['username','addtime','mum','num','czr','endtime','status','type','beizhu']
		data.to_csv("%s%s_%s.csv" % (self.conf.get('root','csv_root'),param['admin'],self.pid),index=True,sep=',',encoding = "gbk",columns=columns)
		r = redis.Redis(host=self.conf.get('redis','host'), port=int(self.conf.get('redis','port')),db=0,password=self.conf.get('redis','password'))
		r.set('%s_weike_mycz' % param['admin'],"%s%s_%s.csv" % (self.conf.get('root','csv_root'),param['admin'],self.pid))
		return ch.basic_ack(delivery_tag = method.delivery_tag)

	def data_format(self,df):
		smum = df['mum'].sum()
		snum = df['num'].sum()
		df.drop(['uid','date_time','id'],axis=1, inplace=True)
		tlist = list(df.columns.values)
		data = {}
		for t in tlist:
			if t == 'mum':
				data[t] = smum
			elif t == 'num':
				data[t] = snum
			else:
				data[t] = '----'
		d = pd.DataFrame([data],index=['--'])
		#df.sort_index(axis = 0,ascending = True,by = 'uid')
		return pd.concat([df,d])

	def default_format(self,df):
		rex=re.compile('^([^\s]+)\s+')
		df['date_time'] = rex.match(df['addtime']).group(1)
		return df

	def data_format_total(self,df):
		smum = df['mum'].sum()
		snum = df['num'].sum()
		tlist = list(df.columns.values)
		data = {}
		for t in tlist:
			if t == 'mum':
				data[t] = smum
			elif t == 'num':
				data[t] = snum
			else:
				data[t] = '****'
		d = pd.DataFrame([data],index=['**'])
		#df.sort_index(axis = 0,ascending = True,by = 'uid')
		return pd.concat([df,d])

def run(queue,exchange):
	pid = os.getpid()
	DirectConsume(queue,exchange,pid)

if __name__ == '__main__':
	#ts = [threading.Thread(target=run, args=('weike_mycz','huocoin_csv')) for i in xrange(5)]
	ps = [Process(target=run, args=('weike_mycz','huocoin_csv')) for i in xrange(5)]
	for p in ps:
		p.start()