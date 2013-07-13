<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

class yahoo_oauth
{
    const SCHEME = 'https';
    const HOST = 'api.login.yahoo.com';
    const AUTHORIZE_URI = '/oauth/v2/request_auth';
    const REQUEST_URI   = '/oauth/v2/get_request_token';
    const ACCESS_URI    = '/oauth/v2/get_token';

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
        $baseurl = self::SCHEME.'://'.self::HOST.self::REQUEST_URI;

        //Generate an array with the initial oauth values we need
        $auth = build_auth_array($baseurl, $this->_consumer['key'], $this->_consumer['secret'],
                                 array('oauth_callback'=>urlencode($callback)),
                                 $this->_consumer['method'], $this->_consumer['algorithm']);
        //Create the "Authorization" portion of the header
        $str = '';
        foreach($auth AS $key=>$value)
            if($key != 'scope')$str .= ",{$key}=\"{$value}\"";//Do not include scope in the Authorization string.
        $str = substr($str, 1);
        $str = 'Authorization: OAuth '.$str;
        //Send it
        $response = $this->_connect($baseurl, $str);
        //We should get back a request token and secret which
        //we will add to the redirect url.
        parse_str($response, $resarray);
        //Return the full redirect url and let the user decide what to do from there.
        $redirect = self::SCHEME.'://'.self::HOST.self::AUTHORIZE_URI."?oauth_token=".$resarray['oauth_token']."&S=".$session_id;
        //If they are using HMAC then we need to return the token secret for them to store.
        if($this->_consumer['algorithm'] == OAUTH_ALGORITHMS::RSA_SHA1)return $redirect;
        else return array('token_secret'=>$resarray['oauth_token_secret'], 'redirect'=>$redirect);
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
    public function get_access_token($token = false, $secret = false, $verifier = false)
    {
        //If no request token was specified then attempt to get one from the url
        if($token === false && isset($_GET['oauth_token']))$token = $_GET['oauth_token'];
        if($verifier === false && isset($_GET['oauth_verifier']))$verifier = $_GET['oauth_verifier'];
        //If all else fails attempt to get it from the request uri.
        if($token === false && $verifier === false)
        {
            $uri = $_SERVER['REQUEST_URI'];
            $uriparts = explode('?', $uri);

            $authfields = array();
            parse_str($uriparts[1], $authfields);
            $token = $authfields['oauth_token'];
            $verifier = $authfields['oauth_verifier'];
        }

        $tokenddata = array('oauth_token'=>urlencode($token), 'oauth_verifier'=>urlencode($verifier));
        if($secret !== false)$tokenddata['oauth_token_secret'] = urlencode($secret);

        $baseurl = self::SCHEME.'://'.self::HOST.self::ACCESS_URI;
        //Include the token and verifier into the header request.
        $auth = get_auth_header($baseurl, $this->_consumer['key'], $this->_consumer['secret'],
                                $tokenddata, $this->_consumer['method'], $this->_consumer['algorithm']);
        $response = $this->_connect($baseurl, $auth);
        //Parse the response into an array it should contain
        //both the access token and the secret key. (You only
        //need the secret key if you use HMAC-SHA1 signatures.)
        parse_str($response, $oauth);
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
        require_once(PATH_THIRD.'social_login_pro/libraries/inc/OAuthSimple.php');
        
        $xoauth_yahoo_guid = $response['xoauth_yahoo_guid'];
        $oauth_token = $response['oauth_token'];
        $oauth_token_secret = $response['oauth_token_secret'];
        $baseurl = "http://social.yahooapis.com/v1/user/".$xoauth_yahoo_guid."/profile";
    
        $oauth_obj = new OAuthSimple();
        $oauth = $oauth_obj->sign(Array('path'=>$baseurl,
                        'parameters'=>Array('format'=>'json'),
                        'signatures'=> Array('consumer_key'=>$this->_consumer['key'],
                                            'shared_secret'=>$this->_consumer['secret'],
                                            'access_token'=>$response['oauth_token'],
                                            'access_secret'=>$response['oauth_token_secret'])));

        $response = $this->_connect($oauth['signed_url'], array());    

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
        
        $baseurl = "http://social.yahooapis.com/v1/user/".$xoauth_yahoo_guid."/profile/status";
    
        $oauth_obj = new OAuthSimple();
        $oauth = $oauth_obj->sign(Array('path'=>$baseurl,
                        'parameters'=>Array('format'=>'json'),
                        'signatures'=> Array('consumer_key'=>$this->_consumer['key'],
                                            'shared_secret'=>$this->_consumer['secret'],
                                            'access_token'=>$oauth_token,
                                            'access_secret'=>$oauth_token_secret)));

        $response = $this->_connect($oauth['signed_url'], array());    

        if (function_exists('json_decode'))
        {
            $rawdata = json_decode($response);
        }
        else
        {
            $rawdata = $json->decode($response);
        }
        $data['status_message'] = $rawdata->status->message;
        
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
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
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