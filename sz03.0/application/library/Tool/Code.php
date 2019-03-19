<?php
class Tool_Code
{
    //密钥
    static function getCodeSecretKey($type=1, $encode=true)
    {
        $kMap = array(
            array(
                '0'=>'8',
                '1'=>'3',
                '2'=>'7',
                '3'=>'9',
                '4'=>'6',
                '5'=>'4',
                '6'=>'1',
                '7'=>'2',
                '8'=>'5',
                '9'=>'0',
            ),
            array(
                '0'=>'8',
                '1'=>'yp',
                '2'=>'7',
                '3'=>'9',
                '4'=>'a',
                '5'=>'6',
                '6'=>'c',
                '7'=>'2',
                '8'=>'w',
                '9'=>'bx',
            ),
        );

        if($encode)
        {
            return $kMap[$type];
        }
        return array_flip($kMap[$type]);
    }

    //int型id加密
    static function idEncode($id, $prefix='', $type=1)
    {
        return self::dealCode($id, $prefix, $type, true);
    }

    //int型id解密
    static function idDecode($id, $prefix='', $type=1)
    {
        return self::dealCode($id, $prefix, $type, false);
    }

    static function dealCode($id, $prefix='', $type, $encode, $minLen=8)
    {
        if($encode)
        {
            $id = sprintf('%0'.$minLen.'d', $id);

        }
        else
        {
            $id = str_replace($prefix, '', $id);
        }
        
        $sKey = self::getCodeSecretKey($type, $encode);
        $len = strlen($id);

        $newOne = '';
        for($i=0; $i<$len; $i++)
        {
            $newOne .= isset($sKey[$id[$i]])?$sKey[$id[$i]]:(isset($sKey[$id[$i].$id[$i+1]])?$sKey[$id[$i++].$id[$i]]:'');
        }
        //  var_dump($newOne);die;
        if($encode)
        {
            $newOne = $prefix.$newOne;
        }
        else
        {
            $newOne = intval($newOne);
        }
        return $newOne;
    }


    static function getOver16Str()
    {
        return array('g','h','i','j','k','l','m','n','o','p','q','r','x','t','u','v','w','x','y','z');
    }

    static function id32Encode($id)
    {
        $id = self::idEncode($id, '', 0);
        $idLen = strlen($id);
        if($idLen>14)
        {
            return false;
        }

        $str = substr(md5(uniqid()), $idLen+3);
        $startIndex = rand(0, 8);

        $outStr = substr($str, 0, $startIndex);
        for($i=0; $i<$idLen; $i++)
        {
            $outStr .= $id{$i} . (isset($str{$startIndex+$i})?$str{$startIndex+$i}:'');
        }
        
        $sign = self::getOver16Str();
        $outStr .= substr($str, $startIndex+$idLen).base_convert($idLen, 10, 32) . $startIndex . $sign[rand(0, count($sign)-1)];
        return $outStr;
    }

    static function id32Decode($str)
    {
        if(!in_array(substr($str, -1), self::getOver16Str()))
        {
            return false;
        }
        $startIndex = substr($str, -2, 1);
        $idLen = base_convert(substr($str, -3, 1), 32, 10);
        $id = '';
        $j = 0;
        for($i=$startIndex; $i<32; $i+=2)
        {
            if($j++>=$idLen)
            {
                break;
            }
            $id .= $str{$i};
        }
        
        if(!$id || strlen($id)!=$idLen)
        {
            $id = false;
        }

        $id = self::idDecode($id, '', 0);

        return $id;
    }
}