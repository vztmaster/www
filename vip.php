<?php

require 'Cards_vip.php';

if ($_GET['step'] == "") {
?>
    <a href="vip.php?step=upload_vip">Форма для загрузки файла</a><br>
    <a href="vip.php?step=activation_vip">Форма для активации карты</a><br>
    <a href="vip.php?step=statistics_vip">Статистика</a><br>
    <a href="vip.php?step=operator_vip">Форма для оператора</a><br><br>
    
     <a href="index.php">Вернуться на выбор карт</a><br>
<?php
} elseif ($_GET['step'] == "upload_vip") {
?>    
    <h2><b> Форма для загрузки файлов </b></h2>
        <form action="vip.php?step=upload_vip2" method="post" enctype="multipart/form-data">
            Введите номинал <input type="text" name="nominal"><br>
            Введите номер партии <input type="text" name="set_card"><br>
            <input type="file" name="filename"><br><br>
            <input type="submit" value="Загрузить"><br>
        </form> 
    <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php     
} elseif ($_GET['step'] == "upload_vip2") {
    $obj = new Cards();
    print $obj->upload($_FILES, $_POST['set_card'], $_POST['nominal']);
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php   
} elseif ($_GET['step'] == "activation_vip") {
?>
    <h2><b> Форма для активации карты </b></h2>
        <form name="activation_form" action="vip.php?step=activation_vip2" method="post" enctype="multipart/form-data" onsubmit="return validate_form( );">
            Имя <input type="text" name="name"><br>
            Номер карты <input type="text" name="number"><br>
            email <input type="text" name="email"><br><br>
            <input type="submit" value="Активация"><br>
        </form> 
    <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>    
<?php     
} elseif ($_GET['step'] == "activation_vip2") {
    $obj = new Cards();
    print $obj->activation($_POST['name'], $_POST['email'], $_POST['number']);
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php 
} elseif ($_GET['step'] == "statistics_vip") {
    $obj = new Cards();
    print $obj->statistics_vip();
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php
} elseif ($_GET['step'] == "operator_vip") {
?>
    <h2><b> Форма для активации карты </b></h2>
        <form name="activation_form" action="vip.php?step=operator_vip2" method="post" enctype="multipart/form-data" onsubmit="return validate_form( );">
            Введите номер карты <input type="text" name="number"><br>
            <input type="submit" value="Проверка"><br>
        </form> 
    <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>    
<?php
} elseif ($_GET['step'] == "operator_vip2") {
    $obj = new Cards();
    print $obj->check_activated_step_1($_POST['number']);
    unset ($obj);
?>  <br><br>  <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php
} elseif ($_GET['step'] == "operator_vip3") {
    $obj = new Cards();
    print $obj->check_activated_step_2($_POST['number'], $_POST['name'], $_POST['email'], $_POST['nominal'], $_POST['set_card']);
    unset ($obj);
?>  <br><br>  <a href='http://46.188.37.109/vip.php'>Вернуться на главную страницу</a>
<?php
} else {
    print "Неверная страница.";
}
?>

