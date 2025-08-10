<?php
echo "AVANT_API_KEY: " . ($_ENV['AVANT_API_KEY'] ?? 'NOT SET') . "\n";
echo "Full length: " . strlen($_ENV['AVANT_API_KEY'] ?? '') . "\n";
?>
