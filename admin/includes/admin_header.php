<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - GameDb</title>
    
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        /* --- 1. MODAL STYLES --- */
        .modal { display: none; position: fixed; z-index: 20000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; }

        /* --- 2. DESKTOP LAYOUT (Default) --- */
        .admin-container {
            display: flex; 
            min-height: 100vh;
            width: 100%;
        }

        .admin-sidebar, .sidebar {
            width: 260px;
            min-width: 260px;
            background-color: #2c3e50;
            color: white;
            transition: all 0.3s ease;
        }

        .admin-content {
            flex: 1; /* Fills screen on PC */
            padding: 30px;
            background-color: #f4f7f6;
            min-width: 0; 
        }

        .mobile-nav-bar { display: none; }
        .sidebar-overlay { display: none; }

        /* --- 3. MOBILE & TABLET OVERRIDES --- */
        @media screen and (max-width: 1024px) {
            .mobile-nav-bar {
                display: flex !important;
                background: #2c3e50;
                padding: 0 20px;
                color: white;
                position: fixed;
                top: 0; left: 0; right: 0;
                height: 60px;
                z-index: 15000;
                justify-content: space-between;
                align-items: center;
                box-sizing: border-box;
            }

            .admin-container {
                display: block !important;
                margin-top: 60px;
            }

            .admin-sidebar, .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                width: 280px !important;
                z-index: 20001 !important; /* On top of everything */
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                margin: 0 !important;
            }

            .admin-sidebar.active, .sidebar.active {
                transform: translateX(0) !important;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 20000;
            }
            .sidebar-overlay.active { display: block !important; }

            .admin-content {
                margin-left: 0 !important;
                padding: 15px !important;
                width: 100% !important;
                box-sizing: border-box;
            }

            /* === THE TABLE SAVIOR === */
            .table-container {
                width: 100%;
                overflow-x: auto !important; /* Enable horizontal swipe */
                -webkit-overflow-scrolling: touch;
                margin-top: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            }

            /* Prevent columns from squishing */
            .table-container table {
                min-width: 800px !important; 
                width: 100%;
            }

            /* Fix Header Stacking (Manage Games + Add Button) */
            .admin-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 15px !important;
            }

            /* Fix Search Forms */
            div[style*="background: white; padding: 15px"] {
                flex-direction: column !important;
            }

            form[style*="display: flex"] {
                flex-direction: column !important;
                width: 100%;
            }

            form input[type="text"], 
            form select, 
            form button {
                width: 100% !important;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body class="admin-body">

    <div class="mobile-nav-bar">
        <span style="font-weight: bold; font-size: 1.1rem;">GameDb Admin</span>
        <button type="button" onclick="toggleSidebar()" 
                style="background:#3498db; color:white; border:none; padding:8px 15px; border-radius:4px; font-weight:bold; cursor:pointer;">
            ☰ Menu
        </button>
    </div>

    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar') || document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');
        
        if (sidebar) {
            sidebar.classList.toggle('active');
            if(overlay) overlay.classList.toggle('active');
            
            // Lock/Unlock background scrolling
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
        }
    }
    </script>

    <div class="admin-container">