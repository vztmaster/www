<head>
        <script language="Javascript" type="text/javascript" src="http://yapro.ru/javascript/jquery.js"></script>
        <script type="text/javascript" src="ui.datepicker.js"></script>
        <link type="text/css" href="latest.css" rel="Stylesheet" />
    </head>

<?php
class Cards {
    
    //Настройка БД
    private $host = "localhost"; // адрес сервера 
    private $database = "card_vip"; // имя базы данных
    private $user = "vztmaster"; // имя пользователя
    private $password = "cp2qwe82"; // пароль
    
    //Настройка таблиц БД
    private $no_activation = "no_activation"; //Название таблицы неактивированных карт
    private $activation = "activation"; //Название таблицы активированных карт
    private $activation_old = "activation_old";
    private $cards_set = "cards_set"; //Название таблицы наборов карт
    
    //Настройка email Администратора
    private $mail_admin = "admin@p396071.for-test-only.ru";
    private $headers  = "Content-type: text/html; charset=utf-8 \r\n"
            ."From: Администратор <webmaster@example.com>";
    
    //Приватные свойства
    private $connection; //Для выполнения запросов к БД
    private $nominal; //Заносится номинал карты
    private $namebase; //Заносится номер набора
    private $filename; //Заносится имя файла
    
    private function mysql_connect() {
        if ((mysqli_connect($this->host, $this->user, $this->password, $this->database))) {
            $this->connection = mysqli_connect($this->host, $this->user, $this->password, $this->database);
            $this->connection->set_charset("utf8");
            return TRUE;
        } else {
            return FALSE;
        }   
    }
    
    private function checkRecord($table, $row, $namebase) {
        $query = "SELECT $row FROM $table WHERE $row = '$namebase'";
        $stmt = $this->connection->query($query);
        
        if ($stmt->num_rows != 0) {
            return FALSE;
        }
        return TRUE;
    }
    
    private function nameExt($files) {
        $info = new SplFileInfo($files["filename"]["name"]);
        $extension =  '.'.$info->getExtension();
        $namebase = $info->getBasename("$extension");
        
        return array ($info, $namebase);
    }
    
    private function index2mysql($table, $row, $res) {
        if ($res == 'DROP'){
            $query = "ALTER TABLE $table $res INDEX $row";
            $this->connection->query($query);
            return TRUE;
        }
        
        if ($res == 'ADD'){
            $query = "ALTER TABLE $table $res INDEX ($row)";
            $this->connection->query($query);
            return TRUE;
        }
        
        return FALSE;
    }
    
    public function upload($files, $set_card, $nominal) {
        
        if(!(is_uploaded_file($files["filename"]["tmp_name"]))) {
            return "Ошибка! Файл не загружен на сервер.";
        }
        
        list($info, $namebase) = $this->nameExt($files);
        
        if ($set_card != $namebase) {
            return "Ошибка. Проверьте номер партии и номер загруженного файла.";
        }
       
        //проверяем какое расширение у файла, и если csv, то запускаем функцию
        if ($info->getExtension() == "csv") {
            move_uploaded_file($files["filename"]["tmp_name"], $files["filename"]["name"]);
            $this->namebase = $namebase;
            $this->filename = $info;
            $this->nominal = $nominal;
            return $this->csv2mysql();
        } else {
            return "Ошибка! Неизвестный формат файла.";
        }
    }
    
