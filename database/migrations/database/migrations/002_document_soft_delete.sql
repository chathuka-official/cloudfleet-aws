ALTER TABLE documents
ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
ADD COLUMN delete_marker_version_id VARCHAR(255) NULL DEFAULT NULL;

CREATE INDEX idx_documents_deleted_at
ON documents(deleted_at);