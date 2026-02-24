<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MITRA PAD API Service</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #074764;
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
            max-width: 400px;
            width: 90%;
        }
        .logo-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .brand-text {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #074764;
        }
        .brand-accent {
            color: #d9a742;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 0.5rem 0;
            color: #1e293b;
            font-weight: 600;
        }
        p {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0 0 2rem 0;
            line-height: 1.5;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f0fdf4;
            color: #166534;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #dcfce3;
        }
        .dot {
            width: 8px;
            height: 8px;
            background-color: #22c55e;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Display Request by User -->
        <!-- Note: We use a placeholder image URL or base64 if the logo isn't exposed in public/.
             Assuming production has the logo at a specific URL or we can use the main app's URL for the logo -->
        <div class="logo-container">
            <!-- Using the branding colors since we might not have the static image served by the API directly, 
                 or using a generic icon if the image fails to load -->
            <img src="https://admin.sipanda.online/mitra-logo.png" alt="MITRA Logo" class="logo" onerror="this.style.display='none'">
            <div class="brand-text">MITRA <span class="brand-accent">PAD</span></div>
        </div>
        
        <h1>API Service</h1>
        <p>This is the core API service for the MITRA PAD application ecosystem. Direct web access is restricted.</p>
        
        <div class="status">
            <span class="dot"></span>
            System Online & Secure
        </div>
    </div>
</body>
</html>