    public function csv2mysql() {
        if(!($this->mysql_connect())) {
            unlink("$this->filename");
            return "Нет подключения к БД.";
        }
        
        //Делаем запрос, существует ли уже такой набор и активен ли он
        $query = "SELECT set_card FROM $this->cards_set WHERE set_card = '$this->namebase'";
        $stmt = $this->connection->query($query);
        
        if ($stmt->num_rows != 0) {
            unlink("$this->filename");
            return "Ошибка! Данный набор уже был загружен.";
        }
        
        //Открываем файл, производим его чтение и внесение данных
        if (($handle = fopen("$this->filename", "r")) !== FALSE) {
            $activ_number = 0; //Количество невнесенных номеров
            $number2mysql = 0; //Количество внесенных номеров
            $today = date("Y-m-d-H:i:s"); 
            //
            while (($data = fgetcsv($handle, 100, ",")) !== FALSE) {
                $num = count($data);
                for ($c=0; $c < $num; $c++) {
                    
                    $xr = 0; //переменная для проверки номера
                    $query = "SELECT number FROM $this->no_activation WHERE number = '$data[$c]' LIMIT 10";
                    $result = $this->connection->query($query);
                    if (($result->num_rows) != 0) {
                        $xr = 1;                      
                    }
                    if ($xr == 0){
                        $query = "SELECT number FROM $this->activation WHERE number = '$data[$c]' LIMIT 10";
                        $result = $this->connection->query($query);
                        if (($result->num_rows) != 0){
                            $xr = 1; 
                        }
                    }
                    
                    if ($xr == 0) {
                        $query = "INSERT INTO $this->no_activation (number, set_card, nominal) VALUES ('$data[$c]', '$this->namebase', '$this->nominal')";
                        $this->connection->query($query);
                        $number2mysql++;
                    } else {
                            $fp = fopen("$this->namebase-$today.txt", 'a');
                            fwrite($fp, $data[$c]."\n");
                            fclose($fp);
                            $activ_number++;
                    }                   
                }
            }
 
            $query = "INSERT INTO $this->cards_set (set_card) VALUES ('$this->namebase')";
            $this->connection->query($query);   
            
            //производим индексацию
            $this->index2mysql($this->no_activation, 'number', 'DROP');
            $this->index2mysql($this->no_activation, 'number', 'ADD');
            $this->index2mysql($this->no_activation, 'set_card', 'DROP');
            $this->index2mysql($this->no_activation, 'set_card', 'ADD');
            $this->index2mysql($this->no_activation, 'nominal', 'DROP');
            $this->index2mysql($this->no_activation, 'nominal', 'ADD');

            fclose($handle); //закрываем файл
            unlink("$this->filename"); //удаляем файл
            mysqli_close($this->connection); //закрываем соединение
            return    "Данные внесены в БД.<br>"
                    . "Внесенных номер: $number2mysql<br>"
                    . "Невнесенных номеров: $activ_number<br>";
                    //. "<a href='http://46.188.37.109/$this->namebase-$today.txt'>Сохранить невнесенные номера</a>";    
        } else {
            return "Ошибка! Невозможно открыть файл.";
        }
    } 

    public function activation($name, $email, $number) {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
        if (($this->checkRecord($this->no_activation, 'number', $number))) {
            if (!($this->checkRecord($this->activation, 'number', $number))) {
                return "Ошибка! Карта была активирована.";
            }
            
            if (!($this->checkRecord($this->activation_old, 'number', $number))) {
                return "Ошибка! Карта была активирована и погашена.";
            }
            
            return "Ошибка! Неверный номер карты.";
        }
        
        $query = "SELECT set_card FROM $this->no_activation WHERE number = '$number'";
        $result = $this->connection->query($query);
        $row = $result->fetch_row();
        $query = "SELECT nominal FROM $this->no_activation WHERE number = '$number'";
        $result = $this->connection->query($query);
        $row2 = $result->fetch_row();
        $query = "INSERT INTO $this->activation (number, set_card, name, email, nominal) VALUES ('$number', '$row[0]', '$name', '$email', '$row2[0]')";       
        $this->connection->query($query);
        $query = "DELETE FROM $this->no_activation WHERE number = '$number'";
        $this->connection->query($query);
        
        //производим индексацию
        $this->index2mysql($this->no_activation, 'number', 'DROP');
        $this->index2mysql($this->no_activation, 'number', 'ADD');
        $this->index2mysql($this->no_activation, 'set_card', 'DROP');
        $this->index2mysql($this->no_activation, 'set_card', 'ADD');
        $this->index2mysql($this->no_activation, 'nominal', 'DROP');
        $this->index2mysql($this->no_activation, 'nominal', 'ADD');
        $this->index2mysql($this->activation, 'number', 'DROP');
        $this->index2mysql($this->activation, 'number', 'ADD');
        $this->index2mysql($this->activation, 'set_card', 'DROP');
        $this->index2mysql($this->activation, 'set_card', 'ADD');
        $this->index2mysql($this->activation, 'nominal', 'DROP');
        $this->index2mysql($this->activation, 'nominal', 'ADD');
        mysqli_close($this->connection);
        
        //отправем email
        $this->email($email, $name, $number);
        
        return "Поздравляем! Карта активирована.";       
    }
    
    private function email($to, $name, $number) {
        $subject = 'Активация карты';
        $message = 
                "<html> 
                    <head> 
                        <title>Активация карты</title> 
                    </head> 
                <body> 
                    <p> 
                        Уважаемый, $name.<br>
                        Ваша карта № $number успешно активирована. <br><br>
                        Благодарим за использование нашего продукта.
                    </p>
                </body> 
                </html>";
        $msg_admin = 
                "<html> 
                    <head> 
                        <title>Активация карты</title> 
                    </head> 
                <body> 
                    <p> 
                        Карта № $number активирована. <br>
                        Имя: $name <br>
                        email: $to </p>
                </body> 
            </html>";
     
        mail($to, $subject, $message, $this->headers);
        mail($this->mail_admin, $subject, $msg_admin, $this->headers);
        
        return true;
    }
    
    public function statistics_vip () {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        $start_date = "10.10.2016";
        $today = date("d.m.Y");
        if ($_POST['min'] != ""){
            $start_date = $_POST['min'];
        }
        if ($_POST['max'] != ""){
            $today = $_POST['max'];
        }

        $nominal[0] = '5000';
        $nominal[1] = '1000';
        $nominal[2] = '500';
        $nominal[3] = '100';
            
        for ($i=0; $i < count($nominal); $i++) {
            $query = "SELECT id FROM $this->activation WHERE nominal = $nominal[$i]";                   
            $stmt = $this->connection->query($query);
            $num_activation[$i] = $stmt->num_rows;
            $query = "SELECT id FROM $this->no_activation WHERE nominal = $nominal[$i]";                   
            $stmt = $this->connection->query($query);
            $num_no_activation[$i] = $stmt->num_rows;
            $query = "SELECT id FROM $this->activation_old WHERE nominal = $nominal[$i]";                   
            $stmt = $this->connection->query($query);
            $num_activation_old[$i] = $stmt->num_rows;
        }
        
        $sum_no_activation = 0;
        $sum_activation = 0;
        $sum_activation_old = 0;
        for ($i=0; $i < count($nominal); $i++){
            $sum_no_activation = $sum_no_activation + $num_no_activation[$i];
            $sum_activation = $sum_activation + $num_activation[$i];
            $sum_activation_old = $sum_activation_old + $num_activation_old[$i];
        }
   
        return "Неактивированных карт. Всего: $sum_no_activation<br>"
            . "$nominal[0] : $num_no_activation[0]<br>"
            . "$nominal[1] : $num_no_activation[1]<br>"
            . "$nominal[2] : $num_no_activation[2]<br>"
            . "$nominal[3] : $num_no_activation[3]<br><br>"
            . "Активированные карты (непогашенные). Всего: $sum_activation <br>"
            . "$nominal[0] : $num_activation[0]<br>"
            . "$nominal[1] : $num_activation[1]<br>"
            . "$nominal[2] : $num_activation[2]<br>"
            . "$nominal[3] : $num_activation[3]<br><br>"
            . "Активированные карты (погашенные). Всего: $sum_activation_old <br>"
            . "$nominal[0] : $num_activation_old[0]<br>"
            . "$nominal[1] : $num_activation_old[1]<br>"
            . "$nominal[2] : $num_activation_old[2]<br>"
            . "$nominal[3] : $num_activation_old[3]<br><br>"
            . "<form method='POST'>"
            . "<input name='min' value='$start_date' class='datepickerTimeField'/>"
            . "<input name='max' value='$today' class='datepickerTimeField'/>"
            . "<input type='submit' value='Загрузить результаты'/><br>"
            . "</form><script>"
            . "$('.datepickerTimeField').datepicker({"
		."changeMonth: true,"
		."changeYear: true,"
		."dateFormat: 'dd.mm.yy',"
		."firstDay: 1,"
                ."changeFirstDay: false,"
		."navigationAsDateFormat: false,"
		."duration: 1"
                ."});"
                ."</script>";
    }
    
