<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // sp_approve_room_request(p_request_id)
        //
        // para sa room interaction: approval ug denial locks
        //
        // Steps (all in one atomic unit):
        //   1. Lock & validate the request row
        //   2. Mark request Approved
        //   3. Mark room Occupied
        //   4. Create lease contract
        //   5. Reject all other Pending requests for the same room
        // ---------------------------------------------------------------
        DB::unprepared("
            DROP PROCEDURE IF EXISTS sp_approve_room_request;
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_approve_room_request(IN p_request_id BIGINT)
            BEGIN
                DECLARE v_room_id      BIGINT;
                DECLARE v_user_id      BIGINT;
                DECLARE v_room_status  VARCHAR(20);

                -- Read request details (with lock via surrounding transaction)
                SELECT room_id, user_id
                INTO   v_room_id, v_user_id
                FROM   room_requests
                WHERE  request_id = p_request_id
                  AND  status = 'Pending'
                FOR UPDATE;

                IF v_room_id IS NULL THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Request not found or already processed.';
                END IF;

                -- Confirm room is still Available
                SELECT status INTO v_room_status
                FROM   rooms
                WHERE  room_id = v_room_id
                FOR UPDATE;

                IF v_room_status != 'Available' THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Room is no longer available.';
                END IF;

                -- 1. Approve the request
                UPDATE room_requests
                SET    status = 'Approved', updated_at = NOW()
                WHERE  request_id = p_request_id;

                -- 2. Mark room Occupied
                UPDATE rooms
                SET    status = 'Occupied', updated_at = NOW()
                WHERE  room_id = v_room_id;

                -- 3. Create lease contract
                INSERT INTO lease_contracts
                    (tenant_id, room_id, start_date, end_date, security_deposit, contract_status, created_at, updated_at)
                VALUES
                    (v_user_id, v_room_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0, 'Active', NOW(), NOW());

                -- 4. Reject all other Pending requests for this room
                UPDATE room_requests
                SET    status = 'Rejected', updated_at = NOW()
                WHERE  room_id = v_room_id
                  AND  request_id != p_request_id
                  AND  status = 'Pending';
            END
        ");

        // ---------------------------------------------------------------
        // sp_generate_monthly_bills()
        //
        // loop ang bills for the month kung wala pay nabutang
        // ---------------------------------------------------------------
        DB::unprepared("
            DROP PROCEDURE IF EXISTS sp_generate_monthly_bills;
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_generate_monthly_bills()
            BEGIN
                -- All DECLAREs must come first (variables, then cursors, then handlers)
                DECLARE done            INT DEFAULT FALSE;
                DECLARE v_contract_id   BIGINT;
                DECLARE v_price         DECIMAL(10,2);
                DECLARE v_month_start   DATE;
                DECLARE v_description   VARCHAR(255);
                DECLARE v_count         INT;

                DECLARE cur CURSOR FOR
                    SELECT lc.contract_id, r.price_monthly
                    FROM   lease_contracts lc
                    JOIN   rooms r ON r.room_id = lc.room_id
                    WHERE  lc.contract_status = 'Active';

                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

                -- Executable statements start here
                SET v_month_start = DATE_FORMAT(CURDATE(), '%Y-%m-01');

                OPEN cur;

                read_loop: LOOP
                    FETCH cur INTO v_contract_id, v_price;
                    IF done THEN
                        LEAVE read_loop;
                    END IF;

                    -- Check if a Rent bill already exists for this contract this month
                    SELECT COUNT(*) INTO v_count
                    FROM   bills
                    WHERE  contract_id = v_contract_id
                      AND  bill_type   = 'Rent'
                      AND  due_date    = v_month_start;

                    IF v_count = 0 THEN
                        SET v_description = CONCAT('Rent for ', DATE_FORMAT(CURDATE(), '%M %Y'));

                        INSERT INTO bills
                            (contract_id, bill_type, description, amount_due, due_date, payment_status, version, created_at, updated_at)
                        VALUES
                            (v_contract_id, 'Rent', v_description, v_price, v_month_start, 'Unpaid', 1, NOW(), NOW());
                    END IF;
                END LOOP;

                CLOSE cur;
            END
        ");

        // ---------------------------------------------------------------
        // sp_process_move_out(p_contract_id)
        //
        // terminate ang pendingmoveout
        // ---------------------------------------------------------------
        DB::unprepared("
            DROP PROCEDURE IF EXISTS sp_process_move_out;
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_process_move_out(IN p_contract_id BIGINT)
            BEGIN
                DECLARE v_room_id       BIGINT;
                DECLARE v_status        VARCHAR(30);

                SELECT room_id, contract_status
                INTO   v_room_id, v_status
                FROM   lease_contracts
                WHERE  contract_id = p_contract_id
                FOR UPDATE;

                IF v_status != 'Pending_MoveOut' THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Contract does not have a pending move-out request.';
                END IF;

                UPDATE lease_contracts
                SET    contract_status = 'Terminated', updated_at = NOW()
                WHERE  contract_id = p_contract_id;

                UPDATE rooms
                SET    status = 'Available', updated_at = NOW()
                WHERE  room_id = v_room_id;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_approve_room_request');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_generate_monthly_bills');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_process_move_out');
    }
};
