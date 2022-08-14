<?php
namespace App\ThirdParty;
use Auth;

/**
* This class handle Dewa Fortune
*/
class CsTool
{
	private $api_url;

	function __construct()
	{
		$this->api_url = "https://toko.jayamakmur.xyz/api/v1";
	}

	private function getToken()
	{
		$url = "/client_token";

		$params = array(
			"grant_type"=>"client_credentials",
			"client_id"=>"92b047eb-17be-42be-af4f-c255df44923d",
			"client_secret"=>"t7EvV5fOTM8jUJ4yuNXuhImjxlcINL3Xf8eJJWIU",
			"scope"=>"*",
		);

		$token = $this->executePost($url, $params);
		
		return $token; 
	}

	public function sendCredentials($username, $password)
	{
		$url = "/backend/credentials/update";
		$token = $this->getToken();

		if(isset($token->access_token))
		{
			$params = array(
				"url" => "http://syncgames.xyz/",
				"username" => $username,
				"password" => $password
			);

			$data = $this->executePost($url, $params, $token->access_token);
			return $data;
		}
		else
		{
			return $token; //false
		}
	}


	public function executePost($url, $params, $token="")
	{
		$api_url = $this->api_url.$url;
		$data = http_build_query($params);

		$headers[] = 'X-Requested-With: XMLHttpRequest';
		$headers[] = "Authorization: Bearer ".$token;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);

		$r = json_decode($result);

		return $r;
	}

}
