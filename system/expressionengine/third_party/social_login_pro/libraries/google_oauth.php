<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

class google_oauth
{
    const SCHEME = 'https';
    const HOST = 'accounts.google.com';
    const AUTHORIZE_URI = '/accounts/OAuthAuthorizeToken';
    const REQUEST_URI   = '/o/oauth2/auth';
    const ACCESS_URI    = '/o/oauth2/token';

    //This should be changed to correspond with the
    //google service you are authenticating against.
    const SCOPE         = 'https://www.googleapis.com/auth/plus.login email'; 

    //Array that should contain the consumer secret and
    //key which should be passed into the constructor.
    private $_consumer = false;

    public function google_oauth($params)
    {
        $this->CI = get_instance();
        $this->CI->load->helper('oauth');
        //Set defaults for method and algorithm if they are not specified
        if(!array_key_exists('method', $params))$params['method'] = 'GET';
        if(!array_key_exists('algorithm', $params))$params['algorithm'] = OAUTH_ALGORITHMS::HMAC_SHA1;

        $this->_consumer = $params;
    }


    public function get_request_token($callback, $will_post=true, $session_id=false)
    {
        $baseurl = self::SCHEME.'://'.self::HOST.self::REQUEST_URI;

        //if ($will_post==true) $scope .= ",publish_stream,offline_access";
        $redirect = $baseurl."?response_type=code&scope=".self::SCOPE."&state=$session_id&client_id=".$this->_consumer['key']."&redirect_uri=".urlencode($callback);

        header("Location: $redirect");
        exit();
    }


    public function get_access_token($callback = false, $secret = false)
    {
        $baseurl = self::SCHEME.'://'.self::HOST.self::ACCESS_URI;
        
        if($secret !== false)$tokenddata['oauth_token_secret'] = urlencode($secret);

        $data_str = "client_id=".$this->_consumer['key']."&redirect_uri=".urlencode($callback)."&client_secret=".$this->_consumer['secret']."&code=".urlencode($secret)."&grant_type=authorization_code";

        $response = $this->_connect($baseurl, '', $data_str);

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
    private function _connect($url, $auth, $data=false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth));
        
        if ($data!==false)
        {
            curl_setopt($ch,CURLOPT_POST,true);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    function get_user_data($response = array())
    {
        $access_token = $response['access_token'];
        $baseurl = "https://www.googleapis.com/plus/v1/people/me?access_token=".$access_token;

        $response = $this->_connect($baseurl, '');
       
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
        $data['screen_name'] = $rawdata->displayName;
        foreach ($rawdata->placesLived as $location)
        {
            if ($location->primary==true)
            {
                $data['location'] = $location->value;
                break;
            }
        }
        
        
        $data['url'] = $rawdata->url;
        $data['avatar'] = $rawdata->image->url;
        $data['photo'] = $rawdata->image->url.'0';
        $data['email'] = $rawdata->emails[0]->value;

        $username = explode("@", $data['email']);
        $data['username'] = $username[0];
        if ($data['screen_name']=='') $data['screen_name'] = $data['username'];
        
        $data['custom_field'] = $data['email'];  
        $data['alt_custom_field'] = $rawdata->id;
        $data['status_message'] = '';
        $data['bio'] = '';   
        $data['occupation'] = $rawdata->occupation;
        $data['timezone'] = '';
        
        $data['full_name'] = $rawdata->displayName;
        $data['first_name'] = $rawdata->name->givenName;
        $data['last_name'] = $rawdata->name->familyName;
        $data['gender'] = $rawdata->gender;
                        
        return $data;
    }
    
    function start_following($username='', $response = array())
    {
        return false;
    }    
    
    function post($message, $url, $oauth_token='', $oauth_token_secret='', $xtra=array())
    {
        /*
        set_include_path(PATH_THIRD.'social_login_pro/google-api-php-client/' . PATH_SEPARATOR . get_include_path());
        
        require_once 'Google/Client.php';
        require_once 'Google/Service/Plus.php';
        
        $baseurl = "https://www.googleapis.com/plus/v1/people/me/moments/vault?access_token=".$oauth_token;
        
        $plus = new Google_Client();
        $client->setApplicationName("Client_Library_Examples");
        $apiKey = "<YOUR_API_KEY>";
        if ($apiKey == '<YOUR_API_KEY>') {
          echo missingApiKeyWarning();
        }
        $client->setDeveloperKey($apiKey);
        $moment_body = new Google_Service_Plus_Moment();
        $moment_body->setType("http://schemas.google.com/AddActivity");
        $item_scope = new Google_Service_Plus_ItemScope();
        $item_scope->setDescription($message);
        $item_scope->setUrl($url);
        $moment_body->setTarget($item_scope);
        $momentResult = $plus->moments->insert('me', 'vault', $moment_body);
        

        
        var_dump($momentResult);    
        
        $google_client = new \Google_Client;
$google_client->setClientId(GOOGLE_CLIENT_ID);
$google_client->setClientSecret(GOOGLE_CLIENT_SECRET);
$google_client->setRedirectUri(GOOGLE_REDIRECT_URI);
$google_client->setDeveloperKey(GOOGLE_DEVELOPER_KEY);
$google_client->setAccessType = 'offline';

// Either call:
//     $google_client->authenticate($auth_code);
// with the $auth_code returned by the auth page or 
//     $google_client->setAccessToken($existing_token);
// with a previously generated access token.

$plus = new \Google_Service_Plus($google_client);
$person = $plus->people->get('me');
        
        */


        return true;
   
    }
    
}
// ./system/application/libraries
?>
