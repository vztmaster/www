<?php

require 'Cards.php';

if ($_GET['step'] == "") {
?>
<a href="index.php?step=upload">Форма для загрузки файла</a><br>
<a href="index.php?step=manager">Форма для привязки менеджера</a><br>
<a href="index.php?step=activation">Форма для активации карты</a><br>
<a href="index.php?step=statistics">Статистика менеджеров</a><br>
<?php
} elseif ($_GET['step'] == "upload") {
?>    
    <h2><b> Форма для загрузки файлов </b></h2>
        <form action="index.php?step=upload2" method="post" enctype="multipart/form-data">
            Введите номер партии <input type="text" name="set_card"><br>
            <input type="file" name="filename"><br><br>
            <input type="submit" value="Загрузить"><br>
        </form> 
    <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php    
} elseif ($_GET['step'] == "manager") {
?>   
    <h2><b> Форма для привязки менеджера </b></h2>
        <form name="activation_form" action="index.php?step=manager2" method="post" enctype="multipart/form-data" onsubmit="return validate_form( );">
            <select name="id_manager">
                <?php   
                $obj = new Cards();
                $stmt = $obj->list_manager();
                while ($result = $stmt->fetch_assoc()) {
                    ?>
                    <option value="<?php print $result['id'] ?>"><?php print $result['name'].' '.$result['surname']; ?></option>
                    <?php               
                }
                unset($obj);
                ?>
            </select><br>
            <select name="id_set">
                <?php   
                $obj = new Cards();
                $stmt = $obj->list_set();
                while ($result = $stmt->fetch_assoc()) {
                    ?>
                    <option value="<?php print $result['id'] ?>"><?php print $result['set_card']; ?></option>
                    <?php               
                }
                unset($obj);
                ?>
            </select><br>
            <input type="submit" value="Присвоить менеджера"><br>
        </form> 
    <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php   
} elseif ($_GET['step'] == "activation") {
?>
    <h2><b> Форма для активации карты </b></h2>
        <form name="activation_form" action="index.php?step=activation2" method="post" enctype="multipart/form-data" onsubmit="return validate_form( );">
            Имя <input type="text" name="name"><br>
            Номер карты <input type="text" name="number"><br>
            email <input type="text" name="email"><br><br>
            <input type="submit" value="Активация"><br>
        </form> 
    <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>    
<?php   
} elseif ($_GET['step'] == "upload2") {
    $obj = new Cards();
    print $obj->upload($_FILES, $_POST['set_card']);
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php
} elseif ($_GET['step'] == "manager2") {
    $obj = new Cards();
    print $obj->set_manager($_POST['id_set'], $_POST['id_manager']);
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php
} elseif ($_GET['step'] == "activation2") {
    $obj = new Cards();
    print $obj->activation($_POST['name'], $_POST['email'], $_POST['number']);
    unset($obj);
?>  <br><br>  <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php
} elseif ($_GET['step'] == "statistics") {
   $obj = new Cards();
   print $obj->statistics();
   unset($obj);
?> <br><br> <a href='http://46.188.37.109/'>Вернуться на главную страницу</a>
<?php
}
?>