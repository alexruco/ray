CREATE TABLE tb_emails (
    id SERIAL PRIMARY KEY,
    subject TEXT NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45),  -- Supports both IPv4 and IPv6 addresses
    timestamp TIMESTAMP NOT NULL,
    hash VARCHAR(64) NOT NULL  -- Assuming SHA-256 or similar hash with a length of 64 characters
);
