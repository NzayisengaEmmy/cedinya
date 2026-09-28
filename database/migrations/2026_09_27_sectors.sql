-- Sector directory supplied for district IDs 3701 through 3715.
CREATE TABLE IF NOT EXISTS sectors (
    district_id INT NOT NULL PRIMARY KEY,
    sectorname VARCHAR(100) NOT NULL,
    CONSTRAINT uq_sectors_sectorname UNIQUE (sectorname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sectors (district_id, sectorname) VALUES
    (3701, 'Bushekeri'),
    (3702, 'Bushenge'),
    (3703, 'Cyato'),
    (3704, 'Gihombo'),
    (3705, 'Kagano'),
    (3706, 'Kanjongo'),
    (3707, 'Karambi'),
    (3708, 'Karengera'),
    (3709, 'Kirimbi'),
    (3710, 'Macuba'),
    (3711, 'Mahembe'),
    (3712, 'Nyabitekeri'),
    (3713, 'Rangiro'),
    (3714, 'Ruharambuga'),
    (3715, 'Shangi')
ON DUPLICATE KEY UPDATE sectorname = VALUES(sectorname);