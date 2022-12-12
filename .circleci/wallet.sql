CREATE TABLE `balance_account` (
                                   `balance_account_uuid` char(60) NOT NULL,
                                   `account_uuid` char(60) NOT NULL,
                                   `type` char(60) NOT NULL COMMENT 'invoice, payment, provider, user',
                                   `balance` decimal(10,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `balance_transaction` (
                                       `balance_transaction_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
                                       `account_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
                                       `amount` decimal(10,3) NOT NULL DEFAULT 0.000,
                                       `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                                       `balance` decimal(10,3) NOT NULL DEFAULT 0.000,
                                       `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
                                       `file` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                                       `transaction_datetime` datetime DEFAULT NULL,
                                       `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `bank` (
                        `bank_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
                        `bank_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `bank_iban_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `bank_swift_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `bank_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `bank_transfer_type` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transfer` (
                            `transfer_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
                            `transfer_uuid_short` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `bank_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `transfer_confirmation_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `transfer_file_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `transfer_benef_name` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `transfer_benef_iban` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
                            `transfer_cost` decimal(10,3) DEFAULT NULL,
                            `transfer_total` decimal(10,3) DEFAULT NULL,
                            `transfer_status` tinyint(1) DEFAULT NULL,
                            `transfer_created_at` datetime DEFAULT NULL,
                            `transfer_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user` (
                        `user_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
                        `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                        `auth_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
                        `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                        `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                        `bank_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `bank_account_name` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `iban` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
                        `status` smallint(6) NOT NULL DEFAULT 10,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        `verification_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `balance_account`
    ADD PRIMARY KEY (`balance_account_uuid`);

ALTER TABLE `balance_transaction`
    ADD PRIMARY KEY (`balance_transaction_uuid`),
  ADD KEY `idx-balance_transaction-user_uuid` (`user_uuid`);

ALTER TABLE `bank`
    ADD PRIMARY KEY (`bank_uuid`);

ALTER TABLE `transfer`
    ADD PRIMARY KEY (`transfer_uuid`),
  ADD KEY `fk-transfer-user_uuid` (`user_uuid`),
  ADD KEY `fk-transfer-bank_uuid` (`bank_uuid`),
  ADD KEY `idx-transfer-transfer_file_uuid` (`transfer_file_uuid`),
  ADD KEY `idx-transfer-transfer_uuid_short` (`transfer_uuid_short`);

ALTER TABLE `user`
    ADD PRIMARY KEY (`user_uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`),
  ADD KEY `idx-setting-user_uuid` (`user_uuid`),
  ADD KEY `idx-transfer-user_uuid` (`user_uuid`),
  ADD KEY `idx-transfer-bank_uuid` (`bank_uuid`);

ALTER TABLE `balance_transaction`
    ADD CONSTRAINT `fk-balance_transaction-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

ALTER TABLE `transfer`
    ADD CONSTRAINT `fk-transfer-bank_uuid` FOREIGN KEY (`bank_uuid`) REFERENCES `bank` (`bank_uuid`),
  ADD CONSTRAINT `fk-transfer-transfer_file_uuid` FOREIGN KEY (`transfer_file_uuid`) REFERENCES `transfer_file` (`transfer_file_uuid`),
  ADD CONSTRAINT `fk-transfer-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);
COMMIT;
