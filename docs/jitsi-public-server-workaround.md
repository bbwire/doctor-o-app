# Jitsi Public Server Workaround

## Problem

The `meet.jit.si` public server doesn't support custom JWT authentication and requires moderator authentication, causing the "no moderators have yet arrived" message.

## Solution: Use 8x8 JaaS (Jitsi as a Service)

The most reliable solution is to use **8x8 JaaS** which provides:
- Custom JWT authentication
- Doctor moderator privileges
- Healthcare compliance
- No authentication prompts

## Quick Setup for JaaS

### 1. Sign up for JaaS
1. Go to [8x8 JaaS](https://jaas.8x8.vc/)
2. Sign up for a free account
3. Get your API credentials

### 2. Update Environment Variables
```bash
# In your .env file
JITSI_DOMAIN=your-org.jaas.8x8.vc
JITSI_APP_ID=your-app-id
JITSI_API_SECRET=your-api-secret
```

### 3. Test the Integration
1. Restart your application
2. Try joining a consultation as doctor
3. Verify no moderator prompt appears

## Alternative: Self-Hosted Jitsi

For complete control, deploy your own Jitsi instance:

### Docker Deployment
```bash
docker run -d --name jitsi-meet \
  -p 8000:8000 -p 8443:8443 \
  -e JITSI_PROSODY_DOMAIN=your-domain.com \
  -e JITSI_JICOFO_AUTHENTICATION_TYPE=jwt \
  -e JITSI_JICOFO_AUTHENTICATION_LIFETIME=3600 \
  -e JITSI_JICOFO_AUTHENTICATION_SECRET=your-secret \
  jitsi/web:stable
```

### Configure JWT Authentication
```bash
# In Prosody config
authentication = "jwt"
app_id = "your-app-id"
app_secret = "your-secret"
allow_empty_token = false
```

## Current Workaround Implementation

The enhanced code now includes:

### 1. Smart Domain Detection
- Detects if using `meet.jit.si` vs custom domain
- Applies different strategies for each

### 2. Enhanced Public Server Bypass
- More aggressive moderator bypass attempts
- Multiple check intervals (0.5s, 1.5s, 3s)
- Enhanced configuration options

### 3. Fallback Behavior
- Works with both public and private Jitsi servers
- Graceful degradation when JWT fails

## Testing the Current Workaround

### Option 1: Test with Enhanced Configuration
1. Use the current implementation
2. The system will attempt aggressive moderator bypass
3. May still show login prompt occasionally

### Option 2: Use JaaS (Recommended)
1. Sign up for JaaS account
2. Update environment variables
3. Get reliable moderator authentication

### Option 3: Self-Hosted Solution
1. Deploy Jitsi with JWT authentication
2. Configure custom domain
3. Full control over authentication

## Production Recommendation

For production healthcare use:

1. **JaaS** - Easiest and most reliable
2. **Self-Hosted** - Full control but more maintenance
3. **Public Server** - Not recommended for production

## Why Public Server Fails

The `meet.jit.si` server:
- Doesn't accept custom JWT tokens
- Has built-in authentication requirements
- Designed for public use, not healthcare applications
- Requires Google/Microsoft account for moderator access

## Immediate Test

To test if your JWT setup works:

```bash
# Check if JWT is configured
curl http://localhost:8000/api/v1/jitsi/config \
  -H "Authorization: Bearer YOUR_TOKEN"

# Should show:
{
  "features": {
    "jwtEnabled": true,
    "moderatorAuth": true
  }
}
```

If `jwtEnabled` is false, you need to configure JaaS or self-hosted Jitsi.
