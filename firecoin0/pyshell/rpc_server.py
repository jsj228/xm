#!/usr/bin/python
# -*- coding: utf-8 -*-
from multiprocessing import Process, Pool
import threading
import ConfigParser
import pika
import json
import pandas as pd
import pymysql
import re
import redis
import os
import time
import sys
reload(sys)
sys.setdefaultencoding('utf-8')
class RpcServer:
    def __init__(self,pid):
        self.pid = pid
        self.conf = ConfigParser.ConfigParser()
        self.conf.read("mq_server.conf")
        credentials = pika.PlainCredentials(self.conf.get('mq','user'),self.conf.get('mq','pass'))
        conn_param = pika.ConnectionParameters(self.conf.get('mq','host'),credentials=credentials)
        connection = pika.BlockingConnection(conn_param)
        self.channel = connection.channel()
        self.channel.queue_declare(queue='rpc_queue')
        self.channel.basic_qos(prefetch_count=1)
        self.channel.basic_consume(self.on_request, queue='rpc_queue')
        self.channel.start_consuming()

    ##接收参数，调用处理，回复消息
    def on_request(self,ch, method, props, body):
        response = self.pid
        ch.basic_publish(exchange='',
                         routing_key=props.reply_to,
                         properties=pika.BasicProperties(correlation_id = props.correlation_id),
                         body=str(response))
        ch.basic_ack(delivery_tag = method.delivery_tag)
        self.fib(body)


    ##处理参数并返回结果
    def fib(self,n):
        #'{"option":"4305,4304,4395,4394,4393,4403,4402,4401,4400","total":"0","ad min":"Khz2muwrNPRkeQn","table":"weike_myzr"}'
        param = json.loads(n)
        r = redis.Redis(host=self.conf.get('redis','host'), port=6379,db=0,password=self.conf.get('redis','password'))
        try:
            d = pd.DataFrame(json.loads(r.get(param['redis'])))
            time.sleep(5)
            d.set_index(d['id'],inplace=True)
            d = d.T.apply(self.default_format).T
            d = self.data_format_total(d)
            d = d.groupby(['date_time']).apply(self.data_format)
            columns = ['username','addtime','mum','num','czr','endtime','status','type','beizhu']
            #d.rename(columns={'username': u'用户名', 'addtime':u'下单时间','mum': u'实际到账', 'num': u'订单金额', 'czr': u'操作人', 'endtime': '操作时间','status':u'订单状态','type':u'支付方式','beizhu':u'备注'}, inplace=True) 
            #columns = [u'用户名',u'下单时间',u'实际到账',u'订单金额',u'操作人','操作时间',u'订单状态',u'支付方式',u'备注']
            d.to_csv("%s%s_%s.csv" % (self.conf.get('root','csv_root'),param['admin'],self.pid),index=True,sep=',',encoding = "gbk",columns=columns)#
            return True
            #json.dumps({'file_root':'%s%s.csv' % (self.conf.get('root','csv_http'),param['admin'])})
        except Exception as e:
            return json.dumps({'file_root':'%s%s.csv' % (self.conf.get('root','csv_http'),param['admin'])})

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


    def default_format(self,df):
        rex=re.compile('^([^\s]+)\s+')
        df['date_time'] = rex.match(df['addtime']).group(1)
        return df

def run():
    pid = os.getpid()
    RpcServer(pid)


if __name__ == '__main__':
    ps = [Process(target=run, args=()) for i in xrange(5)]
    for p in ps:
        p.start()


