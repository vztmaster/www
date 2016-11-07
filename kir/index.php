<?php

require 'med.php';

$obj = new Med();
$obj->receipt_date(); ?>


<h2><b> Форма для загрузки файлов </b></h2>
    <form action="vip.php?step=upload_vip2" method="post" enctype="multipart/form-data">
        Введите номинал <input type="text" name="nominal"><br>
        Введите номер партии <input type="text" name="set_card"><br>
        <input type="file" name="filename"><br><br>
        <input type="submit" value="Загрузить"><br>
    </form>



