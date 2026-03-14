# Quick JaaS Fix - Replace Your Domain

## The Issue

Your application is still using `meet.jit.si` instead of your JaaS domain.

## Quick Fix

### Step 1: Update Your JaaS Domain

Edit `pwa/composables/useJitsiMeeting.ts` at line 35:

```typescript
// Replace this line:
const jitsiDomain = ref('your-org.jaas.8x8.vc') // Replace with your actual JaaS domain

// With your actual JaaS domain:
const jitsiDomain = ref('your-actual-domain.jaas.8x8.vc') // Your real JaaS domain
```

### Step 2: Example

If your JaaS domain is `doctoro.jaas.8x8.vc`, change it to:

```typescript
const jitsiDomain = ref('doctoro.jaas.8x8.vc')
```

### Step 3: Restart Frontend

```bash
cd pwa
npm run dev
```

## Verify the Fix

After restarting, when you start a video call, the URL should be:
- ✅ **Correct**: `https://your-domain.jaas.8x8.vc/doctoro-consult-1`
- ❌ **Wrong**: `https://meet.jit.si/doctoro-consult-1`

## Test It

1. Login as doctor
2. Start video call
3. Check the URL in browser - should show your JaaS domain
4. No moderator prompt should appear

## Alternative: Use Environment Variable

If you prefer using environment variables:

### Update `pwa/.env`:
```bash
NUXT_PUBLIC_JITSI_DOMAIN=your-actual-domain.jaas.8x8.vc
```

### Update `pwa/composables/useJitsiMeeting.ts`:
```typescript
// Replace line 35-36:
const config = useRuntimeConfig()
const jitsiDomain = ref(config.public.jitsiDomain as string || 'your-org.jaas.8x8.vc')
const isJaaS = ref(false) // Will be set by loadJitsiConfig()
```

## What Your JaaS Domain Looks Like

Your JaaS domain from 8x8 dashboard looks like:
- `vpaas-magic-cookie-123456789.jaas.8x8.vc`
- `your-org-name.jaas.8x8.vc`

**Not**: `meet.jit.si` (this is the public server)

## Need Help?

1. Check your 8x8 JaaS dashboard
2. Look for "Domain" or "JaaS URL"
3. Copy that exact domain
4. Replace in the code above

This will immediately fix the moderator issue!
