<?php
require_once '../../includes/dbh.inc.php';

try {
    // Start transaction
    $pdo->beginTransaction();

    // Drop the existing foreign key constraint
    $pdo->exec("ALTER TABLE order_items DROP FOREIGN KEY order_items_ibfk_2");

    // Modify the menu_item_id column to allow NULL
    $pdo->exec("ALTER TABLE order_items MODIFY menu_item_id INT NULL");

    // Add the foreign key constraint back with ON DELETE SET NULL
    $pdo->exec("ALTER TABLE order_items ADD CONSTRAINT order_items_ibfk_2 
                FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) 
                ON DELETE SET NULL");

    // Commit the changes
    $pdo->commit();
    echo "Database updated successfully!";
} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error updating database: " . $e->getMessage();
} 