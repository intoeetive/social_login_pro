<?php

/*
=====================================================
 Shorteen
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2012-2013 Yuri Salimovskiy
 Lessn More support by Jerome Brown
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: mcp.shorteen.php
-----------------------------------------------------
 Purpose: Shorten your URLs using wide range of shortening services
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'shorteen/config.php';

class Shorteen_mcp {

    var $version = SHORTEEN_ADDON_VERSION;

    var $settings = array();

    var $docs_url = "https://github.com/intoeetive/shorteen/blob/master/README.md";

    public $providers = array(
                        'googl'=>array(
                            'api_key'
                        ),
                        'bitly'=>array(
                            'access_token'
                        ),
                        'yourls'=>array(
                            'signature',
                            'install_url'
                        ),
                        'lessn-more'=>array(
                            'api_key',
                            'install_url'
                        ),
                        'cloud-app'=>array(
                            'email',
                            'password'
                        )
                    );

    function __construct() {
        // Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Shorteen' LIMIT 1");
        $this->settings = unserialize($query->row('settings'));
    }

    function index()
    {
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
        $this->EE->load->library('javascript');

        $outputjs = "
            $(\".editAccordion\").css(\"borderTop\", $(\".editAccordion\").css(\"borderBottom\"));
            $(\".editAccordion h3\").click(function() {
                if ($(this).hasClass(\"collapsed\")) {
                    $(this).siblings().slideDown(\"fast\");
                    $(this).removeClass(\"collapsed\").parent().removeClass(\"collapsed\");
                } else {
                    $(this).siblings().slideUp(\"fast\");
                    $(this).addClass(\"collapsed\").parent().addClass(\"collapsed\");
                }
            });
        ";

        $providers_view = '';
        $providers = array();

        foreach ($this->providers as $provider_name=>$provider_fields)
        {
            $data['name'] = lang($provider_name);
            $data['fields'] = array();

            foreach ($provider_fields as $field)
            {
                $field_type = ($field == 'password' ? 'password' : 'input');
                $data['fields'][] = array(
                    'label'=>lang($field),
                    'field'=>call_user_func('form_' . $field_type, $field."[$provider_name]", (isset($this->settings[$provider_name][$field])?$this->settings[$provider_name][$field]:''), 'style="width: 80%"')
                );
            }

            $providers_view .= $this->EE->load->view('provider', $data, TRUE);
        }

        $vars = array();
        $vars['providers'] = $providers_view;
        $vars['shorteen_secret'] = form_input('shorteen_secret', (isset($this->settings['shorteen_secret'])?$this->settings['shorteen_secret']:''), 'style="width: 80%"');

        $this->EE->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));
        
        if (version_compare(APP_VER, '2.6.0', '>='))
        {
        	$this->EE->view->cp_page_title = lang('shorteen_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('shorteen_module_name'));
        }
        

    	return $this->EE->load->view('settings', $vars, TRUE);

    }


    function save_settings()
    {

        $site_id = $this->EE->config->item('site_id');

        foreach ($this->providers as $provider_name=>$provider_fields)
        {
            foreach ($provider_fields as $field)
            {
                $settings[$provider_name][$field] = $_POST["$field"]["$provider_name"];
            }
        }
        
        $settings['shorteen_secret'] = $this->EE->input->post('shorteen_secret');

        $this->EE->db->where('module_name', 'Shorteen');
        $this->EE->db->update('modules', array('settings' => serialize($settings)));

        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules');

    }



}
/* END */
?>