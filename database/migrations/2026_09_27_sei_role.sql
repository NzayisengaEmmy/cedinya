ALTER TABLE users
    MODIFY COLUMN role ENUM(
        'superadmin',
        'primaryadmin',
        'OLadmin',
        'ALadmin',
        'TVETadmin',
        'admin',
        'district',
        'headteacher',
        'teacher',
        'SEI'
    ) NOT NULL DEFAULT 'headteacher';
