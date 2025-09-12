-- Migration script to add album system to existing database
-- Run this after the initial setup.sql

-- Add album_id column to photos table
ALTER TABLE photos ADD COLUMN album_id INT;
ALTER TABLE photos ADD CONSTRAINT fk_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE SET NULL;

-- Create albums table
CREATE TABLE IF NOT EXISTS albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create a default "General" album for existing photos
INSERT INTO albums (name, description) VALUES ('General', 'Default album for existing photos');

-- Update existing photos to use the default album
UPDATE photos SET album_id = (SELECT id FROM albums WHERE name = 'General' LIMIT 1);