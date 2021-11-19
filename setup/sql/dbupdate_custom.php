<#1>
<?php
$ilDB->manipulateF("UPDATE object_data SET offline = %s WHERE type = %s", ['integer', 'text'], [0, 'copa']);
?>
<#2>
<?php
$ilDB->manipulateF("UPDATE object_data SET offline = %s WHERE type = %s", ['integer', 'text'], [0, 'frm']);
?>
