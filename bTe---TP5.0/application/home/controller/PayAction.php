<?php
class PayAction extends Action{
    //支付跳转
    function do_pay(){
        //构造参数
        $payment = array(
            'money'=>10,
            'deal_name'=>'充值',
            //支付银行，对应银行ID我在后面贴出来
            'bank_id'=>'10052',

            'notice_sn'=>'20140920123456',
        );
        echo DB::name('mycz')->get_payment_code($payment);
    }

    //支付结果同步回调
    function response(){
        $request = $_GET;
        unset($request['_URL_']);
        $pay_res = DB::name('mycz')->notify($request);
        if($pay_res['status']){
            //支付成功业务逻辑
        }else{
            $this->error('支付失败');
        }
    }

    //支付结果异步回调
    function notify(){
        $request = $_POST;
        $pay_res = DB::name('mycz')->notify($request);
        if($pay_res['status']){
            //支付成功业务逻辑
            echo 'success';
        }else{
            echo 'fail';
        }
    }
}
//    //银行ID
//    $bank_id = array(
//        'ICBCB2C'    =>    '中国工商银行',
//        'CMB'        =>    '招商银行',
//        'CCB'        =>    '中国建设银行',
//        'ABC'        =>    '中国农业银行',
//        'SPDB'        =>    '上海浦东发展银行',
//        'SDB'        =>    '深圳发展银行',
//        'CIB'        =>    '兴业银行',
//        'BJBANK'    =>    '北京银行',
//        'CEBBANK'    =>    '中国光大银行',
//        'CMBC'        =>    '中国民生银行',
//        'CITIC'        =>    '中信银行',
//        'GDB'        =>    '广东发展银行',
//        'SPABANK'    =>    '平安银行',
//        'BOCB2C'    =>    '中国银行',
//        'COMM'        =>    '交通银行',
//        'ALIPAY'    =>    '支付宝',
//    );
?>