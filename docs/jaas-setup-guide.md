# JaaS (Jitsi as a Service) Setup Guide

## Overview

This guide walks you through setting up 8x8 JaaS for the Doctor O application. **8x8 JaaS uses RS256 (RSA) JWT signing**, not simple API secrets. You must generate an RSA key pair, upload the public key to 8x8, and use the private key to sign tokens.

## Prerequisites

- JaaS account from [8x8 JaaS](https://jaas.8x8.vc/)
- Firebase JWT package installed (`composer install`)

## Step 1: Generate RSA Key Pair

8x8 JaaS requires a 4096-bit RSA key pair. Generate it:

```bash
# Generate private key (keep this secure - never commit it!)
ssh-keygen -t rsa -b 4096 -m PEM -f jaasauth.key -N ""

# Extract public key in PEM format for upload to 8x8
openssl rsa -in jaasauth.key -pubout -outform PEM -out jaasauth.key.pub
```

## Step 2: Upload Public Key to 8x8 JaaS

1. Go to [8x8 JaaS Console](https://jaas.8x8.vc/) → Your App → API Keys
2. Click "Add API Key" or "Upload Key"
3. Paste the contents of `jaasauth.key.pub` (the public key)
4. 8x8 will generate a **Key ID (kid)** in format `vpaas-magic-cookie-<appid>/<suffix>`
5. **Download/save the Key ID** – you need it for the `kid` JWT header

## Step 3: Configure Backend

Store the **private key** securely. Two options:

**Option A: File path** (recommended for production)
```bash
# Move private key to a secure location (e.g. api/storage/app/private/)
mv jaasauth.key api/storage/app/private/jaas-private.key
chmod 600 api/storage/app/private/jaas-private.key
```

**Option B: Inline in .env** (for development)
```bash
# Use \n for newlines in the key
JITSI_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\nMIIE...\n-----END RSA PRIVATE KEY-----"
```

Update your `api/.env`:

```bash
# Jitsi 8x8 JaaS Configuration
JITSI_DOMAIN=vpaas-magic-cookie-<your-appid>.8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-<your-appid>
JITSI_KEY_ID=vpaas-magic-cookie-<your-appid>/<key-suffix>

# Private key - choose one:
JITSI_PRIVATE_KEY_PATH=storage/app/private/jaas-private.key
# OR for inline (escape newlines as \n):
# JITSI_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----"
```

**Important**: Your domain is `vpaas-magic-cookie-<appid>.8x8.vc` – the same App ID appears in domain, JITSI_APP_ID, and JITSI_KEY_ID.

## Step 4: Configure Frontend (Optional)

If the API returns your JaaS domain, the frontend will use it automatically. Otherwise:

```bash
# In pwa/.env
NUXT_PUBLIC_JITSI_DOMAIN=vpaas-magic-cookie-<your-appid>.8x8.vc
```

## Step 5: Install Dependencies

```bash
# In the api directory
composer install

# In the pwa directory (if needed)
npm install
```

## Step 6: Test Configuration

### Backend Test
```bash
# Test the Jitsi config endpoint
curl -X GET "http://localhost:8000/api/v1/jitsi/config" \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json"
```

Expected response:
```json
{
  "domain": "your-org.jaas.8x8.vc",
  "appId": "vpaas-magic-cookie-abcdef123456",
  "features": {
    "jwtEnabled": true,
    "moderatorAuth": true,
    "recording": false,
    "livestreaming": false,
    "isJaaS": true
  }
}
```

### Token Generation Test
```bash
# Test JWT token generation (for doctor)
curl -X POST "http://localhost:8000/api/v1/jitsi/generate-token" \
  -H "Authorization: Bearer YOUR_DOCTOR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "roomName": "doctoro-test-room",
    "isModerator": true,
    "consultationId": "test-123"
  }'
```

Expected response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "isModerator": true,
  "expiresAt": "2025-01-09 15:00:00",
  "domain": "your-org.jaas.8x8.vc",
  "appId": "vpaas-magic-cookie-abcdef123456"
}
```

## Step 7: Restart Services

```bash
# Restart Laravel backend
cd api
php artisan config:clear
php artisan cache:clear

# Restart frontend (if running)
cd ../pwa
npm run dev
```

## Step 8: Test Full Integration

1. Login as a doctor
2. Navigate to a consultation room
3. Click "Start Video Call"
4. Verify no moderator prompt appears
5. Test audio/video functionality

## Troubleshooting

### JWT Not Generated
**Problem**: `jwtEnabled: false` in config response
**Solution**: Check your `.env` file for correct JaaS credentials

### Token Generation Fails / Authentication Failed
**Problem**: 500 error or "authentication failed" when joining meeting
**Solution**: 
- 8x8 JaaS uses **RS256**, not HS256. You must use an RSA private key.
- Verify you uploaded the **public** key to 8x8 and are using the **private** key to sign
- Check `JITSI_KEY_ID` matches the Key ID from 8x8 (format: `vpaas-magic-cookie-xxx/yyy`)
- Ensure `JITSI_PRIVATE_KEY` or `JITSI_PRIVATE_KEY_PATH` points to a valid PEM private key
- Check Laravel logs: `php artisan log:tail`

### Moderator Prompt Still Shows
**Problem**: Still seeing "no moderators have yet arrived"
**Solution**:
- Verify domain is correct (should be your JaaS domain, not meet.jit.si)
- Check browser console for JWT token usage
- Ensure doctor user has `role = 'doctor'` in database

### Domain Issues
**Problem**: Connection errors to Jitsi
**Solution**:
- Verify your JaaS domain is correct
- Check if your JaaS subscription is active
- Ensure no firewall blocks the connection

## JWT Token Structure (8x8 JaaS)

8x8 JaaS requires specific claim values. The generated JWT uses:

- **Header**: `alg: RS256`, `kid: <your Key ID>`
- **Payload**: `aud: "jitsi"`, `iss: "chat"`, `sub: <App ID>`, `room`, `context`, `exp`, `nbf`

```json
{
  "aud": "jitsi",
  "iss": "chat",
  "sub": "vpaas-magic-cookie-abcdef123456",
  "exp": 1736409600,
  "nbf": 1736402390,
  "room": "doctoro-consult-123",
  "context": {
    "user": {
      "id": "user-id",
      "name": "Dr. John Doe",
      "email": "doctor@example.com",
      "moderator": true
    },
    "features": {
      "livestreaming": false,
      "recording": false,
      "transcription": false,
      "outbound-call": true
    }
  }
}
```

## Security Considerations

- **Keep the private key secure**: Never commit it to version control. Add `*.key` and `jaas-private.key` to `.gitignore`
- **Token expiration**: Tokens expire in 2 hours (8x8 recommendation)
- **Room-specific**: Tokens are tied to specific consultation rooms
- **Role-based**: Only doctors get moderator privileges

## Production Deployment

For production:

1. Use environment-specific configuration
2. Enable HTTPS (required for WebRTC)
3. Monitor JaaS usage and billing
4. Set up logging for token generation
5. Consider token refresh for long consultations

## Monitoring

Monitor these metrics:

- Token generation success rate
- Meeting join success rate
- Audio/video connection quality
- JaaS API usage and limits

## Support

For JaaS-specific issues:
- Check [8x8 JaaS Documentation](https://jaas.8x8.vc/docs)
- Contact 8x8 support for API issues
- Monitor your JaaS dashboard for usage

For application issues:
- Check Laravel logs
- Verify database user roles
- Test with the provided test pages
