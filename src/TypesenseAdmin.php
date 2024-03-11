<?php
namespace DevProblemsSolutions\PHPTypesenseAdmin;

class TypesenseAdmin {

    private $client;
    private $typesenseProtocol;
    private $typesenseHost;
    private $typesensePort;
    private $typesenseAPIKey;

    function __construct($typesenseProtocol, $typesenseHost, $typesensePort, $typesenseAPIKey)
    {
        $this->typesenseProtocol = $typesenseProtocol;
        $this->typesenseHost = $typesenseHost;
        $this->typesensePort = $typesensePort;
        $this->typesenseAPIKey = $typesenseAPIKey;

        $this->client = new \Typesense\Client(
          [
            'api_key'         => $typesenseAPIKey,
            'nodes'           => [
                [
                    'host'     => $typesenseHost,
                    'port'     => $typesensePort,
                    'protocol' => $typesenseProtocol,
                ],
            ],
            'connection_timeout_seconds' => 2,
          ]
        );
    }

    function getBaseUrl()
    {
        return $this->typesenseProtocol . '://' . $this->typesenseHost . ':' . $this->typesensePort;
    }

    function getHealth()
    {
        $health = json_decode(file_get_contents($this->getBaseUrl() . '/health'));
        return $health->ok ?? $health;
    }

    function fetchTypesenseStats() {
        $url = $this->getBaseUrl() . '/stats.json';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-TYPESENSE-API-KEY: " . $this->typesenseAPIKey
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            return "Curl Error: " . curl_error($curl);
        }

