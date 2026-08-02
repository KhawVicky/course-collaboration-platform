-- Seed material rows for each priority-access criteria.
-- Reuses files already present in uploads/materials.
USE course_collaboration;

INSERT INTO course_materials
    (course_id, uploaded_by, title, description, stored_filename,
     original_filename, mime_type, file_size, uploaded_at,
     member_access_at, public_access_at)
SELECT
    c.id,
    instructor.id,
    dataset.title,
    dataset.description,
    dataset.stored_filename,
    dataset.original_filename,
    'application/pdf',
    dataset.file_size,
    NOW(),
    dataset.member_access_at,
    dataset.public_access_at
FROM courses c
JOIN users instructor ON instructor.email = 'instructor@example.com'
JOIN (
    SELECT
        'Access Criteria 01 - Before Member Access' AS title,
        'Both Member and Non-member students should see Not available yet.' AS description,
        '01bf4e31d31e327bf7a94006565fcbbc0994cdc1.pdf' AS stored_filename,
        'Final Project Assessment Brief.pdf' AS original_filename,
        809551 AS file_size,
        TIMESTAMPADD(DAY, 2, NOW()) AS member_access_at,
        TIMESTAMPADD(DAY, 4, NOW()) AS public_access_at
    UNION ALL
    SELECT
        'Access Criteria 02 - Member Priority Window',
        'Members can access now; Non-members wait until Public Access Time.',
        'Week 5 - Software Development Methodologies - V&V and Test-driven development.pdf',
        'Week 5 - Software Development Methodologies - V&V and Test-driven development.pdf',
        499255,
        TIMESTAMPADD(DAY, -2, NOW()),
        TIMESTAMPADD(DAY, 2, NOW())
    UNION ALL
    SELECT
        'Access Criteria 03 - Public Access Open',
        'Both Member and Non-member students can access this material now.',
        'Week 6 - Software Development Methodologies - CI and Delivery.pdf',
        'Week 6 - Software Development Methodologies - CI and Delivery.pdf',
        579348,
        TIMESTAMPADD(DAY, -4, NOW()),
        TIMESTAMPADD(DAY, -2, NOW())
    UNION ALL
    SELECT
        'Access Criteria 04 - Same-Time Release',
        'Member and Public Access Time are equal; this checks the boundary case.',
        '01bf4e31d31e327bf7a94006565fcbbc0994cdc1.pdf',
        'Final Project Assessment Brief.pdf',
        809551,
        TIMESTAMPADD(DAY, 6, NOW()),
        TIMESTAMPADD(DAY, 6, NOW())
) dataset
WHERE c.course_code = 'CSDM301'
  AND NOT EXISTS (
      SELECT 1
      FROM course_materials existing
      WHERE existing.course_id = c.id
        AND existing.title = dataset.title
  );
