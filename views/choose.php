<h1>Select your contact info</h1>
<p>Ensure the address has commas between lines and starts with the company name</p>
<form method=post action="/?a=select">
    <input type=hidden name=url value="<?=$bot->url?>">
        <?php foreach (['tel', 'email', 'postcode', 'address'] as $field): ?>
        <?php $data = $field == 'address' ? $bot->{$field} : $bot->fields[$field]; ?>

        <?php if (!empty($data)): $count = count($data); ?>
        <div class="row mb-4">
            <div class=col>
                <div class=card>
                    <div class="card-header">
                        <?=$field?>
                    </div>
                    <div class=card-body>
                        <?php foreach ($data as $key => $value): ?>
                        <div class=form-group>
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><input type=radio name="selections[<?=$field?>]" value="<?=$key?>" <?= ($count === 1 ? 'checked' : '') ?>></div>
                                </div>
                                <input class=form-control type=text name="values[<?=$field?>][<?=$key?>]" value="<?=$value?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

    <input class="btn btn-primary" name=select type=submit value="Save">
</form>
<br>
<br>
