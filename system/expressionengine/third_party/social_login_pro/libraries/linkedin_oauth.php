<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

class linkedin_oauth
{
    const SCHEME = 'https';
    const HOST = 'www.linkedin.com';
    const REQUEST_URI   = '/uas/oauth2/authorization';
    const ACCESS_URI    = '/uas/oauth2/accessToken';
    const USERINFO_URI    = '/v1/people/~:(id,first-name,last-name,location,public-profile-url,picture-url,headline,summary,current-share,email-address)?format=json';
    
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
    


    public function get_request_token($callback, $will_post=true, $session_id=false)
    {
        $scope = "r_basicprofile r_emailaddress";
        if ($will_post==true) $scope .= " rw_company_admin w_share";
        $redirect = self::SCHEME.'://'.self::HOST.self::REQUEST_URI."?response_type=code&scope=$scope&state=$session_id&client_id=".$this->_consumer['key']."&redirect_uri=".$callback;

        //$response = $this->_connect($redirect, '');
        //var_dump($response);
        header("Location: $redirect");
        exit();
    }

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
        if (!is_array($auth)) $auth = array($auth);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $auth);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    function get_user_data($response = array())
    {
        
        $baseurl = 'https://api.linkedin.com'.self::USERINFO_URI;
    
        $ch = curl_init($baseurl);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Authorization: Bearer '.$response['access_token']
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
        $data['username'] = $rawdata->id;
        $data['screen_name'] = $rawdata->firstName.' '.$rawdata->lastName;
        $data['bio'] = ($rawdata->summary!='')?$rawdata->summary:$rawdata->headline;
        $data['occupation'] = $rawdata->headline;
        $data['email'] = (isset($rawdata->emailAddress))?$rawdata->emailAddress:'';
        $data['location'] = $rawdata->location->name;
        $data['url'] = (isset($rawdata->publicProfileUrl))?$rawdata->publicProfileUrl:'http://www.linkedin.com/profile/view?id='.$rawdata->id;
        $data['custom_field'] = $rawdata->id;
        $data['alt_custom_field'] = $rawdata->id;
        $data['avatar'] = $rawdata->pictureUrl;
        $data['photo'] = $rawdata->pictureUrl;
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
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
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