<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

class yahoo_oauth
{
    const SCHEME = 'https';
    const HOST = 'api.login.yahoo.com';
    const AUTHORIZE_URI = '/oauth2/request_auth';
    const REQUEST_URI   = '/oauth/v2/get_request_token';
    const ACCESS_URI    = '/oauth2/get_token';

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
    public function yahoo_oauth($params)
    {
        $this->CI = get_instance();
        $this->CI->load->helper('oauth');
        //Set defaults for method and algorithm if they are not specified
        if(!array_key_exists('method', $params))$params['method'] = 'GET';
        if(!array_key_exists('algorithm', $params))$params['algorithm'] = OAUTH_ALGORITHMS::HMAC_SHA1;

        $this->_consumer = $params;
    }

    /**
     * This is called to begin the oauth token exchange. This should only
     * need to be called once for a user, provided they allow oauth access.
     * It will return a URL that your site should redirect to, allowing the
     * user to login and accept your application.
     *
     * @param string $callback the page on your site you wish to return to
     *                         after the user grants your application access.
     * @return mixed either the URL to redirect to, or if they specified HMAC
     *         signing an array with the token_secret and the redirect url
     */
    public function get_request_token($callback, $will_post=true, $session_id=false)
    {
        $redirect = self::SCHEME.'://'.self::HOST.self::AUTHORIZE_URI."?response_type=code&scope=$scope&state=$session_id&client_id=".$this->_consumer['key']."&redirect_uri=".urlencode($callback);

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
        $baseurl = self::SCHEME.'://'.self::HOST.self::ACCESS_URI;
        
        if($secret !== false)$tokenddata['oauth_token_secret'] = urlencode($secret);

        $data_str = "client_id=".$this->_consumer['key']."&client_secret=".$this->_consumer['secret']."&code=".urlencode($secret)."&grant_type=authorization_code"."&redirect_uri=".urlencode($callback);
        
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Authorization: Basic '.base64_encode($this->_consumer['key'].':'.$this->_consumer['secret']),
                                            'Content-Type: application/x-www-form-urlencoded'
                                            ));
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data_str);

        $response = curl_exec($ch);
        curl_close($ch);

        //Parse the response into an array it should contain
        //both the access token and the secret key. (You only
        //need the secret key if you use HMAC-SHA1 signatures.)
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
        
        if (strpos($response, 'error')!==false)
        {
            $oauth['oauth_problem'] = $a->error;
        } 
        else
        {                            
            $oauth['access_token'] = $a->access_token;        
        }   

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
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    
    function get_user_data($token_response = array())
    {
        
        $baseurl = "https://social.yahooapis.com/v1/user/me/profile?format=json";
    
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Authorization: Bearer '.$token_response['access_token']
                                            ));

        $response = curl_exec($ch);
        curl_close($ch);

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
        $username = $rawdata->profile->guid;
        foreach ($rawdata->profile->ims as $im)
        {
            if ($im->type == "YAHOO")
            {
                $username = $im->handle;
                break;
            }
        }
        $data['username'] = $username;
        $data['screen_name'] = $rawdata->profile->nickname;
        $data['bio'] = '';
        $data['occupation'] = '';
        $data['email'] = '';
        foreach ($rawdata->profile->emails as $email)
        {
            if ($email->primary == true)
            {
                $data['email'] = $email->handle;
                break;
            }
        }
        $data['location'] = '';
        $data['url'] = $rawdata->profile->profileUrl;
        $data['custom_field'] = $username;
        $data['alt_custom_field'] = $rawdata->profile->guid;
        $data['avatar'] = $rawdata->profile->image->imageUrl;
        $data['photo'] = $rawdata->profile->image->imageUrl;
        $data['timezone'] = '';
        if ($rawdata->profile->timeZone!='')
        {
            $tz = new DateTimeZone($rawdata->profile->timeZone);
            $offset = $tz->getOffset(new DateTime("now", $tz));
            if ($offset!==false)
            {
                $data['timezone'] = $offset/(60*60);
            }
        }
        
        $data['full_name'] = $rawdata->profile->givenName." ".$rawdata->profile->familyName;
        $data['first_name'] = $rawdata->profile->givenName;
        $data['last_name'] = $rawdata->profile->familyName;
        $data['gender'] = $rawdata->profile->gender;
        $data['status_message'] = urldecode($rawdata->status->message);
        
        return $data;

        
    }
    
    
    
    function start_following($username='', $response = array())
    {
        return false;
    }    



    function post($message, $url, $oauth_token='', $oauth_token_secret='', $xtra=array())
    {
        
        require_once(PATH_THIRD.'social_login_pro/libraries/inc/OAuthSimple.php');
        
        $baseurl = "http://social.yahooapis.com/v1/user/".$xtra['guid']."/profile/status";
    
        $oauth_obj = new OAuthSimple();
        $oauth_obj->setAction('PUT');
        $oauth = $oauth_obj->sign(Array('path'=>$baseurl,
                        'parameters'=>Array('format'=>'json'),
                        'signatures'=> Array('consumer_key'=>$this->_consumer['key'],
                                            'shared_secret'=>$this->_consumer['secret'],
                                            'access_token'=>$oauth_token,
                                            'access_secret'=>$oauth_token_secret)));

        $fields = array(
            'status'=>array(
                'message'=>$message
            )
        );     
        
        if (function_exists('json_encode'))
        {
            $data = json_encode($fields);
        }
        else
        {
            require_once(PATH_THIRD.'social_login_pro/libraries/inc/JSON.php');
            $json = new Services_JSON();
            $data = $json->encode($fields);
        }      
        
        $data = stripslashes($data);
        $putstr = tmpfile();
        fwrite($putstr, $data);
        fseek($putstr, 0);
                
        $ch = curl_init($oauth['signed_url']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $putstr);
        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));


        $response = curl_exec($ch);
        curl_close($ch);

        return true;

    }
    
    
    
}
// ./system/application/libraries
?>