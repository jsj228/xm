<?php
/**
 * AES 加密
 */
class Tool_Aes
{

	public static function encrypt($input, $key, $iv)
	{
		$data = base64_encode(openssl_encrypt($input, 'AES-128-CBC', $key, OPENSSL_RAW_DATA , $iv));
		return $data;
	 }

	public static function decrypt($sStr, $sKey, $iv)
	{
		$decrypted = openssl_decrypt(base64_decode($sStr), 'AES-128-CBC', $sKey, OPENSSL_RAW_DATA , $iv);
		return $decrypted;
	}

}