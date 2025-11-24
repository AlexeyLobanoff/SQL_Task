<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'connect.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ---------------------------
        // ПРОЦЕДУРЫ-ОТЧЁТЫ
        // ---------------------------

        case 'getOrdersByStatus': {
            $status = (int)($_GET['status'] ?? 0);
            $stmt = $pdo->prepare("CALL GetOrdersByStatus(:status)");
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;
        }

        case 'getCompletedOrdersByPeriod': {
            $start = $_GET['startDate'] ?? '2025-01-01';
            $end   = $_GET['endDate']   ?? '2025-12-31';
            $stmt = $pdo->prepare("CALL GetCompletedOrdersByPeriod(:start, :end)");
            $stmt->bindParam(':start', $start);
            $stmt->bindParam(':end', $end);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;
        }

        case 'getTopMasters': {
            $top = (int)($_GET['top'] ?? 5);
            $stmt = $pdo->prepare("CALL GetTopMasters(:top)");
            $stmt->bindParam(':top', $top, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;
        }

        // ---------------------------
        // CRUD ДЛЯ ПРОВЕРКИ ТРИГГЕРОВ
        // ---------------------------

        // создание мастера
        case 'createMaster': {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $sql = "INSERT INTO Masters
                    (Full_Name, Address, Phone, Birth_Date,
                     Master_Tax_Id, Insurance_Certificate_Number,
                     Is_deleted)
                    VALUES
                    (:name, :addr, :phone, :bdate,
                     :tax, :ins, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'  => $data['full_name'],
                ':addr'  => $data['address'],
                ':phone' => $data['phone'],
                ':bdate' => $data['birth_date'],
                ':tax'   => $data['master_tax_id'],
                ':ins'   => $data['insurance_certificate_number'],
            ]);

            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
            break;
        }

        // создание заказа (тут сработают триггеры дат и материалов)
        case 'createOrder': {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $status = (int)($data['status'] ?? 0);
            $materialId = isset($data['material_id']) ? (int)$data['material_id'] : null;

            $sql = "INSERT INTO Orders
                    (Master_Tax_Id, Order_Number, Customer_Name,
                     Order_Date, Completion_Date, Cost,
                     Special_Instructions, Status, Is_deleted, Material_Id)
                    VALUES
                    (:master_tax_id,
                     (SELECT IFNULL(MAX(Order_Number),0)+1 FROM Orders),
                     :customer_name,
                     :order_date,
                     :completion_date,
                     :cost,
                     :instructions,
                     :status,
                     0,
                     :material_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':master_tax_id'   => $data['master_tax_id'],
                ':customer_name'   => $data['customer_name'],
                ':order_date'      => $data['order_date'],
                ':completion_date' => $data['completion_date'] ?? null,
                ':cost'            => $data['cost'] ?? null,
                ':instructions'    => $data['special_instructions'] ?? null,
                ':status'          => $status,
                ':material_id'     => $materialId,
            ]);

            echo json_encode(['ok' => true]);
            break;
        }

        // изменение статуса заказа
        case 'updateOrderStatus': {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $sql = "UPDATE Orders
                    SET Status = :status
                    WHERE Order_Number = :order_number";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status'       => (int)$data['status'],
                ':order_number' => (int)$data['order_number'],
            ]);

            echo json_encode(['ok' => true, 'rows' => $stmt->rowCount()]);
            break;
        }

        // просмотр всех заказов (чистый SELECT, удобно для проверки)
        case 'listOrders': {
            $stmt = $pdo->query("SELECT * FROM Orders ORDER BY Order_Date DESC");
            echo json_encode($stmt->fetchAll());
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}
