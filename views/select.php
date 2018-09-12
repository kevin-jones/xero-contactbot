<h1>Confirm and Create New Contact</h1>
<form action="/?a=create" method=post>
    <?php foreach ($bot->fields as $field => $value): ?>
    <div class=form-group>
        <label><?=$field?></label>
        <div>
            <input class=form-control type=text name="contact[<?=$field?>]" value="<?=$value?>">
        </div>
    </div>
    <?php endforeach; ?>

    <input class="btn btn-success" name=select type=submit value="Create in Xero">
</form>

