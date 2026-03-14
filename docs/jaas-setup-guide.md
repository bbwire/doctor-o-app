# JaaS (Jitsi as a Service) Setup Guide

## Overview

This guide walks you through setting up JaaS for the Doctor O application to resolve the "no moderators have yet arrived" issue with proper JWT authentication.

## Prerequisites

- JaaS account from [8x8 JaaS](https://jaas.8x8.vc/)
- Your JaaS API credentials (App ID and API Secret)
- Firebase JWT package installed (`composer install`)

## Step 1: Get JaaS Credentials

1. Sign up at [8x8 JaaS](https://jaas.8x8.vc/)
2. Create a new app or use existing one
3. Note your:
   - **App ID** (e.g., `vpaas-magic-cookie-abcdef123456`)
   - **API Secret** (long string)
   - **JaaS Domain** (e.g., `your-org.jaas.8x8.vc`)

## Step 2: Configure Backend

Update your `.env` file in the `api` directory:

```bash
# Jitsi JaaS Configuration
JITSI_DOMAIN=your-org.jaas.8x8.vc
JITSI_APP_ID=vpaas-magic-cookie-abcdef123456
JITSI_API_SECRET=your-very-long-api-secret-key-here
```

**Important**: Replace the values with your actual JaaS credentials.

## Step 3: Configure Frontend

Update your `.env` file in the `pwa` directory:

```bash
# Jitsi domain
NUXT_PUBLIC_JITSI_DOMAIN=your-org.jaas.8x8.vc
```

## Step 4: Install Dependencies

```bash
# In the api directory
composer install

# In the pwa directory (if needed)
npm install
```

## Step 5: Test Configuration

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

## Step 6: Restart Services

```bash
# Restart Laravel backend
cd api
php artisan config:clear
php artisan cache:clear

# Restart frontend (if running)
cd ../pwa
npm run dev
```

## Step 7: Test Full Integration

1. Login as a doctor
2. Navigate to a consultation room
3. Click "Start Video Call"
4. Verify no moderator prompt appears
5. Test audio/video functionality

## Troubleshooting

### JWT Not Generated
**Problem**: `jwtEnabled: false` in config response
**Solution**: Check your `.env` file for correct JaaS credentials

### Token Generation Fails
**Problem**: 500 error when generating token
**Solution**: 
- Check Laravel logs: `php artisan log:tail`
- Verify App ID and API Secret are correct
- Ensure `firebase/php-jwt` is installed

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

## JWT Token Structure

The generated JWT tokens include:

```json
{
  "iss": "vpaas-magic-cookie-abcdef123456",
  "aud": "vpaas-magic-cookie-abcdef123456",
  "exp": 1736409600,
  "iat": 1736406000,
  "sub": "user-id",
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
  },
  "room": "doctoro-consult-123"
}
```

## Security Considerations

- **Keep API Secret secure**: Never commit to version control
- **Token expiration**: Tokens expire in 1 hour
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
