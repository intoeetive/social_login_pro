<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login_pro'.AMP.'method=save_templates');?>

<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login_pro'.AMP.'method=index'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login_pro'.AMP.'method=templates'?>"><?=lang('posting_templates')?></a>  </li> 

</ul> 
<div class="clear_left shun"></div> 

<?php 

foreach ($data as $name=>$rows)
{
    echo "<h3 class=\"accordion ui-accordion-header ui-helper-reset ui-state-active ui-corner-top\">".lang($name."_tmpl_name")."</h3>";
    
    $this->table->set_template($cp_pad_table_template);
    
    foreach ($rows as $key => $val)
    {
    	$this->table->add_row($val);
    }
    
    echo $this->table->generate();
    $this->table->clear();
}
?>


<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?php
form_close();

