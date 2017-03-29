<?php

/*
=====================================================
 Social login PRO
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: mod.social_login_pro.php
-----------------------------------------------------
 Purpose: Integration of EE membership with social networks
=====================================================
*/


if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'social_login_pro/config.php';

class Social_login_pro {

    var $return_data	= ''; 						// Bah!
    
    var $settings = array();
    
    var $providers = array('twitter', 'facebook', 'linkedin', 'yahoo', 'google', 'vkontakte', 'instagram', 'appdotnet', 'windows', 'edmodo');//, 'weibo');
    
    var $social_login = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 
        $this->EE->lang->loadfile('login');
        $this->EE->lang->loadfile('member');
        $this->EE->lang->loadfile('social_login_pro');
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Social_login_pro' LIMIT 1");
        if ($query->num_rows()>0) $this->settings = unserialize($query->row('settings')); 
    }
    /* END */
    
    
    function form()
    {
        if (empty($this->settings)) return $this->EE->TMPL->no_results();
		                    
        $site_id = $this->EE->session->userdata('site_id');
        $data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id('Social_login_pro', 'request_token');            
		$data['id']		= ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'social_login_form';
        $data['name']		= ($this->EE->TMPL->fetch_param('name')!='') ? $this->EE->TMPL->fetch_param('name') : 'social_login_form';
        $data['class']		= ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : 'social_login_form';
        
        if ($this->EE->TMPL->fetch_param('group_id')!='')
        {
            $this->EE->load->library('encrypt');
            $data['hidden_fields']['group_id'] = $this->EE->encrypt->encode($this->EE->TMPL->fetch_param('group_id'));
        }

        if ($this->EE->TMPL->fetch_param('return')=='')
        {
            $return = $this->EE->functions->fetch_site_index();
        }
        else if ($this->EE->TMPL->fetch_param('return')=='SAME_PAGE')
        {
            $return = $this->EE->functions->fetch_current_uri();
        }
        else if (strpos($this->EE->TMPL->fetch_param('return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('return'), "https://")!==FALSE)
        {
            $return = $this->EE->TMPL->fetch_param('return');
        }
        else
        {
            $return = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'));
        }

        $data['hidden_fields']['RET'] = $return;
        
        if ($this->EE->TMPL->fetch_param('no_email_return')=='')
        {
            $data['hidden_fields']['no_email_return'] = $return;
        }
        else if ($this->EE->TMPL->fetch_param('no_email_return')=='SAME_PAGE')
        {
            $data['hidden_fields']['no_email_return'] = $this->EE->functions->fetch_current_uri();
        }
        else if (strpos($this->EE->TMPL->fetch_param('no_email_return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('no_email_return'), "https://")!==FALSE)
        {
            $data['hidden_fields']['no_email_return'] = $this->EE->TMPL->fetch_param('no_email_return');
        }
        else
        {
            $data['hidden_fields']['no_email_return'] = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('no_email_return'));
        }
        
        
        if ($this->EE->TMPL->fetch_param('new_member_return')!='')
        {
            if ($this->EE->TMPL->fetch_param('new_member_return')=='SAME_PAGE')
            {
                $data['hidden_fields']['new_member_return'] = $this->EE->functions->fetch_current_uri();
            }
            else if (strpos($this->EE->TMPL->fetch_param('new_member_return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('new_member_return'), "https://")!==FALSE)
            {
                $data['hidden_fields']['new_member_return'] = $this->EE->TMPL->fetch_param('new_member_return');
            }
            else
            {
                $data['hidden_fields']['new_member_return'] = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('new_member_return'));
            }
        }
        
        
        if (strpos($this->EE->TMPL->fetch_param('callback_uri'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('callback_uri'), "https://")!==FALSE)
        {
            $data['hidden_fields']['callback_uri'] = $this->EE->TMPL->fetch_param('callback_uri');
        }
        else if ($this->EE->TMPL->fetch_param('callback_uri')!='')
        {
            $data['hidden_fields']['callback_uri'] = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('callback_uri'));
        }      
        
        if ($this->EE->TMPL->fetch_param('secure_action')=='yes')
        {
            $data['hidden_fields']['secure_action'] = 'yes';
        } 
        
        
        
        $providers_list = ($this->EE->TMPL->fetch_param('providers')!='') ? explode('|', $this->EE->TMPL->fetch_param('providers')) : array();
        
        $tagdata = $this->EE->TMPL->tagdata;
        
        $icon_set = ($this->EE->TMPL->fetch_param('icon_set')!='') ? $this->EE->TMPL->fetch_param('icon_set') : $this->settings[$site_id]['icon_set'];
        if ($this->EE->config->item('path_third_themes')!='')
        {
            $theme_folder_path = $this->EE->config->slash_item('path_third_themes').'social_login/';
        }
        else
        {
            $theme_folder_path = $this->EE->config->slash_item('theme_folder_path').'third_party/social_login/';
        }
        if (!is_dir($theme_folder_path.$icon_set))
        {
            $icon_set = $this->settings[$site_id]['icon_set'];
        }
        
        if (preg_match_all("/".LD."providers.*?(backspace=[\"|'](\d+?)[\"|'])?".RD."(.*?)".LD."\/providers".RD."/s", $tagdata, $matches))
		{
            $providers = array();
        
            foreach($this->providers as $provider) 
            {
                if (empty($providers_list) || in_array($provider, $providers_list))
                {
                    $providers[] = $provider;
                }
            }

            $out = '';
            $chunk = $matches[3][0];
            
            if ($this->EE->config->item('url_third_themes')!='')
            {
                $theme_folder_url = $this->EE->config->slash_item('url_third_themes').'social_login/';
            }
            else
            {
                $theme_folder_url = $this->EE->config->slash_item('theme_folder_url').'third_party/social_login/';
            }

            foreach ($providers as $provider)
            {
                if (isset($this->settings[$site_id]["$provider"]) && $this->settings[$site_id]["$provider"]['app_id']!='' && $this->settings[$site_id]["$provider"]['app_secret']!='' && $this->settings[$site_id]["$provider"]['custom_field']!='')
                {
                    
                    $parsed_chunk = $chunk;
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('provider_name', $provider, $parsed_chunk);
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('provider_title', lang($provider), $parsed_chunk);
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('provider_icon', $theme_folder_url.$icon_set.'/'.$provider.'.png', $parsed_chunk);
                    if ($this->EE->session->userdata('member_id')==0)
                    {
                        $out .= $parsed_chunk;
                    }
                    else
                    {
                        $fieldname = 'm_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'];
                        $this->EE->db->select($fieldname)
                            ->from('member_data')
                            ->where('member_id', $this->EE->session->userdata('member_id'))
                            ->limit(1);
                        $query = $this->EE->db->get();
                        if ($query->row($fieldname)=='')
                        {
                            $out .= $parsed_chunk;
                        }
                    }
                    
                }
            }
            $tagdata = str_replace($matches[0][0], $out, $tagdata);
            
            if ($matches[2][0]!='')
			{
				$tagdata = substr( trim($tagdata), 0, -$matches[2][0]);
			}
		}       
        
        if ($this->EE->TMPL->fetch_param('popup')=='yes')
        {
            $tagdata .= "<script type=\"text/javascript\">
var myForm = document.getElementById('".$data['id']."');
myForm.onsubmit = function() {
    var w = window.open('about:blank','SocialLoginPopup','toolbar=0,statusbar=0,menubar=0,width=800,height=600');
    this.target = 'SocialLoginPopup';
};
</script>
            ";    
            $data['hidden_fields']['popup'] = 'y';
        }        

        return $this->EE->functions->form_declaration($data).$tagdata."\n"."</form>";
	}
    
    
    function request_token($provider='')
    {        
		
        @session_start();
        $session_id = session_id();

		$is_popup = ($this->EE->input->get_post('popup')=='y')?true:false;
        
        $site_id = $this->EE->session->userdata('site_id');
        
        if ($provider=='')
        {
            $provider = $this->EE->input->get_post('provider');
        }
        
        if ($provider=='')
        {
            $this->_show_error('general', lang('no_service_provider'), $is_popup);
            return;
        }
        
        if (!file_exists(PATH_THIRD.'social_login_pro/libraries/'.$provider.'_oauth.php'))
        {
            $this->_show_error('general', lang('provider_file_missing'), $is_popup);
            return;
        }

        //if one of the settings is empty, we can't proceed
        if ($this->settings[$site_id]["$provider"]['app_id']=='' || $this->settings[$site_id]["$provider"]['app_secret']=='' || $this->settings[$site_id]["$provider"]['custom_field']=='')
        {
            $this->_show_error('general', lang('please_provide_settings_for').' '.$providers["$provider"]['name'], $is_popup);
            return;
        }

        $this->social_login['provider'] = $provider;
        $this->social_login['auto_login'] = $this->EE->input->get_post('auto_login');
        $this->social_login['return'] = ($this->EE->input->get_post('RET')!='')?$this->EE->input->get_post('RET'):$this->EE->functions->fetch_site_index();
        $this->social_login['no_email_return'] = ($this->EE->input->get_post('no_email_return')!='')?$this->EE->input->get_post('no_email_return'):$this->social_login['return'];
        $this->social_login['new_member_return'] = ($this->EE->input->get_post('new_member_return')!='')?$this->EE->input->get_post('new_member_return'):$this->social_login['return'];
		$this->social_login['anon'] = $this->EE->input->get_post('anon');
        $this->social_login['group_id'] = $this->EE->input->get_post('group_id');
        $this->social_login['is_popup'] = $is_popup;
        $this->social_login['secure_action'] = $this->EE->input->get_post('secure_action');
        
        if ($this->EE->input->get_post('callback_uri')!='')
        {
            $this->social_login['callback_uri'] = $this->EE->input->get_post('callback_uri');
        }
        
        $this->_save_session_data($this->social_login, $session_id);
        
        if ($this->EE->session->userdata['member_id']!=0)
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login_pro' AND method='access_token_loggedin'");
        }
        else
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login_pro' AND method='access_token'");
        }
        
        if ($this->EE->input->get_post('callback_uri')!='')
        {
            $access_token_url = $this->social_login['callback_uri'];
        }
        else
        {
            $access_token_url = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
            if (!in_array($provider, array('google', 'linkedin', 'yahoo')))
            {
                $access_token_url .= '&sid='.$session_id;
            }
        }
        
        if ($this->EE->input->get_post('secure_action')=='yes')
        {
            if (strpos($access_token_url, '//')===0)
            {
                $access_token_url = 'https:'.$access_token_url;
            }
            else
            {
                $access_token_url = str_replace('http://', 'https://', $access_token_url);
            }
        }

        //do we need publish permissions?
        $will_post = false;
        if (!isset($this->settings[$site_id][$provider]['enable_posts']) || $this->settings[$site_id][$provider]['enable_posts']=='y')
        {
            $will_post = true;
        }
        
        if ($provider=='facebook')
        {
            require_once PATH_THIRD.'social_login_pro/facebook-sdk/facebook.php';
            
            $fb_config = array();
            $fb_config['appId'] = $this->settings[$site_id]["$provider"]['app_id'];
            $fb_config['secret'] = $this->settings[$site_id]["$provider"]['app_secret'];
            
            $facebook = new Facebook($fb_config);
            
            $scope = "public_profile,email,user_about_me,user_status";
            if ($will_post==true) $scope .= ",publish_actions";
            
            $params = array(
              'scope' => $scope,
              'redirect_uri' => $access_token_url
            );
            
            $loginUrl = $facebook->getLoginUrl($params);
            
            header("Location: $loginUrl");
            exit();
            
        }
        
        $params = array('key'=>$this->settings[$site_id]["$provider"]['app_id'], 'secret'=>$this->settings[$site_id]["$provider"]['app_secret']);

        $lib = $provider.'_oauth';
        $this->EE->load->library($lib, $params);
        
        
        $response = $this->EE->$lib->get_request_token($access_token_url, $will_post, $session_id, $is_popup);
        
        $this->social_login['token_secret'] = $response['token_secret'];
        
        $this->_save_session_data($this->social_login, $session_id);	        

        return $this->EE->functions->redirect($response['redirect']);
    }
       
       
       
        
    function access_token()
    {
        
        if ($this->EE->input->get('sid')!='')
        {
            $session_id = $this->EE->input->get('sid');
        }
        else if ($this->EE->input->get('state')!='')
        {
            $session_id = $this->EE->input->get('state');
        }
        else
        {
            @session_start();
            $session_id = session_id();
        }
        
		$this->social_login = $this->_get_session_data($session_id);
		
        $is_popup = $this->social_login['is_popup'];
        
        $upd_data = array();
        
        if (version_compare(APP_VER, '2.2.0', '<'))
        {
            $temp_password = $upd_data['password'] = $this->EE->functions->hash($this->_random_string());
        }
        else
        {
            $temp_password = '';
        }
        
        
        if ($this->EE->input->get('multi'))
        {
            //multisite login - go on...
            return $this->_login_by_id('0', TRUE, $temp_password);
        }
        
        $this->EE->load->helper('url');
        
        $site_id = $this->EE->config->item('site_id');
        $provider = $this->social_login['provider'];

        $lib = $provider.'_oauth';
        $params = array('key'=>$this->settings[$site_id]["$provider"]['app_id'], 'secret'=>$this->settings[$site_id]["$provider"]['app_secret']);
        
        $this->EE->load->library($lib, $params);
        
        if ($provider=='facebook')
        {
            require_once PATH_THIRD.'social_login_pro/facebook-sdk/facebook.php';
            
            $fb_config = array();
            $fb_config['appId'] = $this->settings[$site_id]["$provider"]['app_id'];
            $fb_config['secret'] = $this->settings[$site_id]["$provider"]['app_secret'];
            
            $facebook = new Facebook($fb_config);
            
            $response = array();
            $response['access_token'] = $this->social_login['oauth_token'] = $facebook->getAccessToken();
            
        }
        else
        {
            if (in_array($provider, array('vkontakte', 'instagram', 'appdotnet', 'windows', 'google', 'edmodo', 'linkedin', 'yahoo')))
            {
                if (isset($this->social_login['callback_uri']) && $this->social_login['callback_uri']!='')
                {
                    $access_token_url = $this->social_login['callback_uri'];
                }
                else
                {
                    $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login_pro' AND method='access_token'");
                    $access_token_url = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
                    if (!in_array($provider, array('google', 'linkedin', 'yahoo')))
                    {
                        $access_token_url .= '&sid='.$session_id;
                    }
                }
                
                if (isset($this->social_login['secure_action']) && $this->social_login['secure_action']=='yes')
                {
                    if (strpos($access_token_url, '//')===0)
                    {
                        $access_token_url = 'https:'.$access_token_url;
                    }
                    else
                    {
                        $access_token_url = str_replace('http://', 'https://', $access_token_url);
                    }
                }
                
                $response = $this->EE->$lib->get_access_token($access_token_url, $this->EE->input->get('code'));
                $this->social_login['oauth_token'] = $response['access_token'];
            }
            else
            {
                $response = $this->EE->$lib->get_access_token(false, $this->social_login['token_secret']);
                $this->social_login['oauth_token'] = $response['oauth_token'];
                $this->social_login['oauth_token_secret'] = $response['oauth_token_secret'];
            }

            if ($provider=='yahoo')
            {
                $this->social_login['guid'] = $response['xoauth_yahoo_guid'];
            }
    
            if ($response==NULL || (isset($response['oauth_problem']) && $response['oauth_problem']!=''))
            {
                //$this->EE->output->show_user_error('general', array($this->EE->lang->line('oauth_problem').$this->EE->lang->line($provider).'. '.$this->EE->lang->line('try_again')));
                $return = $this->social_login['return'];
                $this->_clear_session_data($session_id);
                return $this->EE->functions->redirect($return);
            }
        }
        
        $this->_save_session_data($this->social_login, $session_id);

        if ($provider == 'instagram')
        {
			$userdata = $response;
		}
		else
		{
			$userdata = $this->EE->$lib->get_user_data($response);
		}
        
        if ($userdata['custom_field']=='')
        {
            $this->_show_error('general', $this->EE->lang->line('oauth_problem').$this->EE->lang->line($provider).'. '.$this->EE->lang->line('try_again'), $is_popup);
            return;
        }

        //check whether member with this social ID exists
        $this->EE->db->select('exp_members.member_id, exp_members.email, exp_members.avatar_filename, exp_members.photo_filename')
                    ->from('exp_members')
                    ->join('exp_member_data', 'exp_members.member_id=exp_member_data.member_id', 'left')
                    ->where('m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'], $userdata['custom_field']);
        if (isset($userdata['alt_custom_field']) && $userdata['alt_custom_field']!='' && $userdata['alt_custom_field']!=$userdata['custom_field'])
        {
        	$this->EE->db->or_where('m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'], $userdata['alt_custom_field']);
        }
        $this->EE->db->limit(1);
        $query = $this->EE->db->get();
        if ($query->num_rows()>0)
        {
            if ($query->row('email')=='' && $userdata['email']!='')
            {
            	$upd_data['email'] = $userdata['email'];
            }
			if (!empty($upd_data))
            {
                $this->EE->db->where('member_id', $query->row('member_id'));
                $this->EE->db->update('members', $upd_data);
            }
            if ($this->EE->config->item('enable_avatars')=='y' && $query->row('avatar_filename')=='' && $userdata['avatar']!='')
            {
                $this->_update_avatar($query->row('member_id'), $userdata['avatar']);
            }
            if ($this->EE->config->item('enable_photos')=='y' && $query->row('photo_filename')=='' && $userdata['photo']!='')
            {
                $this->_update_photo($query->row('member_id'), $userdata['photo']);
            }
            $this->_store_keys($query->row('member_id'), (isset($userdata['alt_custom_field']))?$userdata['alt_custom_field']:'');
            
            return $this->_login_by_id($query->row('member_id'), FALSE, $temp_password);
        }

        //check whether member with this email address exists
        if ($userdata['email']!='')
        {
            $this->EE->db->select('exp_members.member_id, exp_members.avatar_filename, exp_members.photo_filename, m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'].' AS custom_field')
                        ->from('exp_members')
                        ->join('exp_member_data', 'exp_members.member_id=exp_member_data.member_id', 'left')
                        ->where('email', $userdata['email'])
                        ->limit(1);
            $query = $this->EE->db->get();
            if ($query->num_rows()>0)
            {
                
				if (version_compare(APP_VER, '2.2.0', '<'))
                {
                    $this->EE->db->where('member_id', $query->row('member_id'));
                    $this->EE->db->update('members', $upd_data);
                }
                if ($this->EE->config->item('enable_avatars')=='y' && $query->row('avatar_filename')=='' && $userdata['avatar']!='')
                {
                    $this->_update_avatar($query->row('member_id'), $userdata['avatar']);
                }
                if ($this->EE->config->item('enable_photos')=='y' && $query->row('photo_filename')=='' && $userdata['photo']!='')
	            {
	                $this->_update_photo($query->row('member_id'), $userdata['photo']);
	            }
                if ($query->row('custom_field')=='')
                {
                    $this->EE->db->where('member_id', $query->row('member_id'));
                    $this->EE->db->update('exp_member_data', array('m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'] => $userdata['custom_field']));
                }
                return $this->_login_by_id($query->row('member_id'), FALSE, $temp_password);
            }
        }
        
        if ( $this->EE->config->item('allow_member_registration') != 'y' )
		{
			$this->_show_error('general', lang('mbr_registration_not_allowed'), $is_popup);
            return;
		}
                
        if (isset($this->settings[$site_id]['email_is_username']) && $this->settings[$site_id]['email_is_username']==true && $userdata['email']!='')
        {
			$data['username']	= $userdata['email'];
		}        
        else
        {
            $data['username']	= $userdata['username'];
        }
        
        //need to make sure username is unique
        $this->EE->db->select('username')
                    ->from('members')
                    ->where('username', $data['username'])
                    ->limit(1);
        $q = $this->EE->db->get();
        if ($q->num_rows()>0)
        {
            $data['username'] = $userdata['username'].'@'.$provider;
        }
        
        $j = 1;
        do
        {
            $this->EE->db->select('username')
                        ->from('members')
                        ->where('username', $data['username'])
                        ->limit(1);
            $q = $this->EE->db->get();
            if ($q->num_rows()>0)
            {
                $data['username'] = $userdata['username'].$j;
            }
            $j++;
        } 
        while ($q->num_rows()>0);
        
        if ($userdata['email']=='' && isset($this->settings[$site_id]['force_pending_if_no_email']) && $this->settings[$site_id]['force_pending_if_no_email']==true)
        {
        	$data['group_id'] = 4; //Pending
       	}
       	else
       	{
			$data['group_id'] = (isset($this->settings[$site_id]['member_group']) && $this->settings[$site_id]['member_group']!='') ? $this->settings[$site_id]['member_group'] : $this->EE->config->item('default_member_group');
	        if ($this->social_login['group_id']!='')
	        {
	            $this->EE->load->library('encrypt');
	            $group_id = $this->EE->encrypt->decode($this->social_login['group_id']);
	            $member_groups = array();
	            $this->EE->db->select('group_id, group_title');
	            $this->EE->db->where('group_id NOT IN (1,2,4)');
	            $q = $this->EE->db->get('member_groups');
	            foreach ($q->result() as $obj)
	            {
	                $member_groups[$obj->group_id] = $obj->group_id;
	            }
	            if (in_array($group_id, $member_groups))
	            {
	                $data['group_id'] = $group_id;
	            }
	        }
   		}

		$data['password'] = '';
        $data['ip_address']  = $this->EE->input->ip_address();
		$data['unique_id']	= $this->EE->functions->random('encrypt');
		$data['join_date']	= $this->EE->localize->now;
		$data['email']		= $userdata['email'];
        
		$data['screen_name'] = $userdata['screen_name'];
        //need to make sure screen_name is unique
        $j = 1;
        do
        {
            $this->EE->db->select('screen_name')
                        ->from('members')
                        ->where('screen_name', $data['screen_name'])
                        ->limit(1);
            $q = $this->EE->db->get();
            if ($q->num_rows()>0)
            {
                $data['screen_name'] = $userdata['screen_name']." ".$j;
            }
            $j++;
        } 
        while ($q->num_rows()>0);
        
		$data['url']		 = prep_url($userdata['url']);
        $data['bio']         = $userdata['bio'];
        $data['occupation']  = $userdata['occupation'];
		$data['location']	 = $userdata['location'];
        if ($userdata['timezone']!='')
        {
            $this->EE->load->helper('date');
			$data['timezone'] = array_search($userdata['timezone'], timezones());
        }
        else
        {
            $data['timezone']	= ($this->EE->config->item('default_site_timezone') && $this->EE->config->item('default_site_timezone') != '') ? $this->EE->config->item('default_site_timezone') : $this->EE->config->item('server_timezone');
        }
        
        $data['avatar_filename'] = 'social_login/'.$provider.'.png';
        $data['avatar_width'] = '80'; 
        $data['avatar_height'] = '80';

		$data['language']	= ($this->EE->config->item('deft_lang')) ? $this->EE->config->item('deft_lang') : 'english';
		$data['time_format'] = ($this->EE->config->item('time_format')) ? $this->EE->config->item('time_format') : 'us';
		if (version_compare(APP_VER, '2.6.0', '<'))
		{
			$data['daylight_savings'] = ($this->EE->config->item('default_site_dst') && $this->EE->config->item('default_site_dst') != '') ? $this->EE->config->item('default_site_dst') : $this->EE->config->item('daylight_savings');	
		}	
        
        $data['last_visit'] = $this->EE->localize->now;
        $data['last_activity'] = $this->EE->localize->now;
		
        $this->EE->db->query($this->EE->db->insert_string('exp_members', $data));

		$member_id = $this->EE->db->insert_id();

		$cust_fields['member_id'] = $member_id;
        $cust_fields['m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field']] = $userdata['custom_field'];
        if (isset($this->settings[$site_id]['full_name']) && $this->settings[$site_id]['full_name']!='')
        {
            $cust_fields['m_field_id_'.$this->settings[$site_id]['full_name']] = $userdata['full_name'];
        }
        if (isset($this->settings[$site_id]['first_name']) && $this->settings[$site_id]['first_name']!='')
        {
            $cust_fields['m_field_id_'.$this->settings[$site_id]['first_name']] = $userdata['first_name'];
        }
        if (isset($this->settings[$site_id]['last_name']) && $this->settings[$site_id]['last_name']!='')
        {
            $cust_fields['m_field_id_'.$this->settings[$site_id]['last_name']] = $userdata['last_name'];
        }
        if (isset($this->settings[$site_id]['gender']) && $this->settings[$site_id]['gender']!='')
        {
            $userdata['gender'] = (in_array(strtolower($userdata['gender']), array('male','m')))?'m':'f';
            $cust_fields['m_field_id_'.$this->settings[$site_id]['gender']] = $userdata['gender'];
        }
		$this->EE->db->query($this->EE->db->insert_string('exp_member_data', $cust_fields));

		$this->EE->db->query($this->EE->db->insert_string('exp_member_homepage', array('member_id' => $member_id)));
        
        if (version_compare(APP_VER, '2.2.0', '<'))
        {
            $this->EE->db->where('member_id', $member_id);
            $this->EE->db->update('members', $upd_data);
        }
        
        if ($this->EE->config->item('enable_avatars')=='y' && $userdata['avatar']!='')
        {
            $this->_update_avatar($member_id, $userdata['avatar']);
        }
        if ($this->EE->config->item('enable_photos')=='y' && $userdata['photo']!='')
        {
            $this->_update_photo($member_id, $userdata['photo']);
        }
        
        $zoo = $this->EE->db->select('module_id')->from('modules')->where('module_name', 'Zoo_visitor')->get(); 
        if ($zoo->num_rows()>0)
        {
        	$this->EE->load->add_package_path(PATH_THIRD.'zoo_visitor/');
			$this->EE->load->library('zoo_visitor_lib');
			$this->EE->zoo_visitor_lib->sync_member_data();
			$this->EE->load->remove_package_path(PATH_THIRD.'zoo_visitor/');
        }
        
        if ($this->settings[$site_id]["$provider"]['follow_username']!='')
        {
            $follow_username = $this->settings[$site_id]["$provider"]['follow_username'];
            $this->EE->$lib->start_following($follow_username, $response);
        }
        
        $this->_store_keys($member_id, (isset($userdata['alt_custom_field']))?$userdata['alt_custom_field']:'');

        //$data = array_merge($data, $cust_fields);
        
        // Send admin notifications
		if ($this->EE->config->item('new_member_notification') == 'y' && 
			$this->EE->config->item('mbr_notification_emails') != '')
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$swap = array(
							'name'					=> $name,
							'site_name'				=> stripslashes($this->EE->config->item('site_name')),
							'control_panel_url'		=> $this->EE->config->item('cp_url'),
							'username'				=> $data['username'],
							'email'					=> $data['email']
						 );

			$template = $this->EE->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);

			$this->EE->load->helper('string');

			// Remove multiple commas
			$notify_address = reduce_multiples($this->EE->config->item('mbr_notification_emails'), ',', TRUE);

			// Send email
			$this->EE->load->helper('text');

			$this->EE->load->library('email');
			$this->EE->email->wordwrap = true;
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
			$this->EE->email->to($notify_address);
			$this->EE->email->subject($email_tit);
			$this->EE->email->message(entities_to_ascii($email_msg));
			$this->EE->email->Send();
		}
        
        $this->EE->stats->update_member_stats();
        
        //new members can be redirected to different URL
        if ($this->social_login['new_member_return']!=$this->social_login['return'])
        {
            $this->social_login['no_email_return']=$this->social_login['new_member_return'];
        }
        $this->social_login['return'] = $this->social_login['new_member_return'];
        $this->_save_session_data($this->social_login, $session_id);


        // -------------------------------------------
		// 'member_member_register' hook.
		//  - Additional processing when a member is created through the User Side
		//  - $member_id added in 2.0.1
		//
			$edata = $this->EE->extensions->call('member_member_register', $data, $member_id);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

        return $this->_login_by_id($member_id, FALSE, $temp_password);

    }  
    
    
    
    function access_token_loggedin()
    {
        if ($this->EE->input->get('sid')!='')
        {
            $session_id = $this->EE->input->get('sid');
        }
        else
        {
            $session_id = $this->EE->input->get('state');
        }

		$this->social_login = $this->_get_session_data($session_id);
		
        $is_popup = $this->social_login['is_popup'];
        
        if ($this->EE->session->userdata('member_id')==0)
        {
            $this->_show_error('general', $this->EE->lang->line('please_log_in'), $is_popup);
            return;
        }
        
        $site_id = $this->EE->config->item('site_id');
        $provider = $this->social_login['provider'];
        $lib = $provider.'_oauth';
        $params = array('key'=>$this->settings[$site_id]["$provider"]['app_id'], 'secret'=>$this->settings[$site_id]["$provider"]['app_secret']);
                
        $this->EE->load->library($lib, $params);
        if (in_array($provider, array('facebook', 'vkontakte', 'instagram', 'appdotnet', 'windows', 'google', 'linkedin', 'yahoo')))
        {
            $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login_pro' AND method='access_token_loggedin'");
            $access_token_url = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
            if (!in_array($provider, array('google', 'linkedin', 'yahoo')))
            {
                $access_token_url .= '&sid='.$session_id;
            }
            
            if (isset($this->social_login['secure_action']) && $this->social_login['secure_action']=='yes')
            {
                if (strpos($access_token_url, '//')===0)
                {
                    $access_token_url = 'https:'.$access_token_url;
                }
                else
                {
                    $access_token_url = str_replace('http://', 'https://', $access_token_url);
                }
            }
            
            $response = $this->EE->$lib->get_access_token($access_token_url, $this->EE->input->get('code'));
            $this->social_login['oauth_token'] = $response['access_token'];
        }
        else
        {
            $response = $this->EE->$lib->get_access_token(false, $this->social_login['token_secret']);
            $this->social_login['oauth_token'] = $response['oauth_token'];
            $this->social_login['oauth_token_secret'] = $response['oauth_token_secret'];
        }
        if ($provider=='yahoo')
        {
            $this->social_login['guid'] = $response['xoauth_yahoo_guid'];
        }

        if ($response==NULL || (isset($response['oauth_problem']) && $response['oauth_problem']!=''))
        {
            $this->_clear_session_data($session_id);
			$this->_show_error('general', $this->EE->lang->line('oauth_problem').$this->EE->lang->line($provider).'. '.$response['oauth_problem'].$this->EE->lang->line('try_again'), $is_popup);
            return;
        }
        
        $this->_save_session_data($this->social_login, $session_id);

        if ($provider == 'instagram')
        {
			$userdata = $response;
		}
		else
		{
			$userdata = $this->EE->$lib->get_user_data($response);
		}
        
        if ($userdata['custom_field']=='')
        {
            $this->_show_error('general', $this->EE->lang->line('oauth_problem').$this->EE->lang->line($provider).'. '.$this->EE->lang->line('try_again'), $is_popup);
            return;
        }	
        
        if (isset($this->settings[$site_id]['prevent_duplicate_assoc']) && $this->settings[$site_id]['prevent_duplicate_assoc']==true)
        {
        	$this->EE->db->select('member_id')
        			->from('exp_member_data')
        			->where('m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field'], $userdata['custom_field'])
					->where('member_id != '.$this->EE->session->userdata('member_id'));
			$check_q = $this->EE->db->get();
			if ($check_q->num_rows()>0)
			{
				$this->_show_error('general', $this->EE->lang->line('this').$this->EE->lang->line($provider).$this->EE->lang->line('account_already_associated'), $is_popup);
            return;
			}
        }
		
		$select_a = array();
		$select = "avatar_filename, photo_filename";
		if (isset($this->settings[$site_id]['full_name']) && $this->settings[$site_id]['full_name']!='')
        {
           $select_a[] = 'm_field_id_'.$this->settings[$site_id]['full_name'];
        }
        if (isset($this->settings[$site_id]['first_name']) && $this->settings[$site_id]['first_name']!='')
        {
            $select_a[] = 'm_field_id_'.$this->settings[$site_id]['first_name'];
        }
        if (isset($this->settings[$site_id]['last_name']) && $this->settings[$site_id]['last_name']!='')
        {
            $select_a[] = 'm_field_id_'.$this->settings[$site_id]['last_name'];
        }
        if (isset($this->settings[$site_id]['gender']) && $this->settings[$site_id]['gender']!='')
        {
            $select_a[] = 'm_field_id_'.$this->settings[$site_id]['gender'];
        }
        if (!empty($select_a))
        {
        	$select .= ", ". implode(", ", $select_a);
        }
        $this->EE->db->select($select)
        			->from('exp_members')
        			->join('exp_member_data', 'exp_members.member_id=exp_member_data.member_id', 'left')
        			->where('exp_members.member_id', $this->EE->session->userdata('member_id'));
		$member_data_q = $this->EE->db->get();
		
		$cust_fields = array();        
        $cust_fields['m_field_id_'.$this->settings[$site_id]["$provider"]['custom_field']] = $userdata['custom_field'];
			
		foreach ($member_data_q->result_array() as $member_data)
		{
			if (isset($this->settings[$site_id]['full_name']) && $this->settings[$site_id]['full_name']!='' && $member_data['m_field_id_'.$this->settings[$site_id]['full_name']]=='')
	        {
	            $cust_fields['m_field_id_'.$this->settings[$site_id]['full_name']] = $userdata['full_name'];
	        }
	        if (isset($this->settings[$site_id]['first_name']) && $this->settings[$site_id]['first_name']!='' && $member_data['m_field_id_'.$this->settings[$site_id]['first_name']]=='')
	        {
	            $cust_fields['m_field_id_'.$this->settings[$site_id]['first_name']] = $userdata['first_name'];
	        }
	        if (isset($this->settings[$site_id]['last_name']) && $this->settings[$site_id]['last_name']!='' && $member_data['m_field_id_'.$this->settings[$site_id]['last_name']]=='')
	        {
	            $cust_fields['m_field_id_'.$this->settings[$site_id]['last_name']] = $userdata['last_name'];
	        }
	        if (isset($this->settings[$site_id]['gender']) && $this->settings[$site_id]['gender']!='' && $member_data['m_field_id_'.$this->settings[$site_id]['gender']]=='')
	        {
	            $userdata['gender'] = (in_array(strtolower($userdata['gender']), array('male','m')))?'m':'f';
	            $cust_fields['m_field_id_'.$this->settings[$site_id]['gender']] = $userdata['gender'];
	        }
			
	        if ($this->EE->config->item('enable_avatars')=='y' && $userdata['avatar']!='' && $member_data['avatar_filename']=='')
	        {
	            $this->_update_avatar($this->EE->session->userdata('member_id'), $userdata['avatar']);
	        }
	        if ($this->EE->config->item('enable_photos')=='y' && $userdata['photo']!='' && $member_data['photo_filename']=='')
	        {
	            $this->_update_photo($this->EE->session->userdata('member_id'), $userdata['photo']);
	        }
   		}
   		
   		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
   		$this->EE->db->update('exp_member_data', $cust_fields);
        
        $this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
   		$this->EE->db->update('exp_members', array('last_activity' => $this->EE->localize->now));
		
        $zoo = $this->EE->db->select('module_id')->from('modules')->where('module_name', 'Zoo_visitor')->get(); 
        if ($zoo->num_rows()>0)
        {
        	$this->EE->load->add_package_path(PATH_THIRD.'zoo_visitor/');
			$this->EE->load->library('zoo_visitor_lib');
			$this->EE->zoo_visitor_lib->sync_member_data();
			$this->EE->load->remove_package_path(PATH_THIRD.'zoo_visitor/');
        }
        
        if (isset($this->settings[$site_id]["$provider"]['follow_username']) && $this->settings[$site_id]["$provider"]['follow_username']!='')
        {
            $follow_username = $this->settings[$site_id]["$provider"]['follow_username'];
            $this->EE->$lib->start_following($follow_username, $response);
        }
        
        $this->_store_keys($this->EE->session->userdata('member_id'), (isset($userdata['alt_custom_field']))?$userdata['alt_custom_field']:'');

        // success!!
        $return = $this->social_login['return'];
        $this->_clear_session_data($session_id);
        
        if ($is_popup==false)
        {
            return $this->EE->functions->redirect($return);
        }
        else
        {
            $out = "<script type=\"text/javascript\">
window.opener.location = '$return';
window.close();            
</script>";
            echo $out;
        }
        
    }      
    
    
    function tokens()
    {
    	if ($this->EE->session->userdata('member_id')==0)
    	{
    		return $this->EE->TMPL->no_results();
    	}
    	
    	$this->EE->db->select('social_login_keys')
                    ->from('members')
                    ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get();
        if ($q->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
 
        if ($q->row('social_login_keys')=='')
        {
            return $this->EE->TMPL->no_results();
        }
        
        $keys = unserialize($q->row('social_login_keys'));
        $variables = array();
        $variables_row = array();
        $site_id = $this->EE->session->userdata('site_id');
        $icon_set = ($this->EE->TMPL->fetch_param('icon_set')!='') ? $this->EE->TMPL->fetch_param('icon_set') : $this->settings[$site_id]['icon_set'];
        if (!is_dir($this->EE->config->slash_item('theme_folder_path').'third_party/social_login/'.$icon_set))
        {
            $icon_set = $this->settings[$site_id]['icon_set'];
        }
        
        if ($this->EE->config->item('url_third_themes')!='')
        {
            $theme_folder_url = $this->EE->config->slash_item('url_third_themes').'social_login/';
        }
        else
        {
            $theme_folder_url = $this->EE->config->slash_item('theme_folder_url').'third_party/social_login/';
        }
                                
        foreach ($keys as $provider_name=>$provider_data)
        {
        	$variables_row = array();
			$variables_row['provider_name'] = $provider_name;
        	$variables_row['provider_title'] = lang($provider_name);
        	$variables_row['provider_icon'] = $theme_folder_url.$icon_set.'/'.$provider_name.'.png';
        	$variables_row['oauth_token'] = $provider_data['oauth_token'];
        	$variables_row['oauth_token_secret'] = $provider_data['oauth_token_secret'];
        	if ($provider_name=='yahoo')
        	{
        		$variables_row['guid'] = $provider_data['guid'];
        	}
        	else
        	{
        		$variables_row['guid'] = '';
        	}
        	if (isset($provider_data['user_id']))
        	{
        		$variables_row['user_id'] = $provider_data['user_id'];
        	}
        	else
        	{
        		$variables_row['user_id'] = '';
        	}
        	$variables[] = $variables_row;
        }
        
        $out = $this->EE->TMPL->parse_variables(trim($this->EE->TMPL->tagdata), $variables);
        
        return $out;
        

    }
    
    
    
    function _store_keys($member_id, $user_id='')
    {

        $this->EE->db->select('social_login_keys')
                    ->from('members')
                    ->where('member_id', $member_id);
        $q = $this->EE->db->get();
        if ($q->num_rows()==0)
        {
            return false;
        }
        $keys = array();
        if ($q->row('social_login_keys')!='')
        {
            $keys = unserialize($q->row('social_login_keys'));
        }
        $provider = $this->social_login['provider'];
        $keys[$provider]['oauth_token'] = $this->social_login['oauth_token'];
        $keys[$provider]['oauth_token_secret'] = isset($this->social_login['oauth_token_secret'])?$this->social_login['oauth_token_secret']:'';
        $keys[$provider]['user_id'] = $user_id;
        if ($provider=='yahoo') $keys[$provider]['guid'] = $this->social_login['guid'];
        
        $data['social_login_keys'] = serialize($keys);
        $this->EE->db->where('member_id', $member_id);
        $this->EE->db->update('members', $data);
        
    }
    
    
    
    
    
    function _update_avatar($member_id, $url)
    {
        if ($member_id==0 || $member_id=='' || $url=='')
        {
            return;
        }
        
        $avatar_path = $this->EE->config->item('avatar_path');
        if ( ! @is_dir($avatar_path))
        {
        	return;
        }
        
        $filename = 'uploads/avatar_'.$member_id.'.png';
        $filepath = $avatar_path.$filename;
        while (file_exists($filepath))
        {
            $filename = 'uploads/avatar_'.$member_id.'_'.rand(1, 100000).'.png';
            $filepath = $avatar_path.$filename;
        }

        $ch = curl_init();
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        else
        {        
            $rch = curl_copy_handle($ch);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $url);
                $header = curl_exec($rch);
                if (curl_errno($rch)) 
                {
                    $code = false;
                }
                else 
                {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) 
                    {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $url = trim(array_pop($matches));
                    } 
                    else 
                    {
                        $code = false;
                    }
                }
            } while ($code != false);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $fp = fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        $size = getimagesize($filepath);
        //rename if necessary
        switch ($size['mime'])
        {
            case 'image/jpeg':
                $filename = str_replace('.png', '.jpg', $filename);
                break;
            case 'image/gif':
                $filename = str_replace('.png', '.gif', $filename);
                break;
            default:
                //do nothing;
                break;
        }
        $new_filepath = $avatar_path.$filename;
        //size ok?
		$max_w	= ($this->EE->config->item('avatar_max_width') == '' OR $this->EE->config->item('avatar_max_width') == 0) ? 100 : $this->EE->config->item('avatar_max_width');
		$max_h	= ($this->EE->config->item('avatar_max_height') == '' OR $this->EE->config->item('avatar_max_height') == 0) ? 100 : $this->EE->config->item('avatar_max_height');
        if ($size[0] > $max_w && $size[1] > $max_h)
        {
            $config['source_image'] = $filepath;
            $config['new_image'] = $new_filepath;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $max_w;
            $config['height'] = $max_h;
            $this->EE->load->library('image_lib', $config);

            $this->EE->image_lib->resize();
        }
        else 
        if ($new_filepath != $filepath)
        {
            copy($filepath, $new_filepath);
        }
        
        if (file_exists($new_filepath))
        {
            $size = getimagesize($new_filepath);
            if ($size!==false)
            {
                $upd_data = array('avatar_filename'=>$filename, 'avatar_width'=>$size[0], 'avatar_height'=>$size[1]);
                $this->EE->db->where('member_id', $member_id);
                $this->EE->db->update('members', $upd_data);
            }
        }

    }
    


    function _update_photo($member_id, $url)
    {
        if ($member_id==0 || $member_id=='' || $url=='')
        {
            return;
        }
        
        $photo_path = $this->EE->config->item('photo_path');
        if ( ! @is_dir($photo_path))
        {
        	return;
        }
        
        $filename = 'photo_'.$member_id.'.jpg';
        $filepath = $photo_path.$filename;
        while (file_exists($filepath))
        {
            $filename = 'photo_'.$member_id.'_'.rand(1, 100000).'.jpg';
            $filepath = $photo_path.$filename;
        }

        $ch = curl_init();
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        else
        {        
            $rch = curl_copy_handle($ch);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $url);
                $header = curl_exec($rch);
                if (curl_errno($rch)) 
                {
                    $code = false;
                }
                else 
                {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) 
                    {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $url = trim(array_pop($matches));
                    } 
                    else 
                    {
                        $code = false;
                    }
                }
            } while ($code != false);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $fp = fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        $size = getimagesize($filepath);
        //rename if necessary
        switch ($size['mime'])
        {
            case 'image/png':
                $filename = str_replace('.jpg', '.png', $filename);
                break;
            case 'image/gif':
                $filename = str_replace('.jpg', '.gif', $filename);
                break;
            default:
                //do nothing;
                break;
        }
        $new_filepath = $photo_path.$filename;
        //size ok?
        $max_w	= ($this->EE->config->item('photo_max_width') == '' OR $this->EE->config->item('photo_max_width') == 0) ? 100 : $this->EE->config->item('photo_max_width');
		$max_h	= ($this->EE->config->item('photo_max_height') == '' OR $this->EE->config->item('photo_max_height') == 0) ? 100 : $this->EE->config->item('photo_max_height');
        if ($size[0] > $max_w && $size[1] > $max_h)
        {
			$config['source_image'] = $filepath;
            $config['new_image'] = $new_filepath;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $max_w;
            $config['height'] = $max_h;
            $this->EE->load->library('image_lib', $config);

            $this->EE->image_lib->resize();
        }
        else 
        if ($new_filepath != $filepath)
        {
            copy($filepath, $new_filepath);
        }

        if (file_exists($new_filepath))
        {
            $size = getimagesize($new_filepath);
            if ($size!==false)
            {
                $upd_data = array('photo_filename'=>$filename, 'photo_width'=>$size[0], 'photo_height'=>$size[1]);
                $this->EE->db->where('member_id', $member_id);
                $this->EE->db->update('members', $upd_data);
            }
        }

    }


    
    function _login_by_id($member_id, $multi = FALSE, $temp_password='')
    {
        $session_id = $this->social_login['session_id'];
		$is_popup = $this->social_login['is_popup'];
        
        $site_id = $this->EE->config->item('site_id');
        
        if ($multi == FALSE && ($member_id=='' || $member_id==0))
        {
            $this->_clear_session_data($session_id);
            return false;
        }
        
        // Auth library will not work here, as we don't have password
        // so using old fashion session routines...

		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{
			$this->_clear_session_data($session_id);
            $this->_show_error('general', $this->EE->lang->line('not_authorized'), $is_popup);
            return;
		}

		/* -------------------------------------------
		/* 'member_member_login_start' hook.
		/*  - Take control of member login routine
		/*  - Added EE 1.4.2
		*/
			$edata = $this->EE->extensions->call('member_member_login_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$expire = ($this->social_login['auto_login']==1) ? 60*60*24*365 : 0;
        
        
        
		if ( $multi == FALSE )
		{
			$this->EE->db->select('member_id, unique_id, group_id, email')
                        ->from('exp_members')
                        ->where('member_id', $member_id);
                        
			$query = $this->EE->db->get();
			
			if ($query->row('email')=='')
			{
				$this->social_login['return'] = $this->social_login['no_email_return'];
				//$this->_save_session_data($this->social_login, $session_id);       
			}

		}
		else
		{
			if ($this->EE->config->item('allow_multi_logins') == 'n' || ! $this->EE->config->item('multi_login_sites') || $this->EE->config->item('multi_login_sites') == '')
			{
				$this->_clear_session_data($session_id);
                $this->_show_error('general', $this->EE->lang->line('not_authorized'), $is_popup);
                return;
			}

			if ($this->EE->input->get('cur') === FALSE || $this->EE->input->get_post('orig') === FALSE || $this->EE->input->get('orig_site_id') === FALSE)
			{
				$this->_clear_session_data($session_id);
                $this->_show_error('general', $this->EE->lang->line('not_authorized'), $is_popup);
                return;
			}

			// remove old sessions
			$this->EE->session->gc_probability = 100;
			$this->EE->session->delete_old_sessions();

			// Check Session ID

			$this->EE->db->select('member_id, unique_id, group_id, email')
                        ->from('exp_sessions')
                        ->join('exp_members', 'exp_sessions.member_id = exp_members.member_id', 'left')
                        ->where('session_id', $this->EE->input->get('multi'))
                        ->where('exp_sessions.last_activity > '.$expire);
                        
			$query = $this->EE->db->get();

			if ($query->num_rows() > 0)
			{

    			//start setting cookies
        		$this->EE->input->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);
                if (version_compare(APP_VER, '2.2.0', '<'))
                {
            		$this->EE->input->set_cookie($this->EE->session->c_uniqueid , $query->row('unique_id') , $expire);
            		$this->EE->input->set_cookie($this->EE->session->c_password , $temp_password,  $expire);
                }
        
                // anonymize?
        		if ($this->social_login['social_login_anon']==1)
        		{
        			$this->EE->input->set_cookie($this->EE->session->c_anon);
        		}
        		else
        		{
        			$this->EE->input->set_cookie($this->EE->session->c_anon, 1,  $expire);
        		}
    
    			if ($this->EE->config->item('user_session_type') == 'cs' || $this->EE->config->item('user_session_type') == 's')
    			{
    				$this->EE->input->set_cookie($this->EE->session->c_session, 
                                                    $this->EE->input->get('multi'), 
                                                    $this->EE->session->session_length);
    			}
    
    			// -------------------------------------------
    			// 'member_member_login_multi' hook.
    			//  - Additional processing when a member is logging into multiple sites
    			//
    				$edata = $this->EE->extensions->call('member_member_login_multi', $query->row());
    				if ($this->EE->extensions->end_script === TRUE) return;
    			//
    			// -------------------------------------------
    
    			//more sites to log in?
                $sites_list	=  explode('|',$this->EE->config->item('multi_login_sites'));
                $sites_list = array_filter($sites_list, 'strlen');
                
                if ($this->EE->input->get('orig') == $this->EE->input->get('cur') + 1)
                {
                    $next = $this->EE->input->get_post('cur') + 2;
                }
                else
                {
                    $next = $this->EE->input->get('cur') + 1;
                }

    			if ( isset($sites_list[$next]) )
    			{
        			$act_q = $this->EE->db->select('action_id')->from('actions')->where('class', 'Social_login_pro')->where('method', 'access_token')->get();
                    $next_qs = array(
        				'ACT'	=> $act_q->row('action_id'),
        				'sid'		=> $session_id,
        				'cur'	=> $next,
        				'orig'	=> $this->EE->input->get('orig'),
        				'multi'	=> $this->EE->input->get('multi'),
        				'orig_site_id' => $this->EE->input->get('orig_site_id')
        			);
        			
        			$next_url = $sites[$next].'?'.http_build_query($next_qs);
        
        			return $this->EE->functions->redirect($next_url);
    			}
                else
                {
                    if ($query->row('email')=='')
					{
						$this->social_login['return'] = $this->social_login['no_email_return'];     
					}
					$return = $this->social_login['return'];
                    $this->_clear_session_data();
                    return $this->EE->functions->redirect($return);
                }
            }
		}

		// any chance member does not exist? :)
        if ($query->num_rows() == 0)
		{
			$this->_clear_session_data($session_id);
            $this->_show_error('submission', $this->EE->lang->line('auth_error'), $is_popup);
            return;
		}

		// member pending?
        if ($query->row('group_id') == 4 && $query->row('email') != '')
		{
			$this->_clear_session_data($session_id);
            $this->_show_error('general', $this->EE->lang->line('mbr_account_not_active'), $is_popup);
            return;
		}

        
        // allow multi login check?
		if ($this->EE->config->item('allow_multi_logins') == 'n')
		{

			$this->EE->session->gc_probability = 100;
			$this->EE->session->delete_old_sessions();
            
            $this->EE->db->select('ip_address, user_agent')
                        ->from('exp_sessions')
                        ->where('member_id', $member_id)
                        ->where('last_activity > ', time() - $this->EE->session->session_length);
            if (version_compare(APP_VER, '2.9.0', '<'))
            {            
                        $this->EE->db->where('site_id', $site_id);
            }
            $sess_check = $this->EE->db->get();

			if ($sess_check->num_rows() > 0)
			{
				if ($this->EE->session->userdata['ip_address'] != $sess_check->row('ip_address')  ||  $this->EE->session->userdata['user_agent'] != $sess_check->row('user_agent')  )
				{
					$this->_show_error('general', $this->EE->lang->line('multi_login_warning'), $is_popup);
                    return;
				}
			}
		}

		//start setting cookies
		$this->EE->input->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);
        if (version_compare(APP_VER, '2.2.0', '<'))
        {
    		$this->EE->input->set_cookie($this->EE->session->c_uniqueid , $query->row('unique_id') , $expire);            
    		$this->EE->input->set_cookie($this->EE->session->c_password , $temp_password,  $expire);
        }

        // anonymize?
		if ($this->social_login['anon']==1)
		{
			$this->EE->input->set_cookie($this->EE->session->c_anon);
		}
		else
		{
			$this->EE->input->set_cookie($this->EE->session->c_anon, 1,  $expire);
		}

		$this->EE->session->create_new_session($member_id);

		// -------------------------------------------
		// 'member_member_login_single' hook.
		//  - Additional processing when a member is logging into single site
		//
			$edata = $this->EE->extensions->call('member_member_login_single', $query->row());
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		//stats update
        $enddate = $this->EE->localize->now - (15 * 60);
		$this->EE->db->query("DELETE FROM exp_online_users WHERE site_id = '".$site_id."' AND ((ip_address = '".$this->EE->input->ip_address()."' AND member_id = '0') OR date < ".$enddate.")");
		$data = array(
						'member_id'		=> $member_id,
						'name'			=> ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name'],
						'ip_address'	=> $this->EE->input->ip_address(),
						'date'			=> $this->EE->localize->now,
						'anon'			=> ($this->social_login['anon']==1)?'y':'',
						'site_id'		=> $site_id
					);
		$this->EE->db->update('exp_online_users', $data, array("ip_address" => $this->EE->input->ip_address(), "member_id" => $member_id));

		// now, are there any other sites to log in? 
        if ($this->EE->config->item('allow_multi_logins') == 'y' && $this->EE->config->item('multi_login_sites') != '')
		{
			$sites_list		=  explode('|',$this->EE->config->item('multi_login_sites'));
            $sites_list = array_filter($sites_list, 'strlen');
			$current_site	= $this->EE->functions->fetch_site_index();

			if (count($sites) > 1 && in_array($current_site, $sites))
			{
				$orig = array_search($current_site, $sites_list);
				$next = ($orig == '0') ? '1' : '0';

    			$act_q = $this->EE->db->select('action_id')->from('actions')->where('class', 'Social_login_pro')->where('method', 'access_token')->get();
                $next_qs = array(
    				'ACT'	=> $act_q->row('action_id'),
    				'sid'	=> $session_id,
    				'cur'	=> $next,
    				'orig'	=> $orig,
    				'multi'	=> $this->EE->session->userdata['session_id'],
    				'orig_site_id' => $orig
    			);
    			
    			$next_url = $sites[$next].'?'.http_build_query($next_qs);
    
    			return $this->EE->functions->redirect($next_url);
			}
		}
        
        // success!!
        $return = $this->social_login['return'];

        $this->_clear_session_data($session_id);
			
        if ($is_popup==false)
        {
            return $this->EE->functions->redirect($return);
        }
        else
        {
            $out = "<script type=\"text/javascript\">
window.opener.location = '$return';
window.close();            
</script>";
            echo $out;
        }
   
    }
    
    
    
    function permissions()
    {
        if ($this->EE->session->userdata('member_id')==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        $tagdata = $this->EE->TMPL->tagdata;
        
        $this->EE->db->select('social_login_keys, social_login_permissions')
            ->from('members')
            ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get();
        if ($q->row('social_login_keys')=='' && $this->EE->TMPL->fetch_param('social_only')=='yes')
        {
            return $this->EE->TMPL->no_results();
        }
        
        $site_id = $this->EE->config->item('site_id');
        $permissions = array(
            'entry_submit' => 'y',
            'comment_submit' => 'y'
        );
        
        if ($q->row('social_login_permissions')!='')
        {
            $permissions_a = unserialize($q->row('social_login_permissions'));
            if (isset($permissions_a[$site_id]))
            {
                $permissions = $permissions_a[$site_id];
            }
        }
        if ($this->EE->config->item('forum_is_installed') == "y" && !isset($permissions['forum_submit']))
		{
            $permissions['forum_submit']='y';
        }
        if ($this->EE->config->item('forum_is_installed') != "y")
        {
            unset($permissions['forum_submit']);
        }
        
        $this->EE->db->select('enable_template')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'insert_comment_end')
                        ->limit(1);
        $check_q = $this->EE->db->get();
        if ($check_q->num_rows > 0)
        {
            if ($check_q->row('enable_template')=='n')
            {
                unset($permissions['comment_submit']);
            }
        }
        
        $this->EE->db->select('enable_template')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'entry_submission_absolute_end')
                        ->limit(1);
        $check_q = $this->EE->db->get();
        if ($check_q->num_rows > 0)
        {
            if ($check_q->row('enable_template')=='n')
            {
                unset($permissions['entry_submit']);
            }
        }
        
        if (preg_match("/".LD."permissions".RD."(.*?)".LD.'\/'."permissions".RD."/s", $tagdata, $match))
		{
			$tmpl = $match['1'];
            $rows = '';
            
            foreach ($permissions as $key=>$val)
            {
                $row = $tmpl;
                $row = $this->EE->TMPL->swap_var_single('field_name', $key, $row);
                $row = $this->EE->TMPL->swap_var_single('title', $this->EE->lang->line($key.'_event'), $row);
                if ($val=='y')
                {
                    $row = $this->EE->TMPL->swap_var_single('selected', ' selected="selected"', $row);
                    $row = $this->EE->TMPL->swap_var_single('checked', ' checked="checked"', $row);
                }
                else
                {
                    $row = $this->EE->TMPL->swap_var_single('selected', '', $row);
                    $row = $this->EE->TMPL->swap_var_single('checked', '', $row);
                }
                $rows .= $row;
            }
            
            $tagdata = preg_replace ("/".LD."permissions".RD.".*?".LD.'\/'."permissions".RD."/s", $rows, $tagdata);
		}
              
        $data['action'] = $this->EE->functions->fetch_site_index();

        $data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id('Social_login_pro', 'save_permissions');   
        $data['hidden_fields']['RET'] = ($this->EE->TMPL->fetch_param('return') != "") ? ((strpos($this->EE->TMPL->fetch_param('return'), 'http')===0)?$this->EE->TMPL->fetch_param('return'):$this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'), FALSE)) : $this->EE->functions->fetch_current_uri();            
		$data['name']		= ($this->EE->TMPL->fetch_param('name')!='') ? $this->EE->TMPL->fetch_param('name') : 'social_login_permissions';
        $data['id']		= ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'social_login_permissions';
        $data['class']		= ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : 'social_login_permissions';

        $out = $this->EE->functions->form_declaration($data)."\n".
                $tagdata."\n".
                "</form>";
        
        return $out;
    }
    
    
    function save_permissions()
    {
        if ($this->EE->session->userdata('member_id') == 0)
		{
			$this->_show_error('submission', $this->EE->lang->line('not_authorized'));
            return;
		}	
        
        if (version_compare(APP_VER, '2.7.0', '<'))
        {
        if ($this->EE->config->item('secure_forms') == 'y')
		{
			$this->EE->db->select('COUNT(*) as count');
            $this->EE->db->where('hash', $this->EE->input->post('XID'));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->where('date >', 'UNIX_TIMESTAMP()-7200');
			$results = $this->EE->db->get('security_hashes');				
		
			if ($results->row('count') == 0)
			{
				$this->_show_error('submission', $this->EE->lang->line('not_authorized'));
                return;
			}	
		}
        }
        $site_id = $this->EE->config->item('site_id');
        $permissions[$site_id] = array(
            'entry_submit' => 'y',
            'comment_submit' => 'y',
            'forum_submit' => 'y'
        );

        foreach ($permissions[$site_id] as $key=>$val)
        {
            $permissions[$site_id][$key] = ($this->EE->input->post($key)=='y')?$this->EE->input->post($key):'n';
        }        
        $upd_data['social_login_permissions'] = serialize($permissions);
        
        $this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
        $this->EE->db->update('members', $upd_data);
        
        if (version_compare(APP_VER, '2.7.0', '<'))
        {
        if ($this->EE->config->item('secure_forms') == 'y')
		{
			$this->EE->db->where('hash', $this->EE->input->post('XID'));
			$this->EE->db->where('ip_address', $this->EE->input->ip_address());
			$this->EE->db->or_where('date', 'UNIX_TIMESTAMP()-7200');
			$this->EE->db->delete('security_hashes');				
		}
        }
        
        $data = array(	'title' 	=> $this->EE->lang->line('thank_you'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('preferences_updated'),
						'redirect'	=> $this->EE->input->post('RET'),							
						'link'		=> array($this->EE->input->post('RET'), $this->EE->lang->line('click_if_no_redirect')),
						'rate'		=> 5
					 );
					
		$this->EE->output->show_message($data);
    }
    
    
    function add_userdata()
    {
        if ($this->EE->session->userdata('member_id')==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        $this->EE->db->select('password, email')
                    ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get('members');
        if ($q->row('email')!='' && $q->row('password')!='')
        {
            return $this->EE->TMPL->no_results();
        }
        
        $tmpl = $this->EE->TMPL->tagdata;
        
        if (preg_match("/".LD."email_block".RD."(.*?)".LD.'\/'."email_block".RD."/s", $tmpl, $match))
		{
            if ($q->row('email')=='')
            {
                $tmpl = str_replace ($match['0'], $match['1'], $tmpl);	
            }
            else
            {
                $tmpl = str_replace ($match['0'], "", $tmpl);	
            }			
		}
        
        if (preg_match("/".LD."password_block".RD."(.*?)".LD.'\/'."password_block".RD."/s", $tmpl, $match))
		{
            if ($q->row('password')=='')
            {
                $tmpl = str_replace ($match['0'], $match['1'], $tmpl);	
            }
            else
            {
                $tmpl = str_replace ($match['0'], "", $tmpl);	
            }			
		}
        
        $data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id('Social_login_pro', 'save_userdata');            
		$data['id']		= ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'social_login_userdata_form';
        $data['name']		= ($this->EE->TMPL->fetch_param('name')!='') ? $this->EE->TMPL->fetch_param('name') : 'social_login_userdata_form';
        $data['class']		= ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : 'social_login_userdata_form';

        if ($this->EE->TMPL->fetch_param('return')=='')
        {
            $return = $this->EE->functions->fetch_site_index();
        }
        else if ($this->EE->TMPL->fetch_param('return')=='SAME_PAGE')
        {
            $return = $this->EE->functions->fetch_current_uri();
        }
        else if (strpos($this->EE->TMPL->fetch_param('return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('return'), "https://")!==FALSE)
        {
            $return = $this->EE->TMPL->fetch_param('return');
        }
        else
        {
            $return = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'));
        }

        $data['hidden_fields']['RET'] = $return;


        $out  = $this->EE->functions->form_declaration($data).$tmpl."</form>";
        
        return $out;
        
    }
    
    function save_userdata()
    {
        $this->EE->lang->loadfile('myaccount');
        $this->EE->lang->loadfile('member');
        
        $xtra_msg = '';
        
        if ($this->EE->session->userdata('member_id')==0)
        {
            $this->_show_error('general', $this->EE->lang->line('unauthorized_access'));
            return;
        }
        
        $this->EE->db->select('password, email')
                    ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get('members');
        if (($q->row('email')!='' && $q->row('password')!='') || ($q->row('email')!='' && isset($_POST['email']) && $_POST['email']!='') || ($q->row('password')!='' && isset($_POST['password']) && $_POST['password']!=''))
        {
            $this->_show_error('general', $this->EE->lang->line('unauthorized_access'));
            return;
        }
        
        if ($this->EE->input->post('email')==false && $this->EE->input->post('password')==false)
        {
            $this->_show_error('general', $this->EE->lang->line('no_data_for_update'));
            return;  
        }
        
        $data = array();
        //	Validate submitted data
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}

		$this->EE->VAL = new EE_Validate(
								array(
										'member_id'			=> $this->EE->session->userdata('member_id'),
										'val_type'			=> 'new', // new or update
										'fetch_lang'		=> FALSE,
										'require_cpw'		=> FALSE,
										'enable_log'		=> TRUE,
										'email'				=> $this->EE->input->post('email'),
                                        'password'			=> $this->EE->input->post('password'),
							            'password_confirm'	=> $this->EE->input->post('password_confirm')
									 )
							);
        if (isset($_POST['email']) && $_POST['email']!='')
        {
            $this->EE->VAL->validate_email();
            $data['email'] = $this->EE->input->post('email');
        }
        
        if (isset($_POST['password']) && $_POST['password']!='')
        {
            $this->EE->VAL->validate_password();
        }

		if (count($this->EE->VAL->errors) > 0)
		{
			$this->_show_error('general', $this->EE->VAL->show_errors());
		}
		
		if (isset($_POST['password']) && $_POST['password']!='')
        {
			$this->EE->load->library('auth');
			$this->EE->auth->update_password($this->EE->session->userdata('member_id'),
											 $this->EE->input->post('password'));
	 	}
        
        if (!empty($data))
        {
	        // We generate an authorization code if the member needs to self-activate
	        // Send user notifications
			if (isset($data['email']) && $this->EE->config->item('req_mbr_activation') == 'email')
			{
				$data['authcode'] = $this->EE->functions->random('alnum', 10);
				$action_id  = $this->EE->functions->fetch_action_id('Member', 'activate_member');
	
				$swap = array(
					'name'				=> $this->EE->session->userdata('screen_name'),
					'activation_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'],
					'site_name'			=> stripslashes($this->EE->config->item('site_name')),
					'site_url'			=> $this->EE->config->item('site_url'),
					'username'			=> $this->EE->session->userdata('username'),
					'email'				=> $data['email']
				 );
	
				$template = $this->EE->functions->fetch_email_template('mbr_activation_instructions');
				$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
				$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
	
				// Send email
				$this->EE->load->helper('text');
	
				$this->EE->load->library('email');
				$this->EE->email->wordwrap = true;
				$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
				$this->EE->email->to($data['email']);
				$this->EE->email->subject($email_tit);
				$this->EE->email->message(entities_to_ascii($email_msg));
				$this->EE->email->Send();
	
				$xtra_msg = BR.lang('mbr_membership_instructions_cont');
			}
			
			$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
	        $this->EE->db->update('members', $data);
			
        }
        
        $zoo = $this->EE->db->select('module_id')->from('modules')->where('module_name', 'Zoo_visitor')->get(); 
        if ($zoo->num_rows()>0)
        {
        	$this->EE->load->add_package_path(PATH_THIRD.'zoo_visitor/');
			$this->EE->load->library('zoo_visitor_lib');
			$this->EE->zoo_visitor_lib->sync_member_data();
			$this->EE->load->remove_package_path(PATH_THIRD.'zoo_visitor/');
        }
        
        // User is quite widespread, so we'll add user hook here
        /* -------------------------------------------
		/* 'user_edit_end' hook.
		/*  - Do something when a user edits their profile
		/*  - Added $cfields for User 2.1
		*/
			$edata = $this->EE->extensions->call('user_edit_end', $this->EE->session->userdata('member_id'), $data, array());
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
        
        $data = array(	'title' 	=> $this->EE->lang->line('profile_updated'),
        				'heading'	=> $this->EE->lang->line('profile_updated'),
        				'content'	=> $this->EE->lang->line('mbr_profile_has_been_updated').$xtra_msg,
        				'redirect'	=> $_POST['RET'],
        				'link'		=> array($_POST['RET'], $this->EE->config->item('site_name')),
                        'rate'		=> 5
        			 );
			
		$this->EE->output->show_message($data);
        
    }     
    
    function _random_string($length = 16, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
    {
        // Length of character list
        $chars_length = (strlen($chars) - 1);
    
        // Start our string
        $string = $chars[rand(0, $chars_length)];
        
        // Generate random string
        for ($i = 1; $i < $length; $i++)
        {
            // Grab a random character from our list
            $r = $chars[rand(0, $chars_length)];
            
            // Make sure the same two characters don't appear next to each other
            //if ($r != $string{$i - 1}) $string .=  $r;
            $string .=  $r;
        }
        
        // Return the string
        return $string;
    }        
    
    
    function _show_error($type='general', $message, $is_popup = false)
    {
        if ($is_popup==true)
        {
            $data = array(	'title' 	=> ($type=='general')?$this->EE->lang->line('general_error'):$this->EE->lang->line('submission_error'),
    						'heading'	=> ($type=='general')?$this->EE->lang->line('general_error'):$this->EE->lang->line('submission_error'),
    						'content'	=> $message
					 );
					
		  $this->EE->output->show_message($data);
        }
        else
        {
            $this->EE->output->show_user_error($type, $message);
        }
    }
    
    function _clear_session_data($session_id)
    {
    	if ($session_id=='') $session_id = session_id(); //fallback
		$this->EE->db->where('session_id', $session_id);
		$this->EE->db->or_where('set_date < ', $this->EE->localize->now - 2*60*60); //and remove records older than 2 hours
    	$this->EE->db->delete('social_login_session_data');
    }
    
    function _save_session_data($data, $session_id)
    {
		//if ($session_id=='') $session_id = session_id(); //fallback
		if (isset($data['session_id'])) unset($data['session_id']);
		$insert = array(
			'session_id'	=>	$session_id,
			'set_date'		=>	$this->EE->localize->now,
			'data'			=>	serialize($data)
		);
		$sql = $this->EE->db->insert_string('social_login_session_data', $insert);
     	$sql .= " ON DUPLICATE KEY UPDATE data='".$this->EE->db->escape_str($insert['data'])."'";
      	$this->EE->db->query($sql);
    }
    
    function _get_session_data($session_id)
    {
    	//if ($session_id=='') $session_id = session_id(); //fallback
		$this->EE->db->select('data');
		$this->EE->db->where('session_id', $session_id);
		$query = $this->EE->db->get('social_login_session_data');
		$data = array();
		if ($query->num_rows()>0)
		{
			foreach ($query->result_array() as $row)
			{
				$data = unserialize($row['data']);
			}
			$data['session_id'] = $session_id;
		}
		return $data;
    }



}
/* END */
?>