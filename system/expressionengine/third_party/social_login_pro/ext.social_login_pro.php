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
 File: ext.social_login_pro.php
-----------------------------------------------------
 Purpose: Integration of EE membership with social networks
=====================================================
*/
//error_reporting(E_ALL);
if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'social_login_pro/config.php';

class Social_login_pro_ext {

	var $name	     	= SOCIAL_LOGIN_PRO_ADDON_NAME;
	var $version 		= SOCIAL_LOGIN_PRO_ADDON_VERSION;
	var $description	= 'Integration of EE membership with social networks';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/social_login_pro.html';
    
    var $settings 		= array();
    var $max_link_length = 25;
    var $providers = array('twitter', 'facebook', 'linkedin', 'yahoo', 'appdotnet', 'google');
    var $maxlen 		= array(
                                'twitter'   => 140,
                                'yahoo'     => 140,
                                'facebook'  => 420,
                                'linkedin'  => 700,
                                'appdotnet' => 210,
                                'google'    => 700
                            );
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();        
        $query = $this->EE->db->query("SELECT * FROM exp_modules WHERE module_name='Social_login_pro' LIMIT 1");
        if ($query->num_rows()>0 && $query->row('settings')!='') $this->settings = unserialize($query->row('settings')); 
        $this->EE->lang->loadfile('social_login_pro');
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		//submit an entry
            array(
    			'hook'		=> 'entry_submission_absolute_end',
    			'method'	=> 'entry_submit',
    			'priority'	=> 10
    		),
            /*array(
    			'hook'		=> 'safecracker_submit_entry_end',
    			'method'	=> 'entry_submit',
    			'priority'	=> 10
    		),*/
            array(
    			'hook'		=> 'channel_form_submit_entry_end',
    			'method'	=> 'entry_submit',
    			'priority'	=> 10
    		),
            //submit a comment
            array(
    			'hook'		=> 'insert_comment_end',
    			'method'	=> 'comment_submit',
    			'priority'	=> 10
    		),
            //submit a forum post
            array(
    			'hook'		=> 'forum_submit_post_end',
    			'method'	=> 'forum_submit',
    			'priority'	=> 10
    		),
            //member registered from fronentd
            array(
    			'hook'		=> 'member_member_register',
    			'method'	=> 'member_register',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'zoo_visitor_register_end',
    			'method'	=> 'member_register',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'user_register_end',
    			'method'	=> 'member_register',
    			'priority'	=> 10
    		)
    		
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	

    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	if ($current < 0.9)
    	{
    		$hooks = array(
	    		array(
	    			'hook'		=> 'zoo_visitor_register_end',
	    			'method'	=> 'member_register',
	    			'priority'	=> 10
	    		),
	    		array(
	    			'hook'		=> 'user_register_end',
	    			'method'	=> 'member_register',
	    			'priority'	=> 10
	    		)
	    		
	    	);
	    	
	        foreach ($hooks AS $hook)
	    	{
	    		$data = array(
	        		'class'		=> __CLASS__,
	        		'method'	=> $hook['method'],
	        		'hook'		=> $hook['hook'],
	        		'settings'	=> '',
	        		'priority'	=> $hook['priority'],
	        		'version'	=> $this->version,
	        		'enabled'	=> 'y'
	        	);
	            $this->EE->db->insert('extensions', $data);
	    	}	
    	}
    	
    	
    	if ($current < 1.1)
    	{
    		$hooks = array(
	    		//submit an entry
	            array(
	    			'hook'		=> 'safecracker_submit_entry_end',
	    			'method'	=> 'entry_submit',
	    			'priority'	=> 10
	    		)	
	    	);
	    	
	        foreach ($hooks AS $hook)
	    	{
	    		$data = array(
	        		'class'		=> __CLASS__,
	        		'method'	=> $hook['method'],
	        		'hook'		=> $hook['hook'],
	        		'settings'	=> '',
	        		'priority'	=> $hook['priority'],
	        		'version'	=> $this->version,
	        		'enabled'	=> 'y'
	        	);
	            $this->EE->db->insert('extensions', $data);
	    	}
    	}
    	
    	
    	    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');        
                    
    }
    
    
    function settings()
    {
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login_pro');	
    }
        
    
    function member_register($data, $member_id)
    {
    	@session_start();
        
        $site_id = $this->EE->config->item('site_id');
        
        //template enabled?
        $this->EE->db->select('enable_template, template_data')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'member_member_register')
                        ->limit(1);
        $q = $this->EE->db->get();
        if ($q->num_rows > 0)
        {
            if ($q->row('enable_template')=='n')
            {
                return false;
            }
            $tmpl = $q->row('template_data');
        }
        else
        {
            $tmpl = $this->EE->lang->line('member_member_register_tmpl');
        }
        
        //get the keys
        $this->EE->db->select('social_login_keys')
            ->from('members')
            ->where('member_id', $member_id);
        $q = $this->EE->db->get();
        if ($q->num_rows()==0 || $q->row('social_login_keys')=='')
        {
            return false;
        }
        $keys = unserialize($q->row('social_login_keys'));
        
        $msg= str_replace(LD.'site_name'.RD, $this->EE->config->item('site_name'), trim($tmpl));
        $msg= str_replace(LD.'site_url'.RD, $this->EE->config->item('site_url'), $msg);

        $this->_post($msg, $keys);

    }
    
    
    
    function entry_submit($entry_id, $meta=false, $data=false, $orig_var=false)
    {
    	$site_id = $this->EE->config->item('site_id');

		if (!is_numeric($entry_id))
    	{
    		//sefecarcker call!
    		$meta = $data = $entry_id->entry;
    		if ($this->EE->input->post('entry_id'))
    		{
    			return false;
    		}
    	}
    	else
    	{
    		//CP call
    		if ($data['entry_id']!=0)
	        {
	            return false;
	        }
    	}
    	
    	$trigger_statuses = (isset($this->settings[$site_id]['trigger_statuses'])?$this->settings[$site_id]['trigger_statuses']:array('open'));
    	
		if (!isset($meta['status']) || !in_array($meta['status'], $trigger_statuses))
        {
            return false;
        }       
        
        @session_start();

        //get the keys
        $this->EE->db->select('social_login_keys, social_login_permissions')
            ->from('members')
            ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get();
        if ($q->num_rows()==0 || $q->row('social_login_keys')=='')
        {
            return false;
        }
        $keys = unserialize($q->row('social_login_keys'));
        if ($q->row('social_login_permissions')!='')
        {
            $permissions = unserialize($q->row('social_login_permissions'));
            if (isset($permissions[$site_id]['entry_submit']) && $permissions[$site_id]['entry_submit']=='n')
            {
                return false;
            }
        }

        //template enabled?
        $this->EE->db->select('enable_template, template_data')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'entry_submission_absolute_end')
                        ->limit(1);
        $q = $this->EE->db->get();
        if ($q->num_rows > 0)
        {
            if ($q->row('enable_template')=='n')
            {
				return false;
            }
            $tmpl = $q->row('template_data');
        }
        else
        {
            $tmpl = $this->EE->lang->line('entry_submission_absolute_end_tmpl');
        }

        //prepare the message
        $msg = str_replace(LD.'site_name'.RD, $this->EE->config->item('site_name'), trim($tmpl));
        $msg = str_replace(LD.'title'.RD, $meta['title'], $msg);
        $msg = str_replace(LD.'url_title'.RD, $meta['url_title'], $msg);

        $this->EE->db->select('channel_title, channel_name, channel_url, comment_url');
        $this->EE->db->from('channels');
        $this->EE->db->where('channel_id', $data['channel_id']);
        $channel = $this->EE->db->get();
        $basepath = ($channel->row('comment_url')!='') ? $channel->row('comment_url') : $channel->row('channel_url');
        $basepath = rtrim($basepath, '/').'/';
        
        $msg = str_replace(LD.'channel_short_name'.RD, $channel->row('channel_name'), $msg);
        $msg = str_replace(LD.'channel'.RD, $channel->row('channel_title'), $msg);
        $msg = str_replace(LD.'permalink'.RD, $basepath.$meta['url_title'], $msg);
        $msg = str_replace(LD.'title_permalink'.RD, $basepath.$meta['url_title'], $msg);
        $msg = str_replace(LD.'entry_id_permalink'.RD, $basepath.$data['entry_id'], $msg);
        
        $custom_fields = array();
        $q = $this->EE->db->select('field_id, field_name')
				->from('exp_channel_fields')
				->where('site_id', $site_id)
				->get();
        foreach ($q->result() as $obj)
        {
            $custom_fields[$obj->field_id] = $obj->field_name;
        }        
        
        foreach ($data as $field=>$val)
        {
        	if (strpos($field, 'field_id_')!==false)
        	{
        		$field_id = str_replace('field_id_', '', $field);
				$msg = str_replace(LD.$custom_fields[$field_id].RD, $val, $msg);
        	}
        }
        
        $this->_post($msg, $keys);

    }    
    
    


    function comment_submit($data, $comment_moderate, $comment_id)
    {
        if ($comment_moderate=='y')
        {
            return false;
        }
        
        $this->EE->db->select('url_title, title, status');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where('entry_id', $data['entry_id']);
        $entry = $this->EE->db->get();
        if ($entry->row('status')!='open')
        {
            return false;
        }
        
        @session_start();
        
        $site_id = $this->EE->config->item('site_id');
        
        //get the keys
        $this->EE->db->select('social_login_keys, social_login_permissions')
            ->from('members')
            ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get();
        if ($q->num_rows()==0 || $q->row('social_login_keys')=='')
        {
            return false;
        }
        $keys = unserialize($q->row('social_login_keys'));
        if ($q->row('social_login_permissions')!='')
        {
            $permissions = unserialize($q->row('social_login_permissions'));
            if (isset($permissions[$site_id]['comment_submit']) && $permissions[$site_id]['comment_submit']=='n')
            {
                return false;
            }
        }
        
        //template enabled?
        $this->EE->db->select('enable_template, template_data')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'insert_comment_end')
                        ->limit(1);
        $q = $this->EE->db->get();
        if ($q->num_rows > 0)
        {
            if ($q->row('enable_template')=='n')
            {
                return false;
            }
            $tmpl = $q->row('template_data');
        }
        else
        {
            $tmpl = $this->EE->lang->line('insert_comment_end_tmpl');
        }

        //prepare the message
        $msg = str_replace(LD.'site_name'.RD, $this->EE->config->item('site_name'), trim($tmpl));
        $msg = str_replace(LD.'title'.RD, $entry->row('title'), $msg);
        $msg = str_replace(LD.'url_title'.RD, $entry->row('url_title'), $msg);
        $msg = str_replace(LD.'comment'.RD, $data['comment'], $msg);
        $msg = str_replace(LD.'comment_id'.RD, $comment_id, $msg);

        $this->EE->db->select('channel_title, channel_name, channel_url, comment_url');
        $this->EE->db->from('channels');
        $this->EE->db->where('channel_id', $data['channel_id']);
        $channel = $this->EE->db->get();
        $basepath = ($channel->row('comment_url')!='') ? $channel->row('comment_url') : $channel->row('channel_url');
        $basepath = rtrim($basepath, '/').'/';
        
        $msg = str_replace(LD.'channel_short_name'.RD, $channel->row('channel_name'), $msg);
        $msg = str_replace(LD.'channel'.RD, $channel->row('channel_title'), $msg);
        $msg = str_replace(LD.'permalink'.RD, $basepath.$entry->row('url_title'), $msg);
        $msg = str_replace(LD.'title_permalink'.RD, $basepath.$entry->row('url_title'), $msg);
        $msg = str_replace(LD.'entry_id_permalink'.RD, $basepath.$data['entry_id'], $msg);
        
        $this->_post($msg, $keys);

    } 




    function forum_submit($obj, $data)
    {

        if (!isset($data['status']))
        {
            return false;
        }
        
        if ($obj->forum_metadata[$data['forum_id']]['forum_status']!='o')
        {
            return false;
        }
        
        @session_start();
        
        $site_id = $this->EE->config->item('site_id');
        
        //get the keys
        $this->EE->db->select('social_login_keys, social_login_permissions')
            ->from('members')
            ->where('member_id', $this->EE->session->userdata('member_id'));
        $q = $this->EE->db->get();
        if ($q->num_rows()==0 || $q->row('social_login_keys')=='')
        {
            return false;
        }
        $keys = unserialize($q->row('social_login_keys'));
        if ($q->row('social_login_permissions')!='')
        {
            $permissions = unserialize($q->row('social_login_permissions'));
            if (isset($permissions[$site_id]['forum_submit']) && $permissions[$site_id]['forum_submit']=='n')
            {
                return false;
            }
        }
        
        //template enabled?
        $this->EE->db->select('enable_template, template_data')
                        ->from('social_login_templates')
                        ->where('site_id', $site_id)
                        ->where('template_name', 'forum_submit_post_end')
                        ->limit(1);
        $q = $this->EE->db->get();
        if ($q->num_rows > 0)
        {
            if ($q->row('enable_template')=='n')
            {
                return false;
            }
            $tmpl = $q->row('template_data');
        }
        else
        {
            $tmpl = $this->EE->lang->line('forum_submit_post_end_tmpl');
        }

        //prepare the message
        $msg = str_replace(LD.'site_name'.RD, $this->EE->config->item('site_name'), trim($tmpl));
        $msg = str_replace(LD.'title'.RD, $data['title'], $msg);

        $basepath = $obj->preferences['board_forum_url'];
        $basepath = rtrim($basepath, '/').'/';
        
        $msg = str_replace(LD.'forum_name'.RD, $obj->forum_metadata[$data['forum_id']]['forum_name'], $msg);
        $msg = str_replace(LD.'forum_id'.RD, $data['forum_id'], $msg);
        $msg = str_replace(LD.'board_name'.RD, $obj->preferences['board_name'], $msg);
        $msg = str_replace(LD.'board_id'.RD, $data['board_id'], $msg);
        $msg = str_replace(LD.'permalink'.RD, $basepath.'viewthread/'.$data['topic_id'], $msg);
        
        $this->_post($msg, $keys);
        
    } 




    
    
    //trims the string to be exactly of less of the given length
    //the integrity of words is kept 
    function _char_limit($str, $length, $minword = 3)
    {
        $sub = '';
        $len = 0;
       
        foreach (explode(' ', $str) as $word)
        {
            $part = (($sub != '') ? ' ' : '') . $word;
            $sub .= $part;
            $len += strlen($part);
           
            if (strlen($word) > $minword && strlen($sub) >= $length)
            {
                break;
            }
        }
       
        return $sub . (($len < strlen($str)) ? '...' : '');

    }
    
    
    
    function _post($msg_orig, $keys)
    {
        $site_id = $this->EE->config->item('site_id');

        foreach ($this->providers as $provider)
        {
            if (!isset($keys["$provider"]['oauth_token']) || $keys["$provider"]['oauth_token']=='')
            {
                continue;
            }
            if ($this->settings[$site_id][$provider]['app_id']=='' || $this->settings[$site_id][$provider]['app_secret']=='' || $this->settings[$site_id][$provider]['custom_field']=='')
            {
                continue;
            }

            if (!isset($this->settings[$site_id][$provider]['enable_posts']) || $this->settings[$site_id][$provider]['enable_posts']=='y')
            {
                $shorturl = '';
				$msg = $msg_orig;
				
				//get at least one url
				preg_match_all('/https?:\/\/[^:\/\s]{3,}(:\d{1,5})?(\/[^\?\s]*)?([\?#][^\s]*)?/i', $msg, $matches);
                foreach ($matches as $match)
                {
                    if (!empty($match) && strpos($match[0], 'http')===0)
                    {
                        $shorturl = $match[0];
                    }
				}
					
                if (strlen($msg)>$this->maxlen[$provider])
                {
                    if ( ! class_exists('Shorteen'))
                	{
                		require_once PATH_THIRD.'shorteen/mod.shorteen.php';
                	}
                	
                	$SHORTEEN = new Shorteen();
                    
                    preg_match_all('/https?:\/\/[^:\/\s]{3,}(:\d{1,5})?(\/[^\?\s]*)?([\?#][^\s]*)?/i', $msg, $matches);

                    foreach ($matches as $match)
                    {
                        if (!empty($match) && strpos($match[0], 'http')===0)
                        {
                            //truncate urls
                            $longurl = $match[0];
                            if (strlen($longurl)>$this->max_link_length)
                            {
                                $shorturl = $SHORTEEN->process($this->settings[$site_id]['url_shortening_service'], $longurl, true);
                                if ($shorturl!='')
                                {
                                    $msg = str_replace($longurl, $shorturl, $msg);
                                }
                            }
                        }
                    }
                }
                //still too long? truncate the message
                //at least one URL should always be included
                if (strlen($msg)>$this->maxlen[$provider])
                {
                    if ($shorturl!='')
                    {
                        $len = $this->maxlen[$provider] - strlen($shorturl) - 1;
                        $msg = $this->_char_limit($msg, $len);
                        $msg .= ' '.$shorturl;
                    }
                    else
                    {
                        $msg = $this->_char_limit($msg, $this->maxlen[$provider]);
                    }
                }
                
                //all is ready! post the message
                $lib = $provider.'_oauth';
                $params = array('key'=>$this->settings[$site_id]["$provider"]['app_id'], 'secret'=>$this->settings[$site_id]["$provider"]['app_secret']);
                $this->EE->load->library($lib, $params);
                if ($provider=='yahoo')
                {
                    $this->EE->$lib->post($msg, $shorturl, $keys["$provider"]['oauth_token'], $keys["$provider"]['oauth_token_secret'], array('guid'=>$keys["$provider"]['guid']));
                }
                else
                {
                    $this->EE->$lib->post($msg, $shorturl, $keys["$provider"]['oauth_token'], $keys["$provider"]['oauth_token_secret']);    
                }
            }
        }
    }


  

}
// END CLASS
