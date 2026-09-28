-- Indexes for the most common listing, availability, and reporting queries.
ALTER TABLE exam_files
    ADD INDEX idx_exam_files_listing (status, created_at),
    ADD INDEX idx_exam_files_availability (status, available_from, available_until),
    ADD INDEX idx_exam_files_year (status, academic_year);

ALTER TABLE access_logs
    ADD INDEX idx_access_logs_action_created (action, created_at);