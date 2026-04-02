<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZTE OLT Provisioning System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-network-wired text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Smart OLT</h1>
            <p class="text-gray-600">ZTE OLT Provisioning System</p>
        </div>
        
        <form id="loginForm" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" id="username" required
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter username" value="admin">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" required
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter password" value="admin">
                </div>
                <p class="text-xs text-gray-500 mt-1">Default: admin / admin</p>
            </div>
            
            <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
            
            <button type="submit" id="loginBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200 flex items-center justify-center">
                <span id="btnText">Sign In</span>
                <i id="btnIcon" class="fas fa-arrow-right ml-2"></i>
                <i id="btnSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} Smart OLT System</p>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            const btnSpinner = document.getElementById('btnSpinner');
            const errorDiv = document.getElementById('errorMessage');
            
            btn.disabled = true;
            btnText.textContent = 'Signing in...';
            btnIcon.classList.add('hidden');
            btnSpinner.classList.remove('hidden');
            errorDiv.classList.add('hidden');
            
            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    localStorage.setItem('token', data.access_token);
                    window.location.href = '/dashboard';
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                errorDiv.textContent = error.message || 'An error occurred. Please try again.';
                errorDiv.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btnText.textContent = 'Sign In';
                btnIcon.classList.remove('hidden');
                btnSpinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
