CREATE DATABASE IF NOT EXISTS crud_csrf_tricky;
USE crud_csrf_tricky;

CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0
);

INSERT INTO wallets (name, balance) VALUES
    ('Jouw rekening', 500.00),
    ('Spaarpot', 0.00);
