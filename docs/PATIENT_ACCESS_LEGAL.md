# Patient Access Control — Legal Basis (French Law)

## Context

This document records the legal analysis for the patient file access model
implemented in this application. The chosen model is a **hybrid approach**:
CHU service practitioners access all patients, external city nephrologists
access only their assigned patients.

---

## Legal Framework

### Medical Secrecy — Article L1110-4 du Code de la santé publique

The core principle: medical information is covered by professional secrecy
(*secret médical*). Sharing is restricted and regulated.

### "Équipe de soins" — Article L1110-12 du Code de la santé publique

Defines the **care team** (*équipe de soins*) as all healthcare professionals
who participate directly in a patient's care:
- Within the **same health establishment** (même établissement de santé)
- Or who **coordinate care** with each other for a shared patient

### Data Sharing Within the Care Team — Article L1110-4, II

Members of the same *équipe de soins* can share **strictly necessary** patient
information for care coordination **without explicit patient consent**, provided:
1. The information shared is necessary for the patient's care
2. The patient has been informed of this sharing (typically via hospital
   admission forms — not the application's responsibility)
3. Access is traceable (audit logging)

---

## Applied Access Model

| User Type | Legal Basis | Access Level |
|-----------|-------------|--------------|
| **CHU transplant service practitioners** (doctors, nurses) | Same *équipe de soins*, same establishment — Art. L1110-4, II & L1110-12 | All patients in the transplant service |
| **City nephrologists** (external practitioners) | Extended care team for specific patients only — care coordination relationship | Only their assigned patients |
| **Admins (ROLE_TECH_ADMIN)** | Technical role, system management | **No patient data access** (Art. L1110-4 CSP, RGPD Art. 25/32) |
| **Medical Admins (ROLE_MEDICAL_ADMIN)** | Senior doctor + admin | Patient access via normal rules (CHU or assigned) |
| **Patients** (ROLE_PATIENT) | Art. L1111-7 — right of access to own medical file | Own file only (future feature) |

---

## Important Constraints

### 1. "Need to Know" Principle (principe du besoin d'en connaître)

Even within the CHU, access is limited to the **transplant service** (*service
de transplantation rénale*). A CHU dermatologist should NOT access transplant
patient files. The `isChuPractitioner` flag is set per-user and should only
be enabled for users who belong to the transplant service.

### 2. Mandatory Traceability (traçabilité obligatoire)

- **CNIL** and **Article L1110-4-1** require that access to medical data be
  logged: who accessed what, when.
- This application implements activity logging via the `LoginActivity` entity
  and event subscribers.
- Future enhancement: log individual patient file accesses (not just login/logout).

### 3. Patient Information Right (droit à l'information)

Patients must be informed that their data is shared within the care team.
This is handled by hospital admission procedures (paper/verbal), not by
this application.

### 4. Patient Consent for External Sharing

For city nephrologists (external to the CHU), the patient's care coordination
relationship serves as the legal basis. In practice, the CHU transplant team
assigns external nephrologists to patients based on the existing treatment
relationship. Explicit consent is recommended but not strictly required when
the sharing is for care coordination within the extended care team.

---

## Implementation

### Technical Design

1. **User.isChuPractitioner** (boolean): Flag indicating the user belongs
   to the CHU transplant service care team.

2. **Patient.authorizedPractitioners** (ManyToMany → User): Join table
   `patient_authorized_user` linking specific patients to external doctors.

3. **PatientAccessVoter**: Symfony Voter checking access with this logic:
   - `ROLE_TECH_ADMIN` → denied (no patient data access)
   - `isChuPractitioner = true` → granted (same care team)
   - User in `patient.authorizedPractitioners` → granted (assigned)
   - Otherwise → denied

### References

- Code de la santé publique, Articles L1110-4, L1110-4-1, L1110-12, L1111-7
- CNIL, Référentiel relatif aux traitements de données à caractère personnel
  dans le cadre de la gestion des cabinets médicaux et paramédicaux (2020)
- Loi n° 2016-41 du 26 janvier 2016 (Loi de modernisation de notre système
  de santé) — introduced the *équipe de soins* concept

---

## §5 — Bris de Glace (Break-the-Glass) — NOT IMPLEMENTED

> **Status**: Documented for future implementation. Not currently in the codebase.

### Concept

"Bris de glace" is an emergency access mechanism that allows a healthcare
professional to temporarily override normal access restrictions to view a
patient file they are not normally authorized to see.

This is recognized by the **CNIL** (Commission nationale de l'informatique
et des libertés) as a legitimate feature in hospital information systems,
provided it meets strict conditions.

### Legal Basis

- **CNIL Référentiel Hôpital (2021)**: Recommends break-the-glass for
  emergency situations, with mandatory a posteriori audit.
- **Art. L1110-4 CSP**: Medical secrecy can be lifted when strictly necessary
  for patient care in emergency situations.
- **RGPD Art. 9(2)(c)**: Processing of health data justified by vital
  interests of the data subject.

### Requirements for Implementation

If implemented in the future, the mechanism must satisfy:

1. **Explicit justification**: The user must state the reason for emergency
   access (free text, mandatory).
2. **Heavy logging**: Every break-the-glass access must be logged with:
   - User identity and role
   - Patient file accessed
   - Timestamp
   - Justification provided
   - Duration of access
3. **A posteriori audit**: The DPO (Data Protection Officer) or medical
   director must regularly review all break-the-glass accesses.
4. **Alert mechanism**: Automatic notification to the DPO or security team
   when break-the-glass is triggered.
5. **Time-limited**: Access should be temporary (e.g., 15-30 minutes),
   requiring renewal if still needed.
6. **Restricted to healthcare professionals**: Only users with medical
   roles (ROLE_DOCTOR, ROLE_NURSE, ROLE_MEDICAL_ADMIN) may use it. Technical
   administrators (ROLE_TECH_ADMIN) must never have this capability.

### Suggested Technical Design

```
Entity: BreakTheGlassAccess
  - user (ManyToOne → User)
  - patient (ManyToOne → Patient)
  - justification (text, required)
  - accessedAt (datetime)
  - expiresAt (datetime, +30 min)
  - reviewed (boolean, default false)
  - reviewedBy (ManyToOne → User, nullable)
  - reviewedAt (datetime, nullable)

Voter logic addition:
  - If normal access denied AND user has medical role
    → check for active (non-expired) BreakTheGlassAccess
    → if exists → GRANT
    → if not → redirect to break-the-glass form
```

This feature is logged in `docs/TODO.txt` as a future enhancement.

---

*This analysis was prepared for a BTS SIO educational project. In a real
hospital context, formal legal review by the DPO (Délégué à la Protection
des Données) and compliance with the establishment's security policy would
be required.*
