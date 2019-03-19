<?php
class Api_Rpc_Server{
	public static function handle($object){
		if($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/json'){
			return false;
		}
		$request = json_decode(file_get_contents('php://input'), true);
		try{
			if($result = @call_user_func_array(array($object, $request['method']), $request['params'])){
				$response = array('id' => $request['id'], 'result' => $result, 'error' => null);
			}
			else{
				$response = array('id' => $request['id'], 'result' => null, 'error' => 'unknown method or incorrect parameters');
			}
		} catch(Exception $e){
			$response = array('id' => $request['id'], 'result' => null, 'error' => $e->getMessage());
		}
		if(!empty($request['id'])){
			header('content-type: text/javascript');
			echo json_encode($response);
		}
		return true;
	}
}
?>
