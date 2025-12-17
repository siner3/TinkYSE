<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Tink</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
        }

        .container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 2rem;
            font-size: 0.9rem;
        }

        .status {
            color: #28a745;
            font-weight: bold;
        }

        .logout-btn {
            margin-top: 1.5rem;
            background: #ff4757;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        .logout-btn:hover { background: #ff6b81; transform: scale(1.05); }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎉 Welcome to Tink!</h1>
        <p>Your website is successfully deployed and running.</p>
        <p>This is your <strong>index.php</strong> file.</p>

        <div class="info">
            <p class="status">✅ PHP is working properly</p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        </div>
<button class="logout-btn" onclick="openLogoutModal()">Sign Out</button>
    </div>

    <div id="logout-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); background: rgba(0,0,0,0.3); z-index: 10000;
        justify-content: center; align-items: center;">

        <div style="background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); text-align: center; width: 90%; max-width: 380px;">
            <h2 style="color: #1a1a1a; margin-bottom: 15px; font-size: 1.5rem;">Sign out of Tink?</h2>
            <p style="color: #777; margin-bottom: 30px; font-size: 0.95rem;">Are you sure you want to end your current session?</p>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button onclick="executeLogout()" style="background: #1a1a1a; color: white; border: none; padding: 14px; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 1rem;">
                    Yes, Sign Out
                </button>

                <button onclick="closeLogoutModal()" style="background: transparent; color: #777; border: none; padding: 10px; cursor: pointer; font-size: 0.9rem; text-decoration: underline;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            const overlay = document.getElementById('logout-overlay');
            overlay.style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logout-overlay').style.display = 'none';
        }

        function executeLogout() {

            const toast = document.createElement('div');
            toast.innerHTML = "Successfully signed out ✨";
            toast.style = `
                position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                background: #1a1a1a; color: white; padding: 12px 30px;
                border-radius: 50px; z-index: 10001; font-weight: 500;
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
                animation: fadeInDown 0.5s ease;
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                window.location.href = 'index.php';
            }, 800);
        }
    </script>
</body>
</html>