const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

// Ensure dist directory exists
if (!fs.existsSync('dist')) {
    fs.mkdirSync('dist');
}

// Run Tailwind CSS build
exec('npx tailwindcss -i ./src/input.css -o ./dist/output.css --minify', (error, stdout, stderr) => {
    if (error) {
        console.error(`Error: ${error.message}`);
        return;
    }
    if (stderr) {
        console.error(`Stderr: ${stderr}`);
        return;
    }
    console.log(`Tailwind CSS compiled successfully: ${stdout}`);
}); 