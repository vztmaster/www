
<?php

$host = "localhost"; // адрес сервера 
$database = "card_vip"; // имя базы данных
$user = "root"; // имя пользователя
$password = "cp2qwe82"; // пароль

$connection = mysqli_connect($host, $user, $password, $database);
$connection->set_charset("utf8");
$start_date = "10.10.2016";
$today = date("d.m.Y");

if ($_POST['min'] != ""){
    $start_date = $_POST['min'];
}
if ($_POST['max'] != ""){
    $today = $_POST['max'];
}

//SELECT * FROM name_table WHERE  DATE(date_column) = DATE(NOW());
$query = "SELECT * FROM activation_old WHERE DATE(date) = '2016-10-20'";                   
            $stmt = $connection->query($query);
            print $stmt->num_rows;
            print "<br><br>";

?>
<form method="POST">
    <input name="min" value="<?php echo $start_date ?>" class="datepickerTimeField"/>
    <input name="max" value="<?php echo $today ?>" class="datepickerTimeField"/>
    <input type="submit" value="Загрузить результаты"/><br>
</form>
<script>
        $(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, 
                changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 1
        });
        </script>     
    </body>
</html>