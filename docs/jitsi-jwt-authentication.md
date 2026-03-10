# Jitsi JWT Authentication Implementation

## Overview

This document outlines the implementation of JWT-based authentication for Jitsi video conferencing to resolve the "no moderators have yet arrived" issue by properly authenticating doctors as moderators.

## Problem Solved

The original issue was that doctors joining Jitsi meetings were not recognized as moderators, causing the "no moderators have yet arrived" message. This has been resolved through:

1. **JWT Token Generation**: Backend generates signed JWT tokens for doctors
2. **Role-Based Authentication**: Doctors receive moderator privileges in the token
3. **Token-Based Meeting Access**: Patients and doctors use tokens for secure meeting access

## Implementation Details

### Backend Changes

#### 1. New Controller: `JitsiController.php`
- **Location**: `api/app/Http/Controllers/Api/JitsiController.php`
- **Endpoints**:
  - `POST /api/v1/jitsi/generate-token` - Generate JWT for authenticated users
  - `GET /api/v1/jitsi/config` - Get Jitsi configuration

#### 2. JWT Token Structure
```json
{
  "iss": "vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b",
  "aud": "vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b",
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

#### 3. Environment Variables
```bash
# Jitsi Configuration
JITSI_DOMAIN=meet.jit.si
JITSI_APP_ID=vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b
JITSI_API_SECRET=your-jitsi-api-secret-here
```

### Frontend Changes

#### 1. Enhanced `useJitsiMeeting.ts`
- **JWT Token Request**: Automatically requests token for doctors
- **Token-Based Authentication**: Passes JWT to Jitsi API
- **Fallback Support**: Works without token for patients or when JWT fails

#### 2. Updated Room Components
- **Doctor Room**: Passes `isDoctor: true` and consultation ID
- **Patient Room**: Passes `isDoctor: false` and consultation ID

## How It Works

### For Doctors (Moderators)
1. Doctor clicks "Start Video Call"
2. Frontend requests JWT token from `/api/v1/jitsi/generate-token`
3. Backend validates doctor role and generates JWT with moderator privileges
4. Frontend passes JWT to Jitsi API
5. Jitsi authenticates doctor as moderator
6. Meeting starts without moderator prompt

### For Patients
1. Patient clicks "Join Video Call"
2. Frontend requests JWT token (without moderator privileges)
3. Backend generates JWT with basic user access
4. Patient joins meeting normally
5. Doctor (moderator) can manage meeting

## Installation & Setup

### 1. Backend Setup
```bash
# Install Firebase JWT package
cd api
composer require firebase/php-jwt

# Add environment variables to .env
cp .env.example .env
# Edit .env with your Jitsi credentials
```

### 2. Production Configuration
For production use, you have three options:

#### Option A: Jitsi as a Service (JaaS) - Recommended
1. Sign up at [8x8 JaaS](https://jaas.8x8.vc/)
2. Get your APP_ID and API_SECRET
3. Update environment variables:
```bash
JITSI_DOMAIN=your-organization.jaas.8x8.vc
JITSI_APP_ID=your-app-id
JITSI_API_SECRET=your-api-secret
```

#### Option B: Self-Hosted Jitsi
1. Deploy Jitsi Meet with JWT authentication
2. Configure JWT app in Jitsi
3. Update environment variables with your credentials

#### Option C: Public Server (Current - Limited)
- Uses fallback mode for meet.jit.si
- Limited moderator capabilities
- Not recommended for production healthcare

## Testing

### 1. Backend Testing
```bash
# Test token generation
curl -X POST http://localhost:8000/api/v1/jitsi/generate-token \
  -H "Authorization: Bearer YOUR_DOCTOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"roomName":"test-room","isModerator":true,"consultationId":"123"}'
```

### 2. Frontend Testing
1. Login as doctor
2. Navigate to consultation room
3. Click "Start Video Call"
4. Verify no moderator prompt appears
5. Check browser console for JWT token usage

## Security Considerations

### JWT Security
- **Short Expiration**: Tokens expire in 1 hour
- **User Validation**: Only authenticated users get tokens
- **Role-Based**: Only doctors get moderator privileges
- **Room-Specific**: Tokens are tied to specific rooms

### Healthcare Compliance
- **No Recording**: Recording is disabled by default
- **No Data Storage**: No consultation data stored on Jitsi servers
- **Secure Transmission**: All traffic encrypted with HTTPS/WSS

## Troubleshooting

### Common Issues

#### 1. "Invalid JWT" Error
**Cause**: Missing or incorrect JWT credentials
**Solution**: 
- Check `JITSI_APP_ID` and `JITSI_API_SECRET` in .env
- Ensure JWT package is installed: `composer install`

#### 2. Moderator Not Recognized
**Cause**: Doctor role not properly set
**Solution**:
- Verify user has `role = 'doctor'` in database
- Check token generation logic in `JitsiController.php`

#### 3. Token Generation Fails
**Cause**: Authentication or validation error
**Solution**:
- Check user is authenticated with valid token
- Verify consultation ID is valid
- Check Laravel logs: `php artisan log:tail`

### Debug Mode
Enable debug logging in `JitsiController.php`:
```php
Log::debug('Jitsi token generation', [
    'user_id' => $user->id,
    'is_moderator' => $isModerator,
    'room' => $validated['roomName']
]);
```

## API Endpoints

### Generate JWT Token
```
POST /api/v1/jitsi/generate-token
Authorization: Bearer {user_token}
Content-Type: application/json

{
  "roomName": "doctoro-consult-123",
  "isModerator": true,
  "consultationId": "123"
}
```

**Response**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "isModerator": true,
  "expiresAt": "2025-01-09 15:00:00"
}
```

### Get Jitsi Configuration
```
GET /api/v1/jitsi/config
Authorization: Bearer {user_token}
```

**Response**:
```json
{
  "domain": "meet.jit.si",
  "appId": "vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b",
  "features": {
    "jwtEnabled": true,
    "moderatorAuth": true,
    "recording": false,
    "livestreaming": false
  }
}
```

## Future Enhancements

1. **Token Refresh**: Implement automatic token refresh for long consultations
2. **Room Permissions**: Fine-grained permissions per consultation type
3. **Audit Logging**: Log all Jitsi token generations and usage
4. **End-to-End Encryption**: Add E2EE for sensitive consultations
5. **Recording with Consent**: Optional secure recording with patient consent

## Support

For issues with JWT authentication:
1. Check Laravel logs: `php artisan log:tail`
2. Verify environment variables
3. Test with the provided test endpoints
4. Check browser console for JavaScript errors
5. Ensure proper user authentication
