<?php
/**
 *  静态资源
 */
class TestController extends Ctrl_Base
{




    //测试
    public function indexAction(){


        $lang  = [
            'cn' => 'cn.json',
            'en' => 'en.json',
            'jpne' => 'jpne.json',
            'kora' => 'kora.json'
        ];

        foreach ($lang as $k=>$v){
            $files[$k] = json_decode(file_get_contents(APPLICATION_PATH."/conf/language/global/$v"),true);

            foreach ($files[$k] as $kk=>$vv){
                $res[$kk][$k] = $vv;
            }
        }

        $i = 0;
        foreach ($res as $k=>$v){
            $data[$i]['key'] = $k;
            foreach ($v as $kk=>$vv){
                $data[$i][$kk] = $vv?:'';
            }
            $i++;
        }
        $data = array_reverse($data);
        $this->assign('data',$data);
    }


    //翻译
    public function translateAction(){

        $lang = ['en','ja','ko'];

        $key = coll('post','key');
        $text = coll('post','text');

        $res['key'] = $key;
        $res['cn'] = $text;
//        $res['en'] = $text;
//        $res['ja'] = $text;
//        $res['ko'] = $text;

        $youdao = new Tool_Youdao();
        foreach ($lang as $v){
            $res[$v] = $youdao->ydtranslate($text,$v);
        }

        $this->ajax('翻译成功',1,$res);
    }

    //添加
    public function addAction(){

        $post = coll('post');

        $lang  = [
            'cn' => 'cn.json',
            'en' => 'en.json',
            'ja' => 'jpne.json',
            'ko' => 'kora.json'
        ];

        foreach ($lang as $k=>$v){


            $file_path = APPLICATION_PATH."/conf/language/global/$v";

            $files[$k] = json_decode(file_get_contents($file_path),true);
//            $files[$k][strtoupper($post['key'])] = $post[$k];

            $key = strtoupper($post['key']);
            $val = $post[$k];

            $str = "{\n";
            foreach ($files[$k] as $kk=>$vv){
                $str .= "\"{$kk}\":\"{$vv}\",\n";
            }
            $str .= "\"{$key}\":\"{$val}\"\n";
            $str.= "}";

            file_put_contents($file_path,$str);
        }

        $this->ajax('添加成功',1,$files);
    }

    function charsetToGBK($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                if (is_array($v)) {
                    $mixed[$k] = $this->charsetToGBK($v);
                } else {
                    $encode = mb_detect_encoding($v, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                    if ($encode == 'UTF-8') { $mixed[$k] = iconv('UTF-8', 'GBK', $v);
                    }
                }
            }
        } else {
            $encode = mb_detect_encoding($mixed, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        }
        if ($encode == 'UTF-8') {
            $mixed = iconv('UTF-8', 'GBK', $mixed);
        }

        return $mixed;
    }
}

