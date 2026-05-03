/** Structured review-of-systems checklist for booking (patient). Stable `id` keys are stored in API metadata. */

export interface BookingRosSymptom {
  id: string
  label: string
}

export interface BookingRosSection {
  id: string
  title: string
  emoji?: string
  items: BookingRosSymptom[]
}

export const BOOKING_ROS_SECTIONS: BookingRosSection[] = [
  {
    id: 'cns',
    title: 'Central Nervous System (CNS)',
    emoji: '🧠',
    items: [
      { id: 'cns-headache', label: 'Headache' },
      { id: 'cns-dizziness', label: 'Dizziness / vertigo' },
      { id: 'cns-syncope', label: 'Loss of consciousness (syncope)' },
      { id: 'cns-seizures', label: 'Seizures / convulsions' },
      { id: 'cns-limb-weakness', label: 'Weakness of limbs' },
      { id: 'cns-numbness', label: 'Numbness / tingling' },
      { id: 'cns-speech', label: 'Difficulty speaking' },
      { id: 'cns-memory', label: 'Memory loss' },
      { id: 'cns-confusion', label: 'Confusion' },
      { id: 'cns-visual', label: 'Visual disturbances (blurred vision, double vision)' },
      { id: 'cns-hearing', label: 'Hearing loss' },
      { id: 'cns-gait', label: 'Difficulty walking / imbalance' },
    ],
  },
  {
    id: 'resp',
    title: 'Respiratory System',
    emoji: '🌬️',
    items: [
      { id: 'resp-cough', label: 'Cough' },
      { id: 'resp-sputum', label: 'Sputum production' },
      { id: 'resp-dyspnea', label: 'Shortness of breath (dyspnea)' },
      { id: 'resp-wheeze', label: 'Wheezing' },
      { id: 'resp-tightness', label: 'Chest tightness' },
      { id: 'resp-hemoptysis', label: 'Hemoptysis (coughing blood)' },
      { id: 'resp-stridor', label: 'Noisy breathing (stridor)' },
      { id: 'resp-night-cough', label: 'Night cough' },
      { id: 'resp-orthopnea', label: 'Orthopnea (breathlessness lying flat)' },
    ],
  },
  {
    id: 'cv',
    title: 'Cardiovascular System',
    emoji: '❤️',
    items: [
      { id: 'cv-chest-pain', label: 'Chest pain' },
      { id: 'cv-palpitations', label: 'Palpitations' },
      { id: 'cv-sob-exertion', label: 'Shortness of breath on exertion' },
      { id: 'cv-orthopnea', label: 'Orthopnea' },
      { id: 'cv-pnd', label: 'Paroxysmal nocturnal dyspnea' },
      { id: 'cv-edema', label: 'Leg swelling (edema)' },
      { id: 'cv-syncope', label: 'Syncope / fainting' },
      { id: 'cv-fatigue', label: 'Easy fatigability' },
      { id: 'cv-cyanosis', label: 'Cyanosis (bluish discoloration)' },
    ],
  },
  {
    id: 'gi',
    title: 'Gastrointestinal System',
    emoji: '🍽️',
    items: [
      { id: 'gi-abdominal-pain', label: 'Abdominal pain' },
      { id: 'gi-nausea', label: 'Nausea' },
      { id: 'gi-vomiting', label: 'Vomiting' },
      { id: 'gi-dysphagia', label: 'Difficulty swallowing (dysphagia)' },
      { id: 'gi-heartburn', label: 'Heartburn' },
      { id: 'gi-anorexia', label: 'Loss of appetite' },
      { id: 'gi-weight-loss', label: 'Weight loss' },
      { id: 'gi-diarrhea', label: 'Diarrhea' },
      { id: 'gi-constipation', label: 'Constipation' },
      { id: 'gi-gi-bleed', label: 'Blood in stool (melena / hematochezia)' },
      { id: 'gi-distension', label: 'Abdominal distension' },
      { id: 'gi-jaundice', label: 'Jaundice' },
    ],
  },
  {
    id: 'gu',
    title: 'Genitourinary System',
    emoji: '🚻',
    items: [
      { id: 'gu-dysuria', label: 'Painful urination (dysuria)' },
      { id: 'gu-frequency', label: 'Increased frequency of urination' },
      { id: 'gu-urgency', label: 'Urgency' },
      { id: 'gu-nocturia', label: 'Nocturia' },
      { id: 'gu-hematuria', label: 'Blood in urine (hematuria)' },
      { id: 'gu-incontinence', label: 'Incontinence' },
      { id: 'gu-retention', label: 'Difficulty passing urine' },
      { id: 'gu-oliguria', label: 'Reduced urine output' },
      { id: 'gu-flank', label: 'Flank pain' },
    ],
  },
  {
    id: 'gu-female',
    title: 'Additional (Female-specific)',
    emoji: '👩‍⚕️',
    items: [
      { id: 'gu-f-discharge', label: 'Vaginal discharge' },
      { id: 'gu-f-bleeding', label: 'Abnormal vaginal bleeding' },
      { id: 'gu-f-dysmenorrhea', label: 'Painful menstruation' },
      { id: 'gu-f-amenorrhea', label: 'Missed periods (amenorrhea)' },
      { id: 'gu-f-dyspareunia', label: 'Pain during intercourse' },
    ],
  },
  {
    id: 'gu-male',
    title: 'Additional (Male-specific)',
    emoji: '👨‍⚕️',
    items: [
      { id: 'gu-m-ed', label: 'Erectile dysfunction' },
      { id: 'gu-m-discharge', label: 'Penile discharge' },
      { id: 'gu-m-testicular', label: 'Testicular pain or swelling' },
    ],
  },
  {
    id: 'msk',
    title: 'Musculoskeletal / Locomotor System',
    emoji: '🦴',
    items: [
      { id: 'msk-joint-pain', label: 'Joint pain' },
      { id: 'msk-joint-swelling', label: 'Joint swelling' },
      { id: 'msk-stiffness', label: 'Stiffness (especially morning stiffness)' },
      { id: 'msk-back-pain', label: 'Back pain' },
      { id: 'msk-muscle-pain', label: 'Muscle pain' },
      { id: 'msk-muscle-weakness', label: 'Muscle weakness' },
      { id: 'msk-gait', label: 'Difficulty walking' },
      { id: 'msk-limited-rom', label: 'Limited joint movement' },
      { id: 'msk-bone-pain', label: 'Bone pain' },
    ],
  },
  {
    id: 'constitutional',
    title: 'General / Constitutional Symptoms',
    emoji: '🌡️',
    items: [
      { id: 'const-fever', label: 'Fever' },
      { id: 'const-chills', label: 'Chills' },
      { id: 'const-night-sweats', label: 'Night sweats' },
      { id: 'const-weight-loss', label: 'Weight loss' },
      { id: 'const-weight-gain', label: 'Weight gain' },
      { id: 'const-anorexia', label: 'Loss of appetite' },
      { id: 'const-fatigue', label: 'Fatigue / weakness' },
      { id: 'const-sleep', label: 'Poor sleep' },
    ],
  },
  {
    id: 'skin',
    title: 'Skin (Integumentary System)',
    emoji: '🧴',
    items: [
      { id: 'skin-rash', label: 'Rash' },
      { id: 'skin-itch', label: 'Itching (pruritus)' },
      { id: 'skin-lesions', label: 'Skin lesions' },
      { id: 'skin-ulcers', label: 'Ulcers' },
      { id: 'skin-hair', label: 'Hair loss' },
      { id: 'skin-nails', label: 'Nail changes' },
    ],
  },
  {
    id: 'endo',
    title: 'Endocrine System',
    emoji: '🧬',
    items: [
      { id: 'endo-polydipsia', label: 'Excessive thirst (polydipsia)' },
      { id: 'endo-polyuria', label: 'Excessive urination (polyuria)' },
      { id: 'endo-heat', label: 'Heat intolerance' },
      { id: 'endo-cold', label: 'Cold intolerance' },
      { id: 'endo-sweat', label: 'Excessive sweating' },
      { id: 'endo-weight-changes', label: 'Weight changes' },
    ],
  },
  {
    id: 'psych',
    title: 'Psychiatric',
    emoji: '🧠',
    items: [
      { id: 'psych-anxiety', label: 'Anxiety' },
      { id: 'psych-depression', label: 'Depression' },
      { id: 'psych-mood', label: 'Mood changes' },
      { id: 'psych-sleep', label: 'Sleep disturbances' },
      { id: 'psych-hallucinations', label: 'Hallucinations' },
      { id: 'psych-suicidal', label: 'Suicidal thoughts' },
    ],
  },
]

export function allBookingRosSymptomIds (): string[] {
  const ids: string[] = []
  for (const sec of BOOKING_ROS_SECTIONS) {
    for (const it of sec.items) {
      ids.push(it.id)
    }
  }
  return ids
}
