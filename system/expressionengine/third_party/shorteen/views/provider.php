<div class="editAccordion open">
<h3><?=$name?></h3>
    <div>
        <table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
        <?php foreach($fields as $parts): ?>
            <tr>
                <td style="width: 50%"><?=$parts['label']?></td>
                <td><?=$parts['field']?></td>
            </tr>
        <?php endforeach;?>
        </table>
    </div>
</div>