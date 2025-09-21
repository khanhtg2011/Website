# 📸 Khanh's Photo Gallery

A modern, secure, and feature-rich photo gallery web application with admin controls, private memories vault, and multimedia support.

## 🌟 Features

### 🎨 **Core Features**
- **Responsive Gallery**: Beautiful grid layout that works on all devices
- **Image & Video Support**: Upload and view photos (JPEG, PNG, GIF, WebP) and videos (MP4, AVI, MOV, WebM)
- **Modal Viewer**: Full-screen image/video viewer with navigation
- **Lazy Loading**: Optimized performance with lazy-loaded images
- **Search & Filter**: Filter photos by album (for regular users)
- **Admin Panel**: Complete administrative control

### 🔐 **Security & Privacy**
- **Admin Authentication**: Secure login system with rate limiting
- **Private Memories**: Completely separate vault for personal photos/videos
- **Session Management**: Automatic logout and activity monitoring
- **File Validation**: Strict upload validation and size limits
- **Access Control**: Admin-only features and private content

### 👥 **User Types**

#### Regular Users
- Browse public photos in albums
- View images/videos in full-screen modal
- Rate and comment on photos
- Download images (admin permission required)

#### Administrators
- All regular user permissions
- Upload photos/videos to albums
- Create and manage albums
- Delete photos/videos
- Access private memories vault
- View analytics and logs
- Manage user feedback

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+ with GD extension
- MySQL/MariaDB (optional - file-based fallback available)
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone/Download** the project files to your web server directory

2. **Configure Database** (Optional - skip for file-based mode):
   ```bash
   # Run the database setup
   php setup_database.php
   ```

3. **Set Admin Credentials**:
   ```bash
   php setup_admin_credentials.php
   ```

4. **Start the Server**:
   ```bash
   # Using PHP built-in server
   php -S localhost:8000

   # Or configure Apache/Nginx to serve the directory
   ```

5. **Access the Gallery**:
   - Open `https://Your-Domain` in your browser
   - Login as admin using your configured credentials

## 📖 User Guide

### 🖼️ **Browsing the Gallery**

#### For Regular Users:
1. **View Photos**: Click any photo thumbnail to open in full-screen
2. **Navigate**: Use arrow keys or click navigation buttons
3. **Albums**: Select different albums from the dropdown
4. **Rate Photos**: Click stars to rate photos (1-5 stars)
5. **Leave Comments**: Add feedback in the comment box

#### Keyboard Shortcuts:
- `←` `→` - Navigate between photos
- `Escape` - Close modal
- `Ctrl+A` (admin) - Select all photos

### 👑 **Admin Features**

#### Accessing Admin Mode:
1. Click **"🔒 Đăng nhập"** (Login) button
2. Enter admin credentials
3. Failed attempts are limited (3 max, then temporary lockout)

#### Photo Management:
- **Upload**: Click **"⬆️ Upload"** to add photos/videos
- **Select Mode**: Click **"👆 Select Mode: OFF"** to enable bulk selection
- **Delete**: Select photos and click **"🗑️ Xóa"** (Delete)
- **Move**: Move selected photos between albums

#### Album Management:
- **Create Albums**: Click **"📁 Quản lý"** (Manage) to create new albums
- **Password Protection**: Add passwords to make albums private
- **Album Stats**: View photo counts per album

#### Private Memories Vault:
- **Access**: Click **"💝 Private Memories"** button (admin only)
- **Upload**: Add personal photos/videos that no one else can see
- **Secure Storage**: Completely separate from public gallery

### 🎥 **Video Support**

- **Upload Videos**: Select video files along with photos
- **Video Playback**: Click video thumbnails to play with controls
- **Supported Formats**: MP4, AVI, MOV, QuickTime, WebM
- **Size Limits**: 100MB for videos, 10MB for photos

## 🏗️ **Technical Architecture**

