# 📸 Photo Gallery - Hostinger Deployment Guide

## 🚀 Quick Deploy to Hostinger

### Step 1: Upload Files
1. **Upload all files** from the `9thg9/` folder to your Hostinger public_html directory
2. **Set proper permissions**:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/thumbs/
   chmod 755 cache/
   chmod 755 logs/
   ```

### Step 2: Database Setup
Your database is already configured in `config.php`:
- **Host**: Your ip MySQL 
- **Database**: your database
- **Username**: Usernam
- **Password**: Password 

### Step 3: Upload Images
1. **Create albums** by placing images in subfolders within `uploads/`
2. **Upload images** via FTP or Hostinger File Manager
3. **Generate thumbnails** by accessing: `yoursite.com/regenerate_thumbs.php`

### Step 4: Access Your Gallery
```
🌐 https://yourdomain.com/
```

## 📋 Features Included

✅ **Responsive Photo Grid** - Works on all devices
✅ **Advanced Photo Viewer** - Zoom, pan, fullscreen
✅ **Mobile Gestures** - Swipe navigation, pinch zoom
✅ **Search Functionality** - Find photos quickly
✅ **Theme Toggle** - Light/dark mode
✅ **Infinite Scroll** - Smooth loading
✅ **Album Organization** - Group photos by albums
✅ **Admin Features** - Upload, manage, delete photos

## 🔧 File Structure

```
9thg9/
├── index.php          # Main gallery page
├── list.php           # API for photo data
├── upload.php         # Image upload handler
├── config.php         # Database configuration
├── album_manager.php  # Album management
├── photo_actions.php  # Photo actions (select, favorite, etc.)
├── regenerate_thumbs.php # Thumbnail generator
├── uploads/           # Original images
├── uploads/thumbs/    # Generated thumbnails
├── cache/            # Cached data
├── logs/             # Log files
└── .htaccess         # URL rewriting & security
```

## 🎨 Customization

### Change Gallery Title
Edit `index.php` line 83:
```php
<title>📸 Your Gallery Name</title>
```

### Modify Database Settings
Edit `config.php`:
```php
$DB_HOST = "your_hostinger_mysql_host";
$DB_USER = "your_mysql_username";
$DB_PASS = "your_mysql_password";
$DB_NAME = "your_database_name";
```

### Add More Images
1. Upload images to `uploads/` folder
2. Access `regenerate_thumbs.php` to create thumbnails
3. Images will automatically appear in gallery

## 🛠️ Troubleshooting

### Gallery Not Loading
- Check file permissions (755 for folders, 644 for files)
- Verify database connection in `config.php`
- Check Hostinger PHP version (7.4+ required)

### Images Not Showing
- Ensure images are in `uploads/` folder
- Run `regenerate_thumbs.php` to create thumbnails
- Check file permissions on uploads folder

### Database Errors
- Verify database credentials in `config.php`
- Check if database tables exist
- Run `setup.sql` if needed

## 📞 Support

If you encounter issues:
1. Check Hostinger error logs
2. Verify file permissions
3. Test database connection
4. Clear cache: delete files in `cache/` folder

---

**🎉 Your photo gallery is ready! Upload some images and enjoy your beautiful photo collection!**