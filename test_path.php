<?php
echo "<h1>Path Test</h1>";
echo "<p>Current script path: " . __FILE__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Directory listing:</p>";
echo "<pre>";
print_r(scandir('.'));
echo "</pre>";

// Check if admin directory exists and is accessible
echo "<h2>Admin Directory Check:</h2>";
if (is_dir('admin')) {
    echo "<p style='color:green'>Admin directory exists at the current level</p>";
    echo "<p>Admin directory contents:</p>";
    echo "<pre>";
    print_r(scandir('admin'));
    echo "</pre>";
} else {
    echo "<p style='color:red'>Admin directory NOT found at the current level</p>";
}

// Check includes directory
echo "<h2>Includes Directory Check:</h2>";
if (is_dir('includes')) {
    echo "<p style='color:green'>Includes directory exists at the current level</p>";
} else {
    echo "<p style='color:red'>Includes directory NOT found at the current level</p>";
}
?> 