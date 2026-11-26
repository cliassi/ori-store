-- Run once on each database used by the store/ordercustomer backends.
-- This keeps OneSignal subscription UUIDs unique while allowing multiple
-- subscriptions for the same admin/customer.
ALTER TABLE push_client
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (type, id, uuid);

-- Append-only transport-attempt log. "relay_accepted" means the local push
-- relay accepted the request; it does not prove the handset displayed it.
CREATE TABLE IF NOT EXISTS push_delivery_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_type ENUM('a', 'c') DEFAULT NULL,
    recipient_id INT UNSIGNED DEFAULT NULL,
    subscription_uuid VARCHAR(64) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    outcome VARCHAR(32) NOT NULL,
    http_status SMALLINT UNSIGNED DEFAULT NULL,
    relay_response MEDIUMTEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_push_log_created_at (created_at),
    KEY idx_push_log_recipient (recipient_type, recipient_id, created_at),
    KEY idx_push_log_subscription (subscription_uuid, created_at),
    KEY idx_push_log_outcome (outcome, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
