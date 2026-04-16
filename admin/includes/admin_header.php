<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - GameDb</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 500px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-modal:hover, .close-modal:focus { color: black; text-decoration: none; cursor: pointer; }
        .modal-actions { text-align: right; margin-top: 20px; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-container">