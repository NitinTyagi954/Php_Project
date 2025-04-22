<?php
header('Content-Type: text/html');
echo "<h1>Admin Directory Diagnostics</h1>";
echo "<p style='font-weight:bold; color:green;'>✅ PHP is working correctly in admin directory!</p>";
echo "<p>Current script path: " . __FILE__ . "</p>";
echo "<p>Current URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

// Redirect testing
echo "<h2>Redirect Path Information:</h2>";
$requestedPath = $_SERVER['REQUEST_URI'];
$basePath = str_replace('admin/test.php', '', $requestedPath);
echo "<p>Base path: " . $basePath . "</p>";
echo "<p>Expected admin dashboard path: " . $basePath . "admin/admin-dashboard.html</p>";

// Compare with directly accessing the file via PHP
echo "<h2>File Access Check:</h2>";
$dashboardFilePath = __DIR__ . '/admin-dashboard.html';
if (file_exists($dashboardFilePath)) {
    echo "<p style='color:green;'>✅ admin-dashboard.html exists at: " . $dashboardFilePath . "</p>";
    
    // Output file content size to verify it's the correct file
    $fileSize = filesize($dashboardFilePath);
    echo "<p>File size: " . $fileSize . " bytes</p>";
} else {
    echo "<p style='color:red;'>❌ admin-dashboard.html does NOT exist at: " . $dashboardFilePath . "</p>";
}

// Check if file exists with absolute path
$adminDashboardFullPath = $_SERVER['DOCUMENT_ROOT'] . $basePath . 'admin/admin-dashboard.html';
echo "<p>Full server path for admin dashboard: " . $adminDashboardFullPath . "</p>";
if (file_exists($adminDashboardFullPath)) {
    echo "<p style='color:green;'>✅ admin-dashboard.html exists using server path</p>";
} else {
    echo "<p style='color:red;'>❌ admin-dashboard.html does NOT exist using server path</p>";
}

// Check includes directory access
echo "<h2>Includes Directory Access:</h2>";
if (is_dir('../includes')) {
    echo "<p style='color:green;'>✅ ../includes directory exists</p>";
    
    // List files in the includes directory
    echo "<p>Files in includes directory:</p>";
    echo "<ul>";
    foreach (scandir('../includes') as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
    
    if (file_exists('../includes/auth.php')) {
        echo "<p style='color:green;'>✅ auth.php exists in includes directory</p>";
        
        // Show last modified time
        echo "<p>Last modified: " . date("Y-m-d H:i:s", filemtime('../includes/auth.php')) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ auth.php does NOT exist in includes directory</p>";
    }
} else {
    echo "<p style='color:red;'>❌ ../includes directory does NOT exist</p>";
}

// Check the admin folder
echo "<h2>Admin Directory Structure:</h2>";
echo "<p>Files in admin directory:</p>";
echo "<ul>";
foreach (scandir('.') as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . " - " . (is_file($file) ? "File" : "Directory") . "</li>";
    }
}
echo "</ul>";

// Provide direct links
echo "<h2>Direct Links:</h2>";
echo "<p><a href='admin-dashboard.html' style='color: blue;'>Click here to try accessing admin-dashboard.html directly</a></p>";
echo "<p><a href='test.html' style='color: blue;'>Click here to try accessing test.html directly</a></p>";
echo "<p><a href='index.html' style='color: blue;'>Click here to try accessing index.html directly</a></p>";
?> 