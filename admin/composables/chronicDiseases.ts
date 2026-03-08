/** Options for chronic diseases (admin and patient profile). */
export const CHRONIC_DISEASE_OPTIONS = [
  'Hypertension',
  'Diabetes',
  'Haemophilia',
  'Sickle cell',
  'Chronic Kidney disease',
  'Allergies (Drugs)',
  'Allergies (Other)',
  'Others'
] as const

export function useChronicDiseases () {
  return {
    options: CHRONIC_DISEASE_OPTIONS.map(label => ({ label, value: label }))
  }
}
