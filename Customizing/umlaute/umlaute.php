<?php
echo "<pre>";
setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8");
echo setlocale( LC_CTYPE , 0) . "\n";
chdir ('/nfs/iliasdata/studon-exam/webdata/logs');
echo exec("/usr/bin/unzip -o umlaute.zip");
echo "\n";