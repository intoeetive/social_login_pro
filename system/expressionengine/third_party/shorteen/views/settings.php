<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=shorteen'.AMP.'method=save_settings');?>

<table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
    <tr>
        <td style="width: 50%"><?=lang('shorteen_secret')?></td>
        <td><?=$shorteen_secret?></td>
    </tr>
</table>

<?=$providers?>

<p><?=form_submit('submit', lang('save'), 'class="submit"')?></p>

<?php
form_close();