# Media Manager Package

A Laravel package to manage media files (images, documents, videos) for the core-cms system. This package provides comprehensive media management functionality including file uploads, image processing, storage management, and a modern admin interface.

## Description

This package extends the Core CMS with complete media management capabilities. It handles file uploads, automatic image resizing, thumbnail generation, and provides a user-friendly file manager interface. The package integrates seamlessly with the Core CMS content system and offers flexible media handling for web applications.

## Features

- ✅ Complete file upload system with drag & drop support
- ✅ Automatic image processing and thumbnail generation
- ✅ Dynamic image resizing with Glide integration
- ✅ Modern file manager interface with search and filtering
- ✅ Support for multiple file types (images, documents, videos, audio)
- ✅ Organized folder structure by date (year/month)
- ✅ Content integration with media associations
- ✅ Form field components for easy media selection
- ✅ API endpoints for programmatic media management
- ✅ Multi-language support
- ✅ SVG file handling with inline rendering
- ✅ Storage disk configuration support

## Requirements

- PHP ^8.1
- Laravel ^12.0
- NetAuraTech Core CMS ^1.0
- Intervention/Image ^2.7
- League/Glide ^2.0

## Installation

### Via Composer (recommended)

```bash
composer require netauratech/media-manager
```

### Manual Installation

1. Clone the repository into your Laravel project
2. Add the dependency to your `composer.json`
3. Run `composer install`

## Configuration

### 1. Service Provider

The service provider is automatically registered thanks to Laravel's automatic discovery. If you need to register it manually:

```php
'providers' => [
    // ...
    Netauratech\MediaManager\MediaManagerServiceProvider::class,
],
```

### 2. Database Setup

Run the migrations to create the media tables:

```bash
php artisan migrate
```

This will create:
- `medias` table - Stores media file information
- Add `media_id` foreign key to `contents` table

### 3. Publishing Assets (Optional)

If you need to customize migrations or seeders:

```bash
# Publish migrations
php artisan vendor:publish --tag=media-manager-migrations

# Publish seeders
php artisan vendor:publish --tag=media-manager-seeders
```

### 4. Storage Configuration

Ensure your `config/filesystems.php` has the public disk configured:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

Run the storage link command:

```bash
php artisan storage:link
```

## Usage

### Basic File Management

#### Uploading Files

The package provides a drag & drop file manager interface accessible at `/admin/media` or through the API:

```php
// Programmatic upload
use Netauratech\MediaManager\Services\MediaProvider;

$mediaProvider = app(MediaProvider::class);
$media = $mediaProvider->store($uploadedFile);
```

#### Retrieving Media

```php
use Netauratech\MediaManager\Services\MediaProvider;

$mediaProvider = app(MediaProvider::class);

// Get media by ID
$media = $mediaProvider->get(1);

// Get original URL
$url = $mediaProvider->originalUrl(1);

// Get resized image URL
$url = $mediaProvider->url(1, ['w' => 300, 'h' => 200]);
```

### Image Processing

The package automatically handles image processing using Glide:

```php
// Generate different sizes
$thumbnail = $mediaProvider->url($mediaId, ['w' => 150, 'h' => 150]);
$medium = $mediaProvider->url($mediaId, ['w' => 500, 'h' => 300, 'fit' => 'crop']);
$large = $mediaProvider->url($mediaId, ['w' => 1200]);
```

#### Available Image Parameters

- `w` - Width in pixels
- `h` - Height in pixels
- `fit` - Resize fit mode (crop, contain, fill, etc.)
- `q` - Quality (1-100, default: 85)
- `fm` - Output format (webp, jpg, png)

### Using in Blade Templates

#### Helper Functions

The package provides several helper functions:

```blade
{{-- Generate image URL --}}
<img src="{{ image_url($mediaId, 300, 200) }}" alt="Image">

{{-- Generate complete image tag --}}
{!! image_tag($mediaId, 'Alt text', 300) !!}

{{-- For content with media --}}
@if($content->media_id)
    {!! image_tag($content->media_id, null, 900) !!}
@endif
```

#### Form Fields

Use the media form field in your forms:

```blade
@include('media-manager::form.media', [
    'label' => 'Featured Image',
    'name' => 'media_id',
    'value' => old('media_id', $content->media_id ?? '')
])
```

### JavaScript Integration

#### File Manager Component

```html
<file-manager data-endpoint="/api/media"></file-manager>
```

#### Input Media Element

```html
<input type="text" is="input-media" data-endpoint="/api/media" name="media_id">
```

### API Endpoints

The package provides several API endpoints:

```bash
# Upload file
POST /api/media

# Delete media
DELETE /api/media/{id}

# Get folders (organized by date)
GET /api/media/folders

# Get files (with optional filtering)
GET /api/media/files?q=search&path=2024/01

# Get specific media info
GET /api/media/files/{id}

# Resize image
GET /media/resize/{filename}?w=300&h=200
```

### Content Integration

The package automatically integrates with the Core CMS content system:

```php
// In your content creation
$content = new Content();
$content->title = 'My Article';
$content->media_id = 5; // Associate with media
$content->save();
```

The package listens to `ContentSaved` events and automatically manages media associations.

### Advanced Usage

#### Custom Media Types

Extend the Media model to add custom functionality:

```php
use Netauratech\MediaManager\Models\Media;

class CustomMedia extends Media
{
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
    
    public function getDuration(): ?int
    {
        // Custom logic for video duration
        return $this->metadata['duration'] ?? null;
    }
}
```

#### Storage Disk Configuration

Use different storage disks:

```php
$mediaProvider = app(MediaProvider::class);
$media = $mediaProvider->store($uploadedFile, 's3');
```

#### Custom Image Processing

```php
// Create custom image variants
$variants = [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    'medium' => ['w' => 500, 'h' => 300],
    'large' => ['w' => 1200],
];

foreach ($variants as $size => $params) {
    $urls[$size] = $mediaProvider->url($mediaId, $params);
}
```

## File Structure

```
src/
├── Http/
│   ├── Controllers/
│   │   └── MediaController.php          # Main media controller
│   └── Requests/
│       └── UploadMediaRequest.php       # Upload validation
├── Models/
│   └── Media.php                        # Media Eloquent model
├── Services/
│   └── MediaProvider.php               # Core media service
├── Listeners/
│   └── UpdateContentMedia.php          # Content integration
├── resources/
│   ├── views/                          # Blade templates
│   │   ├── form/                       # Form components
│   │   ├── blog/                       # Blog integration
│   │   └── option/                     # Option integration
│   └── ts/                             # TypeScript/JavaScript
│       ├── components/                 # Preact components
│       ├── elements/                   # Custom elements
│       └── functions/                  # Utility functions
├── database/
│   └── migrations/                     # Database migrations
├── lang/                              # Translation files
├── routes/                            # Package routes
│   ├── api.php                        # API routes
│   └── web.php                        # Web routes
└── MediaManagerServiceProvider.php    # Service provider
```

## Events

The package dispatches and listens to several events:

- `ContentSaved` - Automatically manages media associations
- Custom `media` events in JavaScript components

## Configuration Options

### Image Processing Defaults

The package uses these default settings for image processing:

- Quality: 85%
- Format: WebP (with fallback)
- Fit: Crop
- Cache: Local storage

### File Organization

Files are automatically organized by upload date:
- Path structure: `medias/YYYY/MM/filename.ext`
- Database references use just the filename
- Folder view groups files by year/month

## Translation

The package supports multiple languages with translation files in:

- English (`en/`)
- French (`fr/`)

### Adding Custom Translations

Publish and modify translation files as needed:

```php
// In your service provider
$assetManager->registerTranslationPath('my-package', __DIR__.'/lang');
```

## Browser Support

The package uses modern web technologies:
- Custom Elements (Web Components)
- ES6+ JavaScript/TypeScript
- Preact for complex UI components
- Modern CSS features

Ensure your target browsers support these features or include appropriate polyfills.

## Security

- CSRF protection on upload endpoints
- Signed URLs for image processing
- File type validation
- Permission-based access control integration

## Development

### Contributing

1. Fork the project
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Testing

The package follows Laravel testing conventions. Run tests with:

```bash
composer test
```

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Support

For support or questions:
- Email: contact@netauratech.fr
- Create an issue on GitHub

## Changelog

### v1.0.0
- Initial release
- Complete media management system
- Image processing with Glide
- File manager interface
- Content integration
- Multi-language support

## Authors

- **NetAuraTech** - *Initial work* - [NetAuraTech](mailto:contact@netauratech.fr)

---

© 2025 NetAuraTech. All rights reserved.