<?php
/**
 * @copyright	OurTradie, https://ourtradie.com.au Copyright (c) OurTradie Pty. Ltd.
 * @license	Released under the GNU General Public License
 */
if (!defined('API_URL'))
	define('API_URL', 'https://uat.ourtradie.com.au/manager/api/');

class OurtradieApi 
{
	/**
	 * @var    Options for Ourtradieapi.
	 * @since  13.1
	 */
	 
	protected $options;	
	
	/**
	 * @var    URL API url
	 * @since  13.1
	 */
	protected $api_url = API_URL;

	/**
     * Constructor.
     */
     
    public function __construct($options=array(), $input=array()) 
	{
		$this->options = isset($options) ? $options : array();
		$this->input = isset($input) ? $input : array();
		$this->options['auth_url'] = $this->api_url . 'authorize';
		$this->options['token_url'] = $this->api_url . 'token';
		$this->options['userefresh']= true;

		if (!empty($input['access_token'])) {
			$this->setToken($input);
		}
	}
	
	/**
	 * Get an option from the Ourtradieapi instance.
	 *
	 * @param   string  $key  The name of the option to get
	 *
	 * @return  mixed  The option value
	 *
	 * @since   12.3
	 */
	public function getOption($key)
	{
		if (isset($this->options[$key]))
			return $this->options[$key];
		return null;
	}
	
	/**
	 * Set an option for the JOAuth2Ourtradieapi instance.
	 *
	 * @param   string  $key    The name of the option to set
	 * @param   mixed   $value  The option value to set
	 *
	 * @return  Ourtradieapi  This object for method chaining
	 *
	 * @since   12.3
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] =$value;

		return $this;
	}
	
	/**
	 * Verify if the client has been authenticated
	 *
	 * @return  boolean  Is authenticated
	 *
	 * @since   12.3
	 */
	public function isAuthenticated()
	{
		$token = $this->getToken();

		if (!$token || !array_key_exists('access_token', $token))
		{
			echo 'Tokent empty';
			exit;
		}
		elseif (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
		{
			echo 'Token Expired';
			exit;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Send request.
	 *
	 * @param   string  $url      The URL forf the request.
	 * @param   mixed   $data     The data to include in the request
	 * @param   array   $headers  The headers to send with the request
	 *
	 * @return  string
	 *	
	 */
	public function query($apiMethod, $data = null, $headers = array(), $files=array(), $is_post = false)
	{
		$token = $this->getToken();
		if (array_key_exists('expires_in', $token) && $token['created'] + $token['expires_in'] < time() + 20)
		{
			if (!$this->getOption('userefresh'))
			{
				return false;
			}
			$token = $this->refreshToken($token['refresh_token']);
		}
		$headers = array('Content-Type:multipart/form-data', 'Authorization: Bearer '.$token['access_token']);

		$response = $this->sendRequest($this->api_url.$apiMethod, $data, $headers, $files, $is_post);
		if (!$response)
		{
			return 'Error - requesting data: ';
		}

		return $response;
	}

	/**
	 * Get the access token or redict to the authentication URL.
	 *
	 * @return  string  The access token
	 *
	 */
	public function authenticate()
	{
		$token = $this->getToken();
		if ($token) 
		{
			return true;
		} 
		else if (!empty($this->input['code']))
		{
			$data['code'] =  $this->input['code'];
			$data['grant_type'] = 'authorization_code';
			$data['redirect_uri'] = $this->getOption('redirect_uri');
			$data['client_id'] = $this->getOption('client_id');
			$data['client_secret'] = $this->getOption('client_secret');

			$response = $this->sendRequest($this->getOption('token_url'), $data);
			$response = json_decode($response, true);

			if ($response)
			{
				$token = array_merge($response, array('created' => time()));
				//$this->setToken($token);
				$strings = array();
				foreach ($token as $key => $value)	{
					$strings[] = $key.'='.$value;
				}
				header('Location:'.$this->options['redirect_uri'].'?'.implode('&', $strings));			
				return $token;
			}
			else
			{
				echo 'Error -  received requesting access token: ';
				exit;
			}
		}

		header('Location:'.$this->createUrl());
		
		return false;
	}
	
	/**
	 * Get the access token from the Ourtradieapi instance.
	 *
	 * @return  array  The access token
	 *	 
	 */
	public function getToken()
	{
		$token = $this->getOption('accesstoken');

		return $this->getOption('accesstoken');
	}

	/**
	 * Set an option for the Ourtradieapi instance.
	 *
	 * @param   array  $value  The access token
	 *
	 * @return  Ourtradieapi  This object for method chaining
	 *
	 * @since   12.3
	 */
	public function setToken($value)
	{
		if (is_array($value) && !array_key_exists('expires_in', $value) && array_key_exists('expires', $value))
		{
			$value['expires_in'] = $value['expires'];
			unset($value['expires']);
		}
		$this->setOption('accesstoken', $value);

		return $this;
	}
	
	/**
	 * Refresh the access token instance.
	 *
	 * @param   string  $token  The refresh token
	 *
	 * @return  array  The new access token
	 *
	 */
	public function refreshToken($token = null)
	{		
		if (!$this->getOption('userefresh'))
		{
			echo 'Refresh token is not supported for this OAuth instance.';exit;
		}

		if (!$token)
		{
			$token = $this->getToken();

			if (!array_key_exists('refresh_token', $token))
			{
				echo 'No refresh token is available.';
				exit;
			}
			$token = $token['refresh_token'];
		}

		$data = array();
		$data['grant_type'] = 'refresh_token';
		$data['refresh_token'] = $token;
		$data['client_id'] = $this->getOption('client_id');
		$data['client_secret'] = $this->getOption('client_secret');

		$response = json_decode($this->sendRequest($this->getOption('token_url'), $data), true);

		if (!empty($response['access_token']))
		{
			$token = array_merge($response, array('created' => time(), 'passed' => 1));
			$strings = array();
			foreach ($token as $key => $value)	{
				$strings[] = $key.'='.$value;
			}
			header('Location:'.$this->options['redirect_uri'].'?'.implode('&', $strings));
		}
		else
		{
			echo 'Error - received refreshing token: ';
			exit;
		}
	}
	
	/**
	 * Create the URL for authentication.
	 *
	 * @return  URL
	 *	 
	 */
	public function createUrl()
	{
		if (!$this->getOption('auth_url') || !$this->getOption('client_id'))
		{
			echo 'Authorization URL and client_id are required';
			exit;
		}

		$url = $this->getOption('auth_url');

		if (strpos($url, '?'))
		{
			$url .= '&';
		}
		else
		{
			$url .= '?';
		}

		$url .= 'response_type=code&authorize=1&state='.md5(time());
		$url .= '&client_id=' . urlencode($this->getOption('client_id'));

		if ($this->getOption('redirect_uri'))
		{
			$url .= '&redirect_uri=' . urlencode($this->getOption('redirect_uri'));
		}

		return $url;
	}
	
	/**
	 * Method to get job data
	 *	
	 */
	public function retrieve($data)
	{
		if ($this->isAuthenticated())
		{
			return $this->query($this->api_url . 'TradieJobRetrieve', $data);
			
		}
	}
	
	/**
	 * Send request
	 *
	 */
	public function sendRequest($url, $params, $headers=null, $files=array(), $is_post = false) 
	{
		$get = array();
		$get['client_id'] = $this->options['client_id'];
		$get['client_secret'] = $this->options['client_secret'];
		$get['redirect_uri'] = $this->options['redirect_uri'];

		$post = array();
		foreach ($files as $key => $file) {
			if (!file_exists($file))
				continue;
			$get[$key] = basename($file);
			$post[base64_encode($get[$key])] = '@'.realpath($file);
		}

		if ($is_post || $post) {
			$post = array_merge($post, $params);
		}
		else
			$get = array_merge($get, $params);

		$get = http_build_query($get);
		$url .= '?'.$get;

		if ($post) {			
			$params = $post;
		}

		$curl_session = curl_init($url);

		// do we have headers? set them
		if( isset($headers) ) {
			curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
		}
		// Tell curl to use HTTP POST
	    curl_setopt ($curl_session, CURLOPT_POST, true);
	    // Tell curl that this is the body of the POST
	    curl_setopt ($curl_session, CURLOPT_POSTFIELDS, $params);
	    // setup the authentication
	    // Tell curl not to return headers, but do return the response
	    curl_setopt($curl_session, CURLOPT_HEADER, false); 
	    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt ($curl_session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($curl_session, CURLOPT_SSL_VERIFYPEER, 0);

		//curl_setopt($curl_session,CURLOPT_PROXY,$pserver . ($pport?":$pport":""));
        //curl_setopt($curl_session,CURLOPT_PROXYUSERPWD,"{$puser}:{$ppass}");

	    $response = curl_exec($curl_session);

	    $error = curl_error($curl_session);

	    if ($error) {
	    	echo $error;
	    	exit;
	    }

	  	curl_close($curl_session);

	  	return($response);
	}
	
}