### 📁 **File Structure**
```
/
├── index.php              # Main gallery page
├── admin-gallery.php      # Private memories vault
├── albums.php             # Album management API
├── list.php               # Photo listing API
├── upload.php             # File upload handler
├── delete.php             # Delete photos API
├── feedback.php           # User feedback system
├── private_memories_handler.php  # Private vault API
├── config.php             # Configuration and database setup
├── style.css              # Main stylesheet
├── secret-page.js         # Frontend JavaScript
├── private_memories/      # Private vault storage
│   ├── originals/         # Full-size private files
│   ├── thumbs/           # Private thumbnails
│   └── memories.json     # Private metadata
├── uploads/               # Public photo storage
├── data/                  # Analytics and logs
└── cache/                 # Performance cache
```

### 🔌 **API Endpoints**

#### Public Endpoints:
- `GET /` - Main gallery
- `GET /list.php` - Get photos by album
- `POST /feedback.php` - Submit photo ratings/comments

#### Admin Endpoints:
- `POST /upload.php` - Upload photos/videos
- `POST /delete.php` - Delete photos
- `GET/POST /albums.php` - Album management
- `GET /admin-gallery.php` - Private memories vault
- `POST /private_memories_handler.php` - Private vault operations

#### Authentication:
- `POST /index.php?admin_login_ajax=1` - Admin login
- `GET /admin_logout` - Admin logout

### 🗄️ **Database Schema** (Optional)

#### Photos Table:
```sql
CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    album_id INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploader VARCHAR(100),
    ip_address VARCHAR(45),
    file_size INT,
    metadata TEXT
);
```

#### Albums Table:
```sql
CREATE TABLE albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    password VARCHAR(255), -- NULL for public albums
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 🔒 **Security Features**

- **Session-based authentication** with automatic expiration
- **CSRF protection** on all forms
- **Rate limiting** on login attempts
- **File type validation** and size limits
- **SQL injection prevention** with prepared statements
- **XSS protection** with input sanitization
- **Private file storage** outside web root for sensitive data

### 📊 **Analytics & Monitoring**

- **Visitor tracking** (non-admin users only)
- **Performance monitoring** (Core Web Vitals)
- **Admin activity logs**
- **Feedback statistics**
- **Cache performance metrics**

## 🛠️ **Configuration**

### config.php
```php
// Database settings (optional)
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'photo_gallery');

// File size limits
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100MB

// Admin credentials
define('ADMIN_USER', 'your_username');
define('ADMIN_PASS', 'your_password');
```

### Environment Variables
- `DB_AVAILABLE` - Set to false to use file-based storage
- `CSRF_TOKEN` - Auto-generated for form security

## 🔧 **Troubleshooting**

### Common Issues:

#### "Class 'mysqli' not found"
- Install PHP MySQL extension: `sudo apt install php-mysql`
- Or use file-based mode by setting `DB_AVAILABLE = false`

#### Upload fails
- Check file permissions on `uploads/` and `private_memories/` directories
- Verify PHP upload limits in `php.ini`
- Check file size limits in `config.php`

#### Admin login not working
- Run `php setup_admin_credentials.php` to set credentials
- Check session save path permissions
- Clear browser cookies if issues persist

#### Videos not playing
- Ensure videos are in supported formats (MP4, WebM recommended)
- Check browser video codec support
- Verify file uploaded completely

### Performance Optimization:
- Enable PHP OPcache
- Use a CDN for static assets
- Configure proper caching headers
- Monitor Core Web Vitals in browser dev tools

## 📝 **Development**

### Adding New Features:
1. Follow the existing file structure
2. Use prepared statements for database queries
3. Implement CSRF protection on forms
4. Add proper error handling
5. Test on multiple devices/browsers

### Code Style:
- PHP: PSR-12 standards
- JavaScript: Modern ES6+ with error handling
- CSS: Mobile-first responsive design
- HTML: Semantic markup with accessibility

## 📄 **License**

This project is private and proprietary. All rights reserved.

## 👨‍💻 **Support**

For technical issues or feature requests, check the error logs in `data/` directory and verify configuration in `config.php`.

---

**Built with ❤️ for personal photo management and sharing**
