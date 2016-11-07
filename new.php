<html>
<head>
	<script language="Javascript" type="text/javascript" src="http://yapro.ru/javascript/jquery.js"></script>
        <link type="text/css" href="latest.css" rel="Stylesheet" />
        <script type="text/javascript" src="ui.datepicker.js"></script>
</head>
<body>

<input name="min" value="04.05.2010" class="datepickerTimeField">
<input name="max" value="19.05.2010" class="datepickerTimeField">
<input type="submit" value="Показать">



<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});
</script>
</body>
</html>