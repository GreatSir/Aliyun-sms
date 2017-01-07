<?php
class Aliyunsms{
	private $app_key;//应用ID
	private $secret_Key;//应用密钥
	//系统公共参数
	/*private $system = array(
		'v'          => '2.0',
		'format'     => 'json',
		'signMethod' => 'md5',
		'method'     => 'alibaba.aliqin.fc.sms.num.send'
	);*/
	public $gatewayUrl = "http://gw.api.taobao.com/router/rest";//接口地址
	protected $method ='alibaba.aliqin.fc.sms.num.send';//接口方法名称
	public $format = "json";//响应格式
	protected $signMethod = "md5";//签名的摘要算法
	protected $apiVersion = "2.0";//API协议版本
	public $sysParams = array();//共用参数
	public $appParams = array();//应用参数
	public function __construct($app_key = "",$secret_Key = ""){
		$this->app_key = $app_key;
		$this->secret_Key = $secret_Key ;
	}
	protected function getSign($params)
	{
		ksort($params);

		$stringToBeSigned = $this->secret_Key;
		foreach ($params as $k => $v)
		{
			if(is_string($v) && "@" != substr($v, 0, 1))
			{
				$stringToBeSigned .= "$k$v";
			}
		}
		unset($k, $v);
		$stringToBeSigned .= $this->secret_Key;

		return strtoupper(md5($stringToBeSigned));
	}
	//设置短信接受号码
	public function set_mobile($mobiel=''){
		$this->appParams['rec_num'] = $mobile;
	}
	//设置短信签名
	public function set_sms_sign($sms_sign){
		$this->appParams['sms_free_sign_name'] = $sms_sign;
	}
	//设置短信模版
	public function set_sms_template($code=''){
		$this->appParams['sms_template_code'] = $code;
	}
	//设置短信模版内容
	public function set_sms_param(array $params =array()){
		$this->appParams['sms_param'] = json_encode($params);
	}
	//设置请求参数
	/*
	$appParams = array(
		'rec_num' => '短信接受号码',
		'sms_free_sign_name' => '短信签名',
		'sms_template_code'  => '模版代码',
		'sms_param' => array(),短信内容
	)
	 */
	
	public function set_app_params(array $appParams = array()){

		$this->appParams = $appParams;
	}
	//合并共有参数和独立参数
	private function merge_params(){
		//组装系统参数
		$this->sysParams["app_key"] = $this->app_key;
		$this->sysParams["v"] = $this->apiVersion;
		$this->sysParams["format"] = $this->format;
		$this->sysParams["sign_method"] = $this->signMethod;
		$this->sysParams["method"] = $this->method;
		$this->sysParams["timestamp"] = date("Y-m-d H:i:s");
		//应用参数组装
		$this->appParams['sms_type'] ='normal';
		return array_merge($this->sysParams,$this->appParams);
	}
	//发送短信
	public function send(){
		$req_params = $this->merge_params();
		$req_params['sign'] = $this->getSign($req_params);
		//return $req_params;
	  	$reponse = $this->curl($this->gatewayUrl,$req_params);
	  	return $reponse;
	}
	//http请求

	public function curl($url, $postFields = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ( $ch, CURLOPT_USERAGENT, "top-sdk-php" );
		//https 请求
		if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		if (is_array($postFields) && 0 < count($postFields))
		{
			$postBodyString = "";
			$postMultipart = false;
			foreach ($postFields as $k => $v)
			{
				if(!is_string($v))
					continue ;

			
				$postBodyString .= "$k=" . urlencode($v) . "&"; 
				
			}
			unset($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			$header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
		}
		$reponse = curl_exec($ch);
		
		if (curl_errno($ch))
		{
			throw new Exception(curl_error($ch),0);
		}
		else
		{
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode)
			{
				throw new Exception($reponse,$httpStatusCode);
			}
		}
		curl_close($ch);
		return $reponse;
	}

}