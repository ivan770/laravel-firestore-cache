# Cloud Firestore cache for Laravel
## Installation
`composer require ivan770/laravel-firestore-cache`
## Configuration
1. Register service provider in `config/app.php`
```php
Ivan770\Firestore\FirestoreServiceProvider::class,
```
2. Add `firestore` config to `config/cache.php`
```php
'firestore' => [
    'driver' => 'firestore',
    'id' => env('FIRESTORE_ID'),
    'key' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'collection' => env("FIRESTORE_COLLECTION", 'cache'),
]
```
3. Configure your .env
```dotenv
CACHE_DRIVER=firestore
FIRESTORE_ID=project_id
GOOGLE_APPLICATION_CREDENTIALS=path_to_key_file
```
## Usage
[Laravel documentation](https://laravel.com/docs/5.8/cache)
