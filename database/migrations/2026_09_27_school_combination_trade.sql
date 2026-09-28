-- Stores a school's subject combination or TVET trade.
ALTER TABLE schools
    ADD COLUMN combination_trade VARCHAR(150) NULL AFTER category;