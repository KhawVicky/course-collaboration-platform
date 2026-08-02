-- Add priority access scheduling to an existing course_collaboration database.
USE course_collaboration;

ALTER TABLE course_materials
    ADD COLUMN member_access_at DATETIME NULL AFTER uploaded_at,
    ADD COLUMN public_access_at DATETIME NULL AFTER member_access_at;

-- Existing materials keep their previous behaviour by becoming available immediately.
UPDATE course_materials
SET member_access_at = uploaded_at,
    public_access_at = uploaded_at
WHERE member_access_at IS NULL OR public_access_at IS NULL;

ALTER TABLE course_materials
    MODIFY COLUMN member_access_at DATETIME NOT NULL,
    MODIFY COLUMN public_access_at DATETIME NOT NULL,
    ADD CONSTRAINT chk_material_access_order CHECK (public_access_at >= member_access_at);