        curl_close($curl);
        return json_decode($response, true);
    }

    function displayTypesenseTable($data) {

        // Start the table
        echo '<table border="1" cellspacing="0" cellpadding="5">';

        // Table header
        echo '<tr><th>Field</th><th>Value</th></tr>';

        // Iterate over the array and generate table rows
        foreach ($data as $key => $value) {
            echo "<tr><td>{$key}</td><td>" . (is_string($value) ? $value : json_encode($value)) ."</td></tr>";
        }

        // Close the table
        echo '</table>';

    }

    function fetchTypesenseMetrics() {
        $url = $this->getBaseUrl() . '/metrics.json';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-TYPESENSE-API-KEY: " . $this->typesenseAPIKey
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            return "Curl Error: " . curl_error($curl);
        }

        curl_close($curl);
        return json_decode($response, true);
    }

    function listCollections()
    {
        $collections = $this->client->collections->retrieve();
        ?>

        <h2>Typesense Collections</h2>

        <?php if (empty($collections)) { ?>
        <p>No collections found</p>
        <?php } ?>

        <?php foreach ($collections as $collection): ?>
            <h3>Collection: <?php echo htmlspecialchars($collection['name']); ?></h3>
            <p>Number of Documents: <?php echo htmlspecialchars($collection['num_documents']); ?></p>
            <p>Default Sorting Field: <?php echo htmlspecialchars($collection['default_sorting_field']); ?></p>

            <p>
                <a class="btn" href="dashboard.php?page=search_collection&collection=<?= urlencode($collection['name']) ?>">🔎 Search through</a> /
                <a class="btn" href="dashboard.php?page=edit_collection&collection=<?= urlencode($collection['name']) ?>">⚙️ Update collection</a> /
                <a class="btn" href="dashboard.php?page=delete_collection&collection=<?= urlencode($collection['name']) ?>" onclick="return confirmLink()">🗑️ Delete this collection</a> /
                <a class="btn" href="dashboard.php?page=import_collection&collection=<?= urlencode($collection['name']) ?>">⬇️ Import collection</a> /
                <a class="btn" href="dashboard.php?page=export_collection&collection=<?= urlencode($collection['name']) ?>">⬆️ Export collection</a>
            </p>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Facet</th>
                    <th>Sort</th>
                </tr>
                <?php foreach ($collection['fields'] as $field): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($field['name']); ?></td>
                        <td><?php echo htmlspecialchars($field['type']); ?></td>
                        <td><?php echo $field['facet'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $field['sort'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>

        <br>

        <p><a href="dashboard.php?page=create_collection" class="btn">+ Create Collections</a></p>

        <script type="text/javascript">
            function confirmLink() {
                const userResponse = confirm("Are you sure you want to delete this collection?");

                return userResponse;
            }
        </script>

        <?php
    }

    function searchCollection() {

        if (empty($_GET['collection']))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_GET['collection']);
        $pageNr = abs((int)(!empty($_GET['pagenr']) ? $_GET['pagenr'] : 0));
        $q = $_GET['q'] ?? '';

        try {
            $searchParameters = [
                'q'         => $q,
                'query_by'  => 'name', 
                'per_page'   => 24,
                'page' => abs($pageNr),
            ];

            $result = $this->client->collections[$collection]->documents->search($searchParameters);
        } catch (Exception $e) {
            echo "Error: ",  $e->getMessage(), "\n";
            return false;
        }

        ?>

        <style type="text/css">
            .result-container {
                margin: 20px;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .result-header {
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .result-body {
                margin-bottom: 10px;
            }
            .result-footer {
                font-size: 0.8em;
                color: #666;
            }
        </style>

        <h2>Search Results</h2>

        <form method="get" action="dashboard.php">
            <input type="hidden" name="page" value="search_collection">
            Page nr: <input type="number" name="pagenr" value="<?= $pageNr ?>" placeholder="page nr" min="0"><br>
            Collection name: <input type="text" name="collection" value="<?= $collection ?>"><br>
            Search Term: <input type="text" name="q" placeholder="Search term ..." value="<?= $q ?>"><br>
            <input type="submit">
        </form>


        <?php foreach ($result['hits'] as $hit): ?>
            <div class="result-container">
                <div class="result-header">
                    <h3><?php echo htmlspecialchars($hit['document']['name']); ?></h3>
                </div>
                <!-- Display relevant information -->
                <div class="result-body">
                    <p>
                    <?php foreach ($hit['document'] as $key => $value) { ?>
                    <strong><?=$key?></strong> <?= $value ?><br>
                    <?php } ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>

        Page: <?= $pageNr ?>

        <?php

    }

    function deleteCollection()
    {
        if (empty($_GET['collection']))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_GET['collection']);

        try {
            $result = $this->client->collections[$collection]->delete();
            echo "<p>Deleted collection!</p><p>" . json_encode($result) . "</p>";
        } catch (Exception $e) {
            echo "Error: ",  $e->getMessage(), "\n";
        } catch (\Typesense\Exceptions\ObjectNotFound $e) {
            echo "Error: ",  $e->getMessage(), "\n";
        }

        echo '<p><a class="btn" href="?page=list_collections">List Collections</a></p>';

    }

    function createCollection()
    {
        echo '<h2>Create collection</h2><form action="?page=store_collection" method="post"><textarea name="json">
{
  "name": "companies",
  "fields": [
    {
      "name": "company_name",
      "type": "string"
    },
    {
      "name": "num_employees",
      "type": "int32",
      "facet": true
    },
    {
      "name": "country",
      "type": "string",
      "facet": true
    }
  ],
  "default_sorting_field": "num_employees",
  "enable_nested_fields": true
}</textarea><br><input type="submit"></form>';
    }

    function storeCollection()
    {
        echo '<h2>Store Collection</h2>';
        try {
            $result = $this->client->collections->create(json_decode($_POST['json'], true));
            http_response_code(201); // Created
            echo "<p>Collection created.</p>";
            echo "<p>" . json_encode($result) . "</p>";
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Typesense\Exceptions\RequestMalformed $e ) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $e->getMessage()]);
        }

        echo '<p><a class="btn" href="?page=list_collections">Back</a></p>';

    
    }

    function editCollection()
    {
        if (empty($_GET['collection']))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_GET['collection']);

        try {
            $result = $this->client->collections[$collection]->retrieve();
        } catch (Exception $e) {
            echo "Error: ",  $e->getMessage(), "\n";
            return false;
        }

        echo '<h2>Edit collection ' . $collection . '</h2>
<h3>Current collection format:</h3>
<p><div class="well">' . json_encode($result) . '</div></p>
<h3>Drop or add fields (<a href="https://typesense.org/docs/0.25.2/api/collections.html#update-or-alter-a-collection" target="_blank">more info</a>)</h3>
<form action="?page=update_collection" method="post"><textarea name="json">
{
  "fields":[
    {"name":"num_employees", "drop": true},
    {"name":"company_category", "type":"string"}
  ]
}
</textarea><input type="hidden" name="collection" value="' . $collection . '"><input type="submit"></form>';
    }

    function updateCollection()
    {

        echo '<h2>Update Colllection</h2>';
        if (empty($_POST['collection']) || empty($_POST))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_POST['collection']);

        try {
            $result = $this->client->collections[$collection]->update(json_decode($_POST['json'], true));
            echo "<p>Updated collection! Result:</p><p>" . json_encode($result) . "</p>";
        } catch (Exception $e) {
            echo "Error: ",  $e->getMessage(), "\n";
        }

        echo '<a class="btn" href="?page=list_collections">Back</a>';

    
    }

    function importCollection()
    {
        if (empty($_GET['collection']))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_GET['collection']);

        echo '<h2>Import collection for ' . $collection . '</h2>';
        echo '<h3>Upload a JSONL file via curl</h3>
