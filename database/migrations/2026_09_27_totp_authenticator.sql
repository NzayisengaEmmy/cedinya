-- Google Authenticator-compatible TOTP settings. Secrets are base32 encoded.
ALTER TABLE users
    ADD COLUMN totp_secret VARCHAR(64) NULL AFTER password,
    ADD COLUMN totp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret;