    public function check_activated_step_1 ($number) {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        
            //проверяем существует ли карта в списке
            if (($this->checkRecord($this->activation, 'number', $number))) {
                    
                if (!($this->checkRecord($this->no_activation, 'number', $number))) {
                    return "Ошибка! Карта не активирована.";
                }
                      
                if (!($this->checkRecord($this->activation_old, 'number', $number))) {
                    //return "Ошибка! Карта была активирована и погашена ранее.";
                    $query = "SELECT name, nominal, email, date FROM $this->activation_old WHERE number = '$number'";
                    $result = $this->connection->query($query);
                    $row = $result->fetch_row();
                    $name = $row[0];
                    $nominal = $row[1];
                    $email = $row[2];
                    $date = $row[3];
                    mysqli_close($this->connection);
                    
                    return  "Данная карта ранее была активирована. <br>"
                    . "Номер карты: $number <br>"
                    . "Номинал карты: $nominal <br>"
                    . "Имя владельца: $name <br>"
                    . "Email владельца: $email <br>"
                    . "Дата активации: $date <br>";
                }
        
                return "Ошибка! Карта не существует.";
            }  
            
            $query = "SELECT name, nominal, set_card, email FROM $this->activation WHERE number = '$number'";
            $result = $this->connection->query($query);
            $row = $result->fetch_row();
            
            $name = $row[0];
            $nominal = $row[1];
            $set_card = $row[2];
            $email = $row[3];
            mysqli_close($this->connection);
            return  "Данная карта существует. <br>"
                    . "Номер карты: $number <br>"
                    . "Номинал карты: $nominal <br>"
                    . "Имя владельца: $name <br>"
                    . "<form name='activation_form' action='vip.php?step=operator_vip3' method='post' enctype='multipart/form-data'>"
                    . "<input type='hidden' name='number' value='$number'><br>"
                    . "<input type='hidden' name='name' value='$name'><br>"
                    . "<input type='hidden' name='nominal' value='$nominal'><br>"
                    . "<input type='hidden' name='set_card' value='$set_card'><br>"
                    . "<input type='hidden' name='email' value='$email'><br>"
                    . "<input type='submit' value='Погасить'><br>"
                    . "</form><br>";
    }
    
    public function check_activated_step_2 ($number, $name, $email, $nominal, $set_card) {
        
        if(!($this->mysql_connect())) {
            return "Нет подключения к БД.";
        }
        $today = date("d.m.Y");    
        $query = "INSERT INTO $this->activation_old (number, name, email, nominal, set_card, date) VALUES ('$number', '$name', '$email', '$nominal', '$set_card', '$today')";       
        $this->connection->query($query);
        $query = "DELETE FROM $this->activation WHERE number = '$number'";
        $this->connection->query($query);
            
        $this->index2mysql($this->activation, 'number', 'DROP');
        $this->index2mysql($this->activation, 'number', 'ADD');
        $this->index2mysql($this->activation, 'set_card', 'DROP');
        $this->index2mysql($this->activation, 'set_card', 'ADD');
        $this->index2mysql($this->activation, 'nominal', 'DROP');
        $this->index2mysql($this->activation, 'nominal', 'ADD');
        $this->index2mysql($this->activation_old, 'number', 'DROP');
        $this->index2mysql($this->activation_old, 'number', 'ADD');
            
        mysqli_close($this->connection);
            
        return "Карта погашена.";
        
    }
}
?>

