<?php
$pageTitle = "About";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <a href="index.php" class="back-link">&larr; Back to Home</a>
    
    <h1 class="page-title">About the Adventurer's Guild</h1>
    <p class="page-subtitle">A CCS0043 Final Project — PHP & MySQL Web Application</p>
    
    <div class="card">
        <h2 class="card-title">Overview of the System</h2>
        <p>The <strong>Adventurer Guild Registration & Quest Board</strong> is a web-based management system built using PHP and MySQL. It serves as a digital hub where adventurers can register, log in, browse available quests, and manage their quest assignments. Guild Masters can post new quests, assign them to adventurers, and track their progress.</p>
    </div>
    
    <div class="card">
        <h2 class="card-title">Problem Being Addressed</h2>
        <p>In many fantasy role-playing environments, managing quests, tracking adventurer progress, and coordinating between Guild Masters and adventurers can be chaotic when done manually. This system provides a centralized, organized platform to streamline quest management and adventurer registration.</p>
    </div>
    
    <div class="card">
        <h2 class="card-title">Target Users</h2>
        <div class="grid-2">
            <div>
                <h3 style="color: var(--color-gold); margin-bottom: 10px;">Guild Masters (Admins)</h3>
                <p>Users with the <strong>Guild Master</strong> role who can post quests, manage the quest board, and view reports about quest completion rates and adventurer statistics.</p>
            </div>
            <div>
                <h3 style="color: var(--color-gold); margin-bottom: 10px;">Adventurers</h3>
                <p>Regular users who can register, browse available quests, update their profiles, and track their own quest progress.</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2 class="card-title">Main Features of the System</h2>
        <div class="grid-2">
            <div>
                <ul style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 10px;">&#128101; <strong>User Registration & Login</strong> — Secure authentication with sessions</li>
                    <li style="margin-bottom: 10px;">&#128221; <strong>Quest Creation</strong> — Guild Masters can post new quests with details</li>
                    <li style="margin-bottom: 10px;">&#128269; <strong>Quest Browsing</strong> — Filter quests by difficulty and status</li>
                </ul>
            </div>
            <div>
                <ul style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 10px;">&#9999; <strong>Quest Management</strong> — Edit and delete quests (CRUD)</li>
                    <li style="margin-bottom: 10px;">&#128100; <strong>Adventurer Directory</strong> — View all registered adventurers</li>
                    <li style="margin-bottom: 10px;">&#128200; <strong>Reports & Statistics</strong> — Quest and adventurer summaries</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2 class="card-title">Technologies Used</h2>
        <div class="grid-3">
            <div class="stat-card">
                <strong>PHP</strong>
                <p>Server-side scripting for form handling, sessions, and database operations</p>
            </div>
            <div class="stat-card">
                <strong>MySQL</strong>
                <p>Relational database for storing users, quests, and system data</p>
            </div>
            <div class="stat-card">
                <strong>HTML / CSS</strong>
                <p>Frontend structure and medieval-themed styling</p>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
