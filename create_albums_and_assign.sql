-- ===========================================
-- CREATE ALBUMS AND ASSIGN PHOTOS TO THEM
-- ===========================================

-- 1. First, let's see what albums already exist
SELECT 'Current Albums:' as Info;
SELECT id, name, description FROM albums ORDER BY id;

-- 2. Create some sample albums (if they don't exist)
INSERT IGNORE INTO albums (name, description) VALUES
('Nature Photos', 'Beautiful nature and landscape photos'),
('Family Moments', 'Family gatherings and special moments'),
('Travel Adventures', 'Photos from trips and adventures'),
('Food & Cooking', 'Delicious food and cooking photos'),
('Pets & Animals', 'Cute pet and animal photos');

-- 3. See the updated albums list
SELECT 'Updated Albums List:' as Info;
SELECT id, name, description FROM albums ORDER BY id;

-- 4. Assign photos to albums based on filename patterns
-- You can modify these patterns to match your photo naming conventions

-- Assign nature/landscape photos to "Nature Photos" album (ID: 6)
UPDATE photos SET album_id = 6 WHERE filename LIKE '%nature%' OR filename LIKE '%landscape%' OR filename LIKE '%mountain%' OR filename LIKE '%river%' OR filename LIKE '%sky%' OR filename LIKE '%sunset%' OR filename LIKE '%sunrise%' OR filename LIKE '%forest%' OR filename LIKE '%beach%' OR filename LIKE '%ocean%';

-- Assign family photos to "Family Moments" album (ID: 7)
UPDATE photos SET album_id = 7 WHERE filename LIKE '%family%' OR filename LIKE '%birthday%' OR filename LIKE '%party%' OR filename LIKE '%wedding%' OR filename LIKE '%graduation%' OR filename LIKE '%christmas%' OR filename LIKE '%holiday%';

-- Assign travel photos to "Travel Adventures" album (ID: 8)
UPDATE photos SET album_id = 8 WHERE filename LIKE '%travel%' OR filename LIKE '%trip%' OR filename LIKE '%vacation%' OR filename LIKE '%holiday%' OR filename LIKE '%airport%' OR filename LIKE '%hotel%';

-- Assign food photos to "Food & Cooking" album (ID: 9)
UPDATE photos SET album_id = 9 WHERE filename LIKE '%food%' OR filename LIKE '%cooking%' OR filename LIKE '%recipe%' OR filename LIKE '%dinner%' OR filename LIKE '%lunch%' OR filename LIKE '%breakfast%' OR filename LIKE '%cake%' OR filename LIKE '%pizza%' OR filename LIKE '%burger%';

-- Assign pet photos to "Pets & Animals" album (ID: 10)
UPDATE photos SET album_id = 10 WHERE filename LIKE '%pet%' OR filename LIKE '%dog%' OR filename LIKE '%cat%' OR filename LIKE '%animal%' OR filename LIKE '%bird%' OR filename LIKE '%horse%' OR filename LIKE '%cow%';

-- 5. Check how many photos were assigned to each album
SELECT 'Photos per Album:' as Info;
SELECT
    a.name as album_name,
    COUNT(p.id) as photo_count
FROM albums a
LEFT JOIN photos p ON a.id = p.album_id
GROUP BY a.id, a.name
ORDER BY a.id;

-- 6. See sample of assigned photos
SELECT 'Sample Assigned Photos:' as Info;
SELECT
    p.filename,
    a.name as album_name,
    p.uploaded_at
FROM photos p
LEFT JOIN albums a ON p.album_id = a.id
WHERE p.album_id IS NOT NULL
ORDER BY p.uploaded_at DESC
LIMIT 10;

-- ===========================================
-- MANUAL ALBUM CREATION COMMANDS
-- ===========================================

-- To create a new album manually:
-- INSERT INTO albums (name, description) VALUES ('Your Album Name', 'Your Description');

-- To assign specific photos to an album:
-- UPDATE photos SET album_id = [ALBUM_ID] WHERE filename LIKE '%keyword%';

-- To see all photos in a specific album:
-- SELECT p.filename, p.uploaded_at FROM photos p WHERE p.album_id = [ALBUM_ID];

-- To remove a photo from an album (set to NULL):
-- UPDATE photos SET album_id = NULL WHERE filename = 'specific_filename.jpg';

-- To delete an album (this will set album_id to NULL for all photos in that album):
-- DELETE FROM albums WHERE id = [ALBUM_ID];