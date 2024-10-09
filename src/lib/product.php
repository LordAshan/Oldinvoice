<?php
// src/Lib/Product.php

namespace Ashan\Oldinvoice\Lib;

use PDO;
use PDOException;

class Product {
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Product constructor.
     *
     * @param PDO $pdo The PDO instance for database interactions.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetches a product by its ID.
     *
     * @param int $product_id The ID of the product.
     * @return array|false The product data or false if not found.
     */
    public function getById(int $product_id) {
        try {
            $sql = "SELECT * FROM products WHERE id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $product_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching product by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all products from the database.
     *
     * @return array An array of products.
     */
    public function getAll(): array {
        try {
            $sql = "SELECT * FROM products ORDER BY name ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Adds a new product to the database.
     *
     * @param string $product_number The unique product number.
     * @param string $name           The name of the product.
     * @param float  $price          The price of the product.
     * @return bool True on success, false on failure.
     */
    public function add(string $product_number, string $name, float $price): bool {
        try {
            $sql = "INSERT INTO products (product_number, name, price) VALUES (:product_number, :name, :price)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':product_number' => $product_number,
                ':name' => $name,
                ':price' => $price
            ]);
        } catch (PDOException $e) {
            error_log("Error adding product: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing product in the database.
     *
     * @param int    $product_id     The ID of the product to update.
     * @param string $product_number The new product number.
     * @param string $name           The new name of the product.
     * @param float  $price          The new price of the product.
     * @return bool True on success, false on failure.
     */
    public function update(int $product_id, string $product_number, string $name, float $price): bool {
        try {
            $sql = "UPDATE products SET product_number = :product_number, name = :name, price = :price WHERE id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':product_number' => $product_number,
                ':name' => $name,
                ':price' => $price,
                ':product_id' => $product_id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a product from the database.
     *
     * @param int $product_id The ID of the product to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $product_id): bool {
        try {
            $sql = "DELETE FROM products WHERE id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':product_id' => $product_id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a unique product number with the format: PXXXX
     * where XXXX is a zero-padded sequential number.
     *
     * @return string|null The generated product number or null on failure.
     */
    public function generateProductNumber(): ?string {
        $prefix = 'P';
        try {
            // Fetch the latest product number
            $sql = "SELECT product_number FROM products ORDER BY id DESC LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && preg_match('/^P(\d{4})$/', $result['product_number'], $matches)) {
                $lastNumber = intval($matches[1]);
                $newNumber = $lastNumber + 1;
            } else {
                // If no products exist or format is incorrect, start from 1
                $newNumber = 1;
            }

            // Format the sequential number with leading zeros (e.g., 0001)
            $sequence = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            return $prefix . $sequence; // Example: P0001
        } catch (PDOException $e) {
            error_log("Error generating product number: " . $e->getMessage());
            return null;
        }
    }
}
?>
