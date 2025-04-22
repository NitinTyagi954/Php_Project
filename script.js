function showMessage(message, type = 'error') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.padding = '15px 25px';
    messageDiv.style.borderRadius = '8px';
    messageDiv.style.color = 'white';
    messageDiv.style.fontWeight = '500';
    messageDiv.style.zIndex = '1000';
    messageDiv.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    if (type === 'error') {
        messageDiv.style.backgroundColor = '#ef4444';
    } else {
        messageDiv.style.backgroundColor = '#10b981';
    }
    document.body.appendChild(messageDiv);
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) {
        console.error('Login form not found');
        showMessage('Login form not found. Please contact support.');
        return;
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            email: formData.get('email'),
            password: formData.get('password'),
            remember: formData.get('remember') === 'on'
        };

        try {
            console.log('Submitting login data:', data);
            const response = await fetch('includes/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                console.error('Network error:', response.status, response.statusText);
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Invalid content type:', contentType);
                throw new TypeError("Expected JSON response");
            }

            const result = await response.json();
            console.log('Login response:', result);

            if (result.success) {
                sessionStorage.setItem('user', JSON.stringify(result.user));
                const redirectUrl = result.redirect || 'dashboard.html';
                console.log('Redirecting to:', redirectUrl);
                
                if (result.user && result.user.role === 'admin') {
                    const adminDebug = document.createElement('div');
                    adminDebug.style.position = 'fixed';
                    adminDebug.style.top = '50%';
                    adminDebug.style.left = '50%';
                    adminDebug.style.transform = 'translate(-50%, -50%)';
                    adminDebug.style.backgroundColor = 'white';
                    adminDebug.style.padding = '20px';
                    adminDebug.style.borderRadius = '10px';
                    adminDebug.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                    adminDebug.style.zIndex = '9999';
                    adminDebug.style.maxWidth = '500px';
                    
                    adminDebug.innerHTML = `
                        <h3 style="margin-top:0">Admin Redirect Debug</h3>
                        <p>You are being redirected to: <code>${redirectUrl}</code></p>
                        <p>If that URL doesn't work, try these alternatives:</p>
                        <ul style="margin-bottom:15px">
                            <li><a href="admin/admin-dashboard.html" style="color:blue">admin/admin-dashboard.html</a></li>
                            <li><a href="admin/index.html" style="color:blue">admin/index.html</a></li>
                            <li><a href="admin/test.html" style="color:blue">admin/test.html</a></li>
                        </ul>
                        <button id="continue-redirect" style="background:#4CAF50;color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;margin-right:10px">Continue to ${redirectUrl}</button>
                        <button id="close-debug" style="background:#f44336;color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer">Close</button>
                    `;
                    
                    document.body.appendChild(adminDebug);
                    
                    document.getElementById('continue-redirect').addEventListener('click', () => {
                        adminDebug.remove();
                        window.location.href = redirectUrl;
                    });
                    
                    document.getElementById('close-debug').addEventListener('click', () => {
                        adminDebug.remove();
                    });
                    
                    return;
                }
                
                window.location.href = redirectUrl;
            } else {
                showMessage(result.message || 'Login failed. Please check your credentials.');
            }
        } catch (error) {
            console.error('Login error:', error.message, error.stack);
            showMessage('An error occurred during login. Please try again.');
        }
    });
});