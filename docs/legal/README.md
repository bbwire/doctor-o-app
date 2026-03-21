# Legal & compliance text (extracted)

Source document: `dr_o_legal_compliance_documents.docx` (repository root).

## Regenerate from Word

```bash
py -3 scripts/extract_legal_compliance.py
```

This writes:

| Output | Description |
| --- | --- |
| `docs/legal/terms-of-service.md` | Document 1 — Terms of Service |
| `docs/legal/privacy-policy.md` | Document 2 — Privacy Policy |
| `docs/legal/contact-information.md` | Consolidated contact sections + quick reference |
| `docs/legal/patient-consent-form.md` | Document 3 — Patient consent form (full text) |

The same files are copied to `pwa/content/legal/` for use in the Nuxt app (`/terms`, `/privacy`, `/contact`).

## App routes

- **Terms:** `/terms` — `pwa/pages/terms.vue`
- **Privacy:** `/privacy` — `pwa/pages/privacy.vue`
- **Contact:** `/contact` — includes structured contact details + form
