<?php



// Query simples
$db->executeQuery('UPDATE user SET ativo = ? WHERE id = ?', [1, 42]);

// SELECT com parâmetros
$result = $db->executeQuery('SELECT * FROM user WHERE ativo = ?', [1]);
foreach ($result as $row) { /* ... */
}