<p>Create a file named ' . $collection . '.jsonl with your documents. <a href="?page=export_collection&collection=' . $collection . '">Export them here if necessary</a> You can then import by:</p>
<p><pre>
export TYPESENSE_API_KEY=YOUR_API_KEY
curl -H "X-TYPESENSE-API-KEY: ${TYPESENSE_API_KEY}" \
      -X POST \
      -T ' . $collection . '.jsonl \
      "' . $this->getBaseUrl() . '/collections/' . $collection . '/documents/import?action=create"</pre></p>';
    }

    function exportCollection()
    {
        if (empty($_GET['collection']))
        {
            echo "no collection set";
            return false;
        }

        $collection = htmlspecialchars($_GET['collection']);

        echo '<h2>Export collection</h2>';
        echo '<pre>export TYPESENSE_API_KEY=YOUR_API_KEY

curl -H "X-TYPESENSE-API-KEY: ${TYPESENSE_API_KEY}" \
      "' . $this->getBaseUrl() . '/collections/' . $collection . '/export" > documents-export-' . $collection . '.jsonl </pre>';
        echo '<a class="btn" href="https://typesense.org/docs/0.25.2/api/documents.html#export-documents" target="_blank">More info in docs</a>';
    }

    
    function listKeys()
    {
        try {
            $keys = $this->client->keys->retrieve();
        } catch (Exception $e) {
            echo "Error fetching API Keys: ",  $e->getMessage(), "\n";
            return false;
        }

        ?>

        <h2>Typesense API Keys</h2>
        <?php if (empty($keys['keys'])) {echo "No keys found"; } else { ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Key Prefix</th>
                    <th>Collections</th>
                    <th>Actions</th>
                    <th>Expires at</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keys['keys'] as $key): ?>
                    <tr>
                        <td><?= htmlspecialchars($key['id']) ?></td>
                        <td><?= htmlspecialchars($key['description']) ?></td>
                        <td><?= htmlspecialchars($key['value_prefix']) ?></td>
                        <td><?= json_encode($key['collections']) ?></td>
                        <td><?= json_encode($key['actions']) ?></td>
                        <td><?= date("Y-m-d H:i:s", htmlspecialchars($key['expires_at'])) ?></td>
                        <td>
                            <a class="btn" href="?page=delete_key&id=<?= htmlspecialchars($key['id']) ?>"  onclick="return confirmLink()">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
            </tbody>
        </table>

        <a class="btn" href="?page=create_key">+ Create Key</a>

        <script type="text/javascript">
            function confirmLink() {
                const userResponse = confirm("Are you sure you want to delete this key?");

                return userResponse;
            }
        </script>

        <?php
        }
    }

    function createKey()
    {
        echo "<h2>Create Key</h2>";
        echo "<p>View documentation for options: <a href='https://typesense.org/docs/0.25.2/api/api-keys.html' target='_blank'>Docs</a></p>";
        echo '<p><em>E.g. search only keys should have an action value of documents:search at the moment of writing.</em></p>';
        echo '<p><em>It seems to support regex at the moment of writing. E.g. an collection with coll.* would match al values with "coll" in their name.</em></p>';
        echo '<form method="post" action="?page=store_key">
<textarea name="json">
{
  "description": "Admin key",
  "actions": [
    "*"
  ],
  "collections": [
    "*"
  ]
}</textarea><br>
        <input type="submit"></form>';
    }

    function storeKey()
    {
        echo '<h2>Store key</h2>';
        try {
            $newKey = $this->client->keys->create(json_decode($_POST['json'], true));
            http_response_code(201); // Created
            echo "<h3>Key created - the part after value is the key</h3>";
            echo "<p>Will be only shown once.</p>";
            echo "<p>" . json_encode($newKey) . "</p>";
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $e->getMessage()]);
        }

        echo '<a class="btn" href="?page=list_keys">Back</a>';

    }

    function deleteKey()
    {
        echo '<h2>Delete key</h2>';
        try {
            $output = $this->client->keys[htmlspecialchars($_GET['id'])]->delete();
            http_response_code(201); // Created
            echo "<h3>Key deleted!</h3>";
            echo "<p>" . json_encode($output) . "</p>";
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $e->getMessage()]);
        }

        echo '<br><a class="btn" href="?page=list_keys">Back</a>';

    }

}