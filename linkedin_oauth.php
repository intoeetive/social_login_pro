<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(E_ALL);

class linkedin_oauth
{
    const SCHEME = 'https';
    const HOST = 'api.linkedin.com';
    const AUTHORIZE_URI = 'www.linkedin.com/uas/oauth/authenticate';
    const REQUEST_URI   = '/uas/oauth/requestToken';
    const ACCESS_URI    = '/uas/oauth/accessToken';
    const USERINFO_URI    = '/v1/people/~:(id,first-name,last-name,location,public-profile-url,picture-url,headline,summary,current-share)';
    
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
    public function linkedin_oauth($params)
    {
        $this->CI = get_instance();
        $this->CI->load->helper('oauth');
        
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
        $str = "";
        foreach($auth as $key => $value)
            $str .= ",{$key}=\"{$value}\"";
        $str = 'Authorization: OAuth '.substr($str, 1);
        //Send it
        $response = $this->_connect($baseurl, $str);
        //We should get back a request token and secret which
        //we will add to the redirect url.
        parse_str($response, $resarray);
        //Return the full redirect url and let the user decide what to do from there.
        $redirect = self::SCHEME.'://'.self::AUTHORIZE_URI."?oauth_token=".$resarray['oauth_token']."&S=".$session_id;
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
        //var_dump($auth);
        $response = $this->_connect($baseurl, $auth);
        //Parse the response into an array it should contain
        //both the access token and the secret key. (You only
        //need the secret key if you use HMAC-SHA1 signatures.)
        parse_str($response, $oauth);
        var_dump($oauth);
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
        
        $baseurl = self::SCHEME.'://'.self::HOST.self::USERINFO_URI;
    
        $oauth_obj = new OAuthSimple();
        $oauth = $oauth_obj->sign(Array('path'=>$baseurl,
                        'parameters'=>Array('format'=>'json'),
                        'signatures'=> Array('consumer_key'=>$this->_consumer['key'],
                                            'shared_secret'=>$this->_consumer['secret'],
                                            'access_token'=>$response['oauth_token'],
                                            'access_secret'=>$response['oauth_token_secret'])));
                                            
        $response = $this->_connect($oauth['signed_url'], 'Authorization: '.$oauth['header']);
        
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
        
        var_dump($rawdata);

        $data = array();
        $data['username'] = $rawdata->id;
        $data['screen_name'] = $rawdata->firstName.' '.$rawdata->lastName;
        $data['bio'] = ($rawdata->summary!='')?$rawdata->summary:$rawdata->headline;
        $data['occupation'] = $rawdata->headline;
        $data['email'] = '';
        $data['location'] = $rawdata->location->name;
        $data['url'] = (isset($rawdata->publicProfileUrl))?$rawdata->publicProfileUrl:'http://www.linkedin.com/profile/view?id='.$rawdata->id;
        $data['custom_field'] = $rawdata->id;
        $data['alt_custom_field'] = $rawdata->id;
        $data['avatar'] = $rawdata->pictureUrl;
        $data['photo'] = '';
        $data['status_message'] = $rawdata->currentShare->comment;
        $data['timezone'] = '';
        
        $data['full_name'] = $rawdata->firstName.' '.$rawdata->lastName;
        $data['first_name'] = $rawdata->firstName;
        $data['last_name'] = $rawdata->lastName;
        $data['gender'] = '';
        if (isset($rawdata->currentShare->content)) $data['status_message'] .= ' '.$rawdata->currentShare->content->submittedUrl;
        
        return $data;
    }
    
    
    
    function start_following($username='', $response = array())
    {
        return false;
    }    



    function post($message, $url, $oauth_token='', $oauth_token_secret='')
    {
        
        require_once(PATH_THIRD.'social_login_pro/libraries/inc/OAuthSimple.php');
        
        $baseurl = self::SCHEME.'://'.self::HOST.'/v1/people/~/shares';
    
        $oauth_obj = new OAuthSimple();
        
        $oauth_obj->setAction('POST');
        $oauth = $oauth_obj->sign(Array('path'=>$baseurl,
                        'parameters'=>array('oauth_signature_method'=>'HMAC-SHA1'),
                        'signatures'=> Array('consumer_key'=>$this->_consumer['key'],
                                            'shared_secret'=>$this->_consumer['secret'],
                                            'access_token'=>$oauth_token,
                                            'access_secret'=>$oauth_token_secret)));
   
        $data  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<share>
  <comment>$message</comment>
  <visibility>
     <code>anyone</code>
  </visibility>
</share>";

        $ch = curl_init($oauth['signed_url']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$oauth['header']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
        
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        $response = curl_exec($ch);
        curl_close($ch);

        return true;

    }    
    
    
}
// ./system/application/libraries
?>