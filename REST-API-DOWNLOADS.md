# Downloads Updater REST API

This API allows you to update program download information via HTTP requests.

## Base URL
```
https://example.com/wp-json/sc/v1/
```

## Authentication

### Option 1: WordPress User Authentication
- Must be logged in as an administrator
- For POST requests, include WordPress nonce: `X-WP-Nonce` header or `_wpnonce` parameter
- GET requests don't require nonce verification

#### Getting the WordPress Nonce
```javascript
// In your WordPress admin/frontend, add this to make nonce available
// Add to functions.php or your theme:
wp_localize_script('your-script', 'wpApiSettings', array(
    'root' => esc_url_raw(rest_url()),
    'nonce' => wp_create_nonce('wp_rest')
));

// Then in JavaScript:
const nonce = wpApiSettings.nonce;
```

```php
// Or get it directly in PHP:
$nonce = wp_create_nonce('wp_rest');
```

### Option 2: API Key Authentication (Optional)
- Add this to your `wp-config.php`:
  ```php
  define( 'SC_DOWNLOADS_API_KEY', 'your-secure-api-key-here' );
  ```
- Include in request headers: `X-API-Key: your-secure-api-key-here`
- Or as URL parameter: `?api_key=your-secure-api-key-here`

## Endpoints

### 1. Update Downloads
**POST/GET** `/wp-json/sc/v1/update-downloads`

Update program download information from external APIs.

#### Parameters
- `program` (optional): Program group to update (e.g., `libreoffice`)
- `dry_run` (optional): Set to `true` to preview changes without making them

#### Examples

**Update all programs:**
```bash
curl -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "X-API-Key: your-api-key"
```

**Update specific program group:**
```bash
curl -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"program": "libreoffice"}'
```

**Dry run (preview changes):**
```bash
curl -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"program": "firefox", "dry_run": true}'
```

**Using URL parameters:**
```bash
curl "https://example.com/wp-json/sc/v1/update-downloads?program=thunderbird&dry_run=true&api_key=your-api-key"
```

#### Response Example
```json
{
  "success": true,
  "message": "Updated 8 of 12 programs in 3.45s (4 failed)",
  "programs_processed": 12,
  "programs_updated": 8,
  "programs_failed": 4,
  "execution_time": 3.45,
  "results": [
    {
      "program": "libreoffice",
      "result": {
        "success": true,
        "message": "Successfully updated 3 versions for libreoffice: 7.6.1-windows-x64 - 7.6.1-macos-x64",
        "data": {
          "post_id": 123,
          "version_count": 3,
          "versions": [...]
        }
      }
    }
  ],
  "api_version": "1.0",
  "timestamp": "2025-08-27 15:30:45",
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 2. Get Available Programs
**GET** `/wp-json/sc/v1/update-downloads/programs`

Get list of available programs from the API.

#### Parameters
- `program` (optional): Filter by program group

#### Examples

**Get all programs:**
```bash
curl "https://example.com/wp-json/sc/v1/update-downloads/programs" \
  -H "X-API-Key: your-api-key"
```

**Filter by program group:**
```bash
curl "https://example.com/wp-json/sc/v1/update-downloads/programs?program=libreoffice" \
  -H "X-API-Key: your-api-key"
```

#### Response Example
```json
{
  "success": true,
  "programs": [
    {
      "wp": "libreoffice",
      "group": "libreoffice",
      "api": "programes/versions/libreoffice.json"
    },
    {
      "wp": "firefox",
      "group": "firefox", 
      "api": "programes/versions/firefox.json"
    }
  ],
  "count": 2,
  "api_version": "1.0",
  "timestamp": "2025-08-27 15:30:45"
}
```

## Error Responses

### Not Logged In (401)
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  "data": {
    "status": 401
  }
}
```

### Missing Nonce (403)
```json
{
  "code": "rest_missing_nonce",
  "message": "Missing nonce. Please include X-WP-Nonce header or _wpnonce parameter.",
  "data": {
    "status": 403
  }
}
```

### Invalid Nonce (403)
```json
{
  "code": "rest_invalid_nonce",
  "message": "Invalid nonce.",
  "data": {
    "status": 403
  }
}
```

### Insufficient Permissions (403)
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to access this endpoint.",
  "data": {
    "status": 403
  }
}
```

### Update Failed (500)
```json
{
  "code": "update_failed",
  "message": "Failed to fetch programs configuration from API",
  "data": {
    "status": 500,
    "success": false,
    "programs_processed": 0,
    "programs_updated": 0,
    "programs_failed": 0
  }
}
```

## Integration Examples

### JavaScript (Frontend)
```javascript
// First get the nonce (if using WordPress session auth)
const nonce = document.querySelector('#your-nonce-field').value; // or from wp_localize_script

// Update all programs with WordPress session
fetch('https://example.com/wp-json/sc/v1/update-downloads', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': nonce  // Required for WordPress session auth
  },
  body: JSON.stringify({
    program: 'libreoffice',
    dry_run: false
  }),
  credentials: 'same-origin'  // Include cookies for session auth
})
.then(response => response.json())
.then(data => {
  console.log('Update result:', data);
  if (data.success) {
    console.log(`Updated ${data.programs_updated} programs`);
  }
});

// Alternative: Using API key (no nonce needed)
fetch('https://example.com/wp-json/sc/v1/update-downloads', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your-api-key'
  },
  body: JSON.stringify({
    program: 'libreoffice',
    dry_run: false
  })
})
.then(response => response.json())
.then(data => {
  console.log('Update result:', data);
});
```

### PHP
```php
$response = wp_remote_post('https://example.com/wp-json/sc/v1/update-downloads', array(
  'headers' => array(
    'Content-Type' => 'application/json',
    'X-API-Key' => 'your-api-key'
  ),
  'body' => json_encode(array(
    'program' => 'firefox',
    'dry_run' => true
  ))
));

$data = json_decode(wp_remote_retrieve_body($response), true);
if ($data['success']) {
  echo "Programs updated: " . $data['programs_updated'];
}
```

### cURL with WordPress Session Authentication
```bash
# Method 1: Get nonce first, then use it
# Get nonce (you'll need to extract it from a WordPress page or admin)
NONCE=$(curl -s -c cookies.txt "https://example.com/wp-admin/" | grep -o 'wp_rest.*" />' | sed 's/.*value="//' | sed 's/".*//')

# Login to WordPress and save cookies
curl -c cookies.txt -b cookies.txt -d "log=admin&pwd=password&wp-submit=Log+In&redirect_to=&testcookie=1" \
  "https://example.com/wp-login.php"

# Use the authenticated session with nonce
curl -b cookies.txt -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $NONCE" \
  -d '{"program": "thunderbird"}'

# Method 2: Use nonce as parameter instead of header
curl -b cookies.txt -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "Content-Type: application/json" \
  -d '{"program": "thunderbird", "_wpnonce": "'$NONCE'"}'
```

### cURL with API Key (Simpler)
```bash
curl -X POST "https://example.com/wp-json/sc/v1/update-downloads" \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"program": "thunderbird"}'
```

## Webhook Integration

You can use this API with webhook services or automation tools:

### GitHub Actions
```yaml
- name: Update Downloads
  run: |
    curl -X POST "${{ secrets.WEBSITE_URL }}/wp-json/sc/v1/update-downloads" \
      -H "X-API-Key: ${{ secrets.DOWNLOADS_API_KEY }}"
```

### Zapier/IFTTT
Use the POST endpoint URL with your API key in webhook automation tools.

### Cron Jobs
```bash
# Add to crontab for daily updates at 3 AM
0 3 * * * curl -X POST "https://example.com/wp-json/sc/v1/update-downloads" -H "X-API-Key: your-api-key"
```
