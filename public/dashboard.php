<?php
session_start();
require __DIR__.'/../vendor/autoload.php';
require "../env.php";

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}

$typesenseAdmin = new \DevProblemsSolutions\PHPTypesenseAdmin\TypesenseAdmin($typesenseProtocol, $typesenseHost, $typesensePort, $typesenseApiKey);

// Simulate a basic router using a 'page' GET parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHPTypesenseAdmin - Dashboard</title>
    <style>
        body { font-size: 1.2rem; font-family: "Helvetica Neue", Arial, sans-serif; }

        input { font-size: 1.3rem; }
        input[type='submit'] { background: green; color: #fff; border: 1px solid darkgreen; border-radius: 5px; }
        .btn { background: #333; border-radius: 3px; display: inline-block; padding: 5px 10px; color: #fff; text-decoration: none; }
        textarea { min-height: 300px; min-width: 50%; font-size: 1.3rem; }
        table { width: 100%; }

        #nav {
            float: left;
            width: 20%;
        }
        #nav ul {
            list-style-type: none;
            padding: 0;
        }
        #nav ul li li {
            display: block;
            margin-left: 20px;
        }

        #content { margin-left: 5%; float: left; width: 75%; }

        .ok { background: green; padding: 10px; display: inline-block; border-radius: 15px; color: #fff; }
        .error { background: red; padding: 10px; display: inline-block; border-radius: 15px; color: #fff; }
        .well { max-width: 100%; overflow: auto; background: #eee; border-radius: 5px; padding: 15px 20px; font-size: 1.1rem; }
    </style>
</head>
<body>
    <h1>PHPTypesenseAdmin</h1>
    <div id="nav">
        <ul>
            <li><a href="dashboard.php?page=home">Health Status</a></li>
            <li><a href="dashboard.php?page=list_collections">Collections</a>
                <ul>
                    <li><a href="dashboard.php?page=list_collections">List Collections</a></li>
                    <li><a href="dashboard.php?page=create_collection">Create Collection</a></li>
                </ul>
            </li>
            <li><a href="dashboard.php?page=list_keys">API Keys</a>
                <ul>
                    <li><a href="dashboard.php?page=list_keys">List</a></li>
                    <li><a href="dashboard.php?page=create_key">Create</a></li>
                </ul>
            </li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div id="content">
        <?php
        // Basic content router
        switch ($page) {
            case 'home':
                $status = $typesenseAdmin->getHealth();
                echo "<h2>Health Status</h2><p>" . ($status ? '<div class="ok">OK, connected with /health API endpoint</div>' : '<div class="error">ERROR, could not connect to /health API endpoint.</div>') . "</p><p>Output: " . $status . "</p>";
                echo "<h2>Stats</h2>";
                $typesenseAdmin->displayTypesenseStatsTables();
                break;
            case 'list_collections':
                $typesenseAdmin->listCollections();
                break;
            case 'edit_collection':
                $typesenseAdmin->editCollection();
                break;
            case 'update_collection':
                $typesenseAdmin->updateCollection();
                break;
            case 'create_collection':
                $typesenseAdmin->createCollection();
                break;
            case 'store_collection':
                $typesenseAdmin->storeCollection();
                break;
            case 'delete_collection':
                $typesenseAdmin->deleteCollection();
                break;
            case 'import_collection':
                $typesenseAdmin->importCollection();
                break;
            case 'export_collection':
                $typesenseAdmin->exportCollection();
                break;
            case 'search_collection':
                echo "<h2>Search collections</h2><p>Collection management area.</p>";
                $typesenseAdmin->searchCollection();
                // Here you would include or require the specific files based on the action
                break;
            case 'list_keys':
                $typesenseAdmin->listKeys();
                break;
            case 'create_key':
                $typesenseAdmin->createKey();
                break;
            case 'store_key':
                $typesenseAdmin->storeKey();
                break;
            case 'delete_key':
                $typesenseAdmin->deleteKey();
                break;
            default:
                echo "<h2>Welcome to the Dashboard</h2>";
                break;
        }
        ?>
    </div>
</body>
</html>