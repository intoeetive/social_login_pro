<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//error_reporting(0);

class weibo_oauth
{
    const SCHEME = 'https';
    const HOST = 'api.weibo.com';
    const AUTHORIZE_URI = '/oauth2/authorize';
    const REQUEST_URI   = '/oauth2/request_token';
    const ACCESS_URI    = '/oauth2/access_token';
    const USERINFO_URI    = '/me';
    
    //Array that should contain the consumer secret and
    //key which should be passed into the constructor.
    private $_consumer = false;
    
    /**
     * Pass in a parameters array which should look as follows:
     * array('key'=>'example.com', 'secret'=>'mysecret');
     * Note that the secret should either be a hash string for
     * HMAC signatures or a file path string for RSA signatures.
     *
     * @param array $params
     */
    public function weibo_oauth($params)
    {
        $this->CI = get_instance();
        $this->CI->load->helper('oauth');
        
        if(!array_key_exists('method', $params))$params['method'] = 'GET';
        if(!array_key_exists('algorithm', $params))$params['algorithm'] = OAUTH_ALGORITHMS::HMAC_SHA1;
        
        $this->_consumer = $params;
    }
    

    public function get_request_token($callback, $will_post=true, $session_id=false, $is_popup=false)
    {

        //$state = md5(uniqid(rand(), true));
		//get_instance()->session->set_userdata('state', $state);
		$params = array(
			'client_id' 		=> $this->_consumer['key'],
			'redirect_uri' 	=> $callback,
			//'state' 		=> $state,
			//'scope'				=> is_array($this->scope) ? implode($this->scope_seperator, $this->scope) : $this->scope,
			'response_type' 	=> 'code',
			'approval_prompt'   => 'force' // - google force-recheck
		);

		$params = array_merge($params, $this->_consumer);

		header("Location: https://api.weibo.com/oauth2/authorize?".http_build_query($params));
        exit();
        
        $baseurl = "http://api.t.sina.com.cn/oauth/request_token";

        //Generate an array with the initial oauth values we need
        $auth = build_auth_array($baseurl, $this->_consumer['key'], $this->_consumer['secret'],
                                 array('oauth_callback'=>urlencode($callback)),
                                 $this->_consumer['method'], $this->_consumer['algorithm']);
        //Create the "Authorization" portion of the header
        $str = "";
        $fields_string = '';
        foreach($auth as $key => $value)
        {
            $str .= ",{$key}=\"{$value}\"";
            $fields_string .= $key.'='.$value.'&';
        }
        $str = 'Authorization: OAuth '.substr($str, 1);
        $fields_string .= '&consumer_secret='.$this->_consumer['secret'];      

        //Send it
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($str));
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);
        exit();
        $redirect = self::SCHEME.'://'.self::HOST.self::AUTHORIZE_URI."?response_type=code&client_id=".$this->_consumer['key']."&redirect_uri=".$callback;

        //$response = $this->_connect($redirect, '');
        //var_dump($response);
        header("Location: $redirect");
        exit();

    }
    
    /**
     * This is called to finish the oauth token exchange. This too should
     * only need to be called once for a user. The token returned should
     * be stored in your database for that particular user.
     *
     * @param string $token this is the oauth_token returned with your callback url
     * @param string $secret this is the token secret supplied from the request (Only required if using HMAC)
     * @param string $verifier this is the oauth_verifier returned with your callback url
     * @return array access token and token secret
     */
    public function get_access_token($callback = false, $secret = false)
    {
  
        if($secret !== false)$tokenddata['oauth_token_secret'] = urlencode($secret);

        $baseurl = self::SCHEME.'://'.self::HOST.self::ACCESS_URI."?client_id=".$this->_consumer['key']."&redirect_uri=".urlencode($callback)."&client_secret=".$this->_consumer['secret']."&grant_type=authorization_code&code=$secret";

        $response = $this->_connect($baseurl, '');

        //Parse the response into an array it should contain
        //both the access token and the secret key. (You only
        //need the secret key if you use HMAC-SHA1 signatures.)
        if (strpos($response, 'error')!==false)
        {
            if (function_exists('json_decode'))
            {
                $a = json_decode($response);
            }
            else
            {
                require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
                $json = new Services_JSON();
                $a = $json->decode($response);
            }
            $oauth['oauth_problem'] = $a->error->message;
        } 
        else
        {                            
            parse_str($response, $oauth);        
        }   
        
        var_dump($response);
        exit();

        //Return the token and secret for storage
        return $oauth;
    }
    
    /**
     * Connects to the server and sends the request,
     * then returns the response from the server.
     * @param <type> $url
     * @param <type> $auth
     * @return <type>
     */
    private function _connect($url, $auth)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    function get_user_data($response = array())
    {
        $access_token = $response['access_token'];
        $baseurl = self::SCHEME.'://'.self::HOST.self::USERINFO_URI."?access_token=".$access_token;

        $response = $this->_connect($baseurl, array());
       
        if (function_exists('json_decode'))
        {
            $rawdata = json_decode($response);
        }
        else
        {
            require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
            $json = new Services_JSON();
            $rawdata = $json->decode($response);
        }               

        $data = array();
        $data['username'] = (isset($rawdata->username))?$rawdata->username:$rawdata->id.'@facebook';
        $data['screen_name'] = $rawdata->name;
        $data['bio'] = $rawdata->bio;
        $data['occupation'] = '';
        $data['email'] = $rawdata->email;
        $data['location'] = $rawdata->location->name;
        $fb_url = (isset($rawdata->username))?'http://www.facebook.com/'.$rawdata->username:'http://www.facebook.com/profile.php?id='.$rawdata->id;
        $data['url'] = ($rawdata->link!='')?$rawdata->link:$fb_url;
        $data['custom_field'] = (isset($rawdata->username))?$rawdata->username:$rawdata->id;
        $data['alt_custom_field'] = $rawdata->id;
        $data['avatar'] = 'http://graph.facebook.com/'.$rawdata->id.'/picture?type=large';
        $data['photo'] = 'http://graph.facebook.com/'.$rawdata->id.'/picture?type=large';
        $data['status_message'] = '';
        $data['timezone'] = $rawdata->timezone;
        
        $data['full_name'] = $rawdata->name;
        $data['first_name'] = $rawdata->first_name;
        $data['last_name'] = $rawdata->last_name;
        $data['gender'] = $rawdata->gender;
        
        $baseurl = self::SCHEME.'://'.self::HOST.self::USERINFO_URI."/feed?access_token=".$access_token;
        $response = $this->_connect($baseurl, array());
        if (function_exists('json_decode'))
        {
            $rawdata = json_decode($response);
        }
        else
        {
            $rawdata = $json->decode($response);
        }        
        foreach ($rawdata->data as $message)
        {
            if ($message->type=='status')
            {
                $data['status_message'] = $message->message;
                break;
            }
        }

        return $data;
    }
    
    
    
    function start_following($username='', $response = array())
    {
        return false;
    }    



    function post($message, $url, $oauth_token='', $oauth_token_secret='')
    {
        
        $baseurl = self::SCHEME.'://'.self::HOST.self::USERINFO_URI."/feed?access_token=".$oauth_token;
        $fields = array(
            'link'=>$url,
			'message'=>urlencode($message)
        );
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        $fields_string = rtrim($fields_string,'&');            
                
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);

        $response = curl_exec($ch);
        curl_close($ch);

        return true;

    }
    
    
    
}
// ./system/application/libraries
?>