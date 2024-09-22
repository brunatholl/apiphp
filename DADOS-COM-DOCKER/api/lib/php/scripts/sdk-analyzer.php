<?php
echo '<phpSdkInfo>';
echo '<os>' . htmlspecialchars(php_uname('s')) . '</os>';
echo '<version>' . htmlspecialchars(phpversion()) . '</version>';
echo '<include_path>' . htmlspecialchars(ini_get('include_path')) . '</include_path>';
echo '</phpSdkInfo>';
?>