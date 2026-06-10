<?php
class Shop {
    private $conn;
    private $table_items = "shop_items";
    private $table_user_items = "user_items";
    private $table_users = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all shop items
    public function getItems() {
        $query = "SELECT * FROM " . $this->table_items . " ORDER BY cost ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get user's inventory
    public function getUserInventory($user_id) {
        $query = "SELECT ui.*, si.name, si.description, si.type, si.value, si.icon 
                  FROM " . $this->table_user_items . " ui
                  JOIN " . $this->table_items . " si ON ui.item_id = si.id
                  WHERE ui.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Buy item
    public function buyItem($user_id, $item_id) {
        // Check if user already owns the item
        if ($this->hasItem($user_id, $item_id)) {
            return ['status' => 'error', 'message' => 'Anda sudah memiliki item ini.'];
        }

        // Get item cost
        $query_item = "SELECT cost FROM " . $this->table_items . " WHERE id = :item_id";
        $stmt_item = $this->conn->prepare($query_item);
        $stmt_item->bindParam(':item_id', $item_id);
        $stmt_item->execute();
        $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['status' => 'error', 'message' => 'Item tidak ditemukan.'];
        }

        $cost = $item['cost'];

        // Check user coins
        $query_user = "SELECT coins FROM " . $this->table_users . " WHERE id = :user_id";
        $stmt_user = $this->conn->prepare($query_user);
        $stmt_user->bindParam(':user_id', $user_id);
        $stmt_user->execute();
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($user['coins'] < $cost) {
            return ['status' => 'error', 'message' => 'Koin tidak cukup.'];
        }

        // Start transaction
        $this->conn->beginTransaction();

        try {
            // Deduct coins
            $query_deduct = "UPDATE " . $this->table_users . " SET coins = coins - :cost WHERE id = :user_id";
            $stmt_deduct = $this->conn->prepare($query_deduct);
            $stmt_deduct->bindParam(':cost', $cost);
            $stmt_deduct->bindParam(':user_id', $user_id);
            $stmt_deduct->execute();

            // Add item to inventory
            $query_add = "INSERT INTO " . $this->table_user_items . " (user_id, item_id) VALUES (:user_id, :item_id)";
            $stmt_add = $this->conn->prepare($query_add);
            $stmt_add->bindParam(':user_id', $user_id);
            $stmt_add->bindParam(':item_id', $item_id);
            $stmt_add->execute();

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Item berhasil dibeli!', 'new_coins' => $user['coins'] - $cost];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Terjadi kesalahan saat pembelian.'];
        }
    }

    // Check if user has item
    public function hasItem($user_id, $item_id) {
        $query = "SELECT id FROM " . $this->table_user_items . " WHERE user_id = :user_id AND item_id = :item_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Equip item (simplified for now, assumes one item per type active logic needs more work if we want strict type slots)
    // For now, just toggle is_equipped.
    public function equipItem($user_id, $item_id) {
        // First, get item type
        $query_type = "SELECT type FROM " . $this->table_items . " WHERE id = :item_id";
        $stmt_type = $this->conn->prepare($query_type);
        $stmt_type->bindParam(':item_id', $item_id);
        $stmt_type->execute();
        $type_row = $stmt_type->fetch(PDO::FETCH_ASSOC);
        
        if (!$type_row) return false;
        $type = $type_row['type'];

        // Unequip all other items of same type for this user
        $query_unequip = "UPDATE " . $this->table_user_items . " ui
                          JOIN " . $this->table_items . " si ON ui.item_id = si.id
                          SET ui.is_equipped = 0
                          WHERE ui.user_id = :user_id AND si.type = :type";
        $stmt_unequip = $this->conn->prepare($query_unequip);
        $stmt_unequip->bindParam(':user_id', $user_id);
        $stmt_unequip->bindParam(':type', $type);
        $stmt_unequip->execute();

        // Equip the selected item
        $query_equip = "UPDATE " . $this->table_user_items . " SET is_equipped = 1 WHERE user_id = :user_id AND item_id = :item_id";
        $stmt_equip = $this->conn->prepare($query_equip);
        $stmt_equip->bindParam(':user_id', $user_id);
        $stmt_equip->bindParam(':item_id', $item_id);
        
        return $stmt_equip->execute();
    }
}
?>