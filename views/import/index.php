<?php
/** @var string[] $errors */

if (count($errors) > 0) {
    echo '<ul>';
    foreach ($errors as $error):
        echo '<li>'.htmlReady($error).'</li>';
    endforeach;
    echo '</ul>';
}
?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="import_file">

    <div>
        <?php
        echo Studip\Button::createAccept();
        echo Studip\Button::createCancel();
        ?>
    </div>
</form>
