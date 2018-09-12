<h1>Saving Contact</h1>

<?php if ($bot->status != 200): ?>
    <p>Error saving contact.</p>
<?php else: ?>
    <p>Contact saved successfully</p>
    <p><a class="btn btn-primary" href="https://go.xero.com/Contacts/View/<?=$bot->contact->ContactID?>" target=_blank>Contact Record in Xero</a></p>
<?php endif; ?>

