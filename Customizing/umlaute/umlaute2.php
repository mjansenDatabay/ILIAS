<?php

echo "<pre>";
$env = getenv();
var_dump($env);
setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8");
echo setlocale( LC_CTYPE , 0) . "\n";
chdir ('/srv/umlaute');
echo exec("/usr/bin/unzip -o umlaute.zip");
echo "\n";