<?php
echo "Mevcut PDO sürücüleri:<br>";
print_r(PDO::getAvailableDrivers());

echo "<br><br>PHP Sürümü: " . PHP_VERSION;
echo "<br>PHP.ini konumu: " . php_ini_loaded_file();

// SQLite DLL dosyalarını kontrol et
$sqlite3_dll = file_exists("C:/xampp/php/ext/php_sqlite3.dll");
$pdo_sqlite_dll = file_exists("C:/xampp/php/ext/php_pdo_sqlite.dll");

echo "<br><br>SQLite DLL Dosyaları:";
echo "<br>php_sqlite3.dll: " . ($sqlite3_dll ? "Var" : "Yok");
echo "<br>php_pdo_sqlite.dll: " . ($pdo_sqlite_dll ? "Var" : "Yok");

// Yüklü PHP modüllerini listele
echo "<br><br>Yüklü PHP Modülleri:<br>";
print_r(get_loaded_extensions());
?> 