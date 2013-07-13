<div class="editAccordion open<?php if ($empty) echo ' collapsed';?>"> 
<h3<?php if ($empty) echo ' class="collapsed"';?>><?=$name.' '.lang('settings')?></h3> 
    <div<?php if ($empty) echo ' style="display: none;"';?>> 
        <table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0" style="margin: 0;"> 
        <tr> 
            <td colspan="2"><?=lang("get_credentials_here").' <a href="'.$app_register_url.'" target="_blank">'.$app_register_url.'</a><br />'.lang("more_info").' <a href="'.$docs_url.'" target="_blank">'.$docs_url.'</a>'?></td>
        </tr> 
        <?php foreach($fields as $parts): ?> 
            <tr> 
                <td style="width: 50%"><?=$parts['label'].'<br /><small>'.$parts['subtext']; ?> </small></td>
                <td><?=$parts['field']?></td> 
            </tr> 
        <?php endforeach;?> 
        </table> 
    </div> 
</div> 