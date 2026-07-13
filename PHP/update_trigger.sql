SET GLOBAL log_bin_trust_function_creators = 1;
DROP TRIGGER IF EXISTS after_borrow_delete;
DELIMITER //
CREATE TRIGGER after_borrow_delete
AFTER DELETE ON borrows
FOR EACH ROW
BEGIN
    IF OLD.status IN ('pending', 'approved', 'pending_return') THEN
        UPDATE equipment SET available = available + OLD.quantity WHERE id = OLD.equipment_id;
    END IF;
END;
//
DELIMITER ;
