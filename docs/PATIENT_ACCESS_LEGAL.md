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
| **CHU transplant service practitioners** (doctors, nurses) | Same *équipe de soins*, same establishment — Art. L1110-4, II & L1110-12 | Only their assigned patients |
| **City nephrologists** (external practitioners) | Extended care team for specific patients only — care coordination relationship | Only their assigned patients |
| **Admins (ROLE_TECH_ADMIN)** | Technical role, system management | **No patient data access** (Art. L1110-4 CSP, RGPD Art. 25/32) |

---

## Important Constraints

### 1. "Need to Know" Principle (principe du besoin d'en connaître)

Access is limited to practitioners who are **explicitly assigned** to a patient.
Even within the CHU, a doctor cannot access a patient's file unless they are
in the `patient_authorized_user` join table.

When emergency access is needed for an unassigned patient, healthcare
professionals can use the **break-the-glass** mechanism (see §5 below),
which requires justification and is heavily audited.

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

1. **Patient.authorizedPractitioners** (ManyToMany → User): Join table
   `patient_authorized_user` linking specific patients to their practitioners.

2. **PatientAccessVoter**: Symfony Voter checking access with this logic:
   - `ROLE_TECH_ADMIN` → denied (no patient data access)
   - User in `patient.authorizedPractitioners` → granted (assigned)
   - Active `BreakTheGlassAccess` for this user+patient → granted (emergency)
   - Otherwise → denied

3. **AccessDeniedHandler**: When a medical professional (ROLE_DOCTOR, ROLE_NURSE)
   is denied access to a patient, they are redirected to the break-the-glass
   form instead of seeing a 403 error.

> **Note:** The `User.isChuPractitioner` field is deprecated and no longer
> used for access control decisions. It remains in the database for
> backward compatibility but has no effect.
> `ROLE_MEDICAL_ADMIN` has been removed — replaced by break-the-glass.

### References

- Code de la santé publique, Articles L1110-4, L1110-4-1, L1110-12, L1111-7
- CNIL, Référentiel relatif aux traitements de données à caractère personnel
  dans le cadre de la gestion des cabinets médicaux et paramédicaux (2020)
- Loi n° 2016-41 du 26 janvier 2016 (Loi de modernisation de notre système
  de santé) — introduced the *équipe de soins* concept

---

## §5 — Bris de Glace (Break-the-Glass) — IMPLEMENTED

> **Status**: Implemented. Break-the-glass provides audited emergency access.

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

### Implementation

The mechanism satisfies the following requirements:

1. **Explicit justification**: The user must state the reason for emergency
   access (free text, mandatory, minimum 10 characters).
2. **Heavy logging**: Every break-the-glass access is logged with:
   - User identity and role
   - Patient file accessed
   - Timestamp (accessedAt)
   - Justification provided
   - Expiration time (expiresAt)
   - Audit log event (ACTION_BREAK_THE_GLASS)
3. **A posteriori audit**: The `BreakTheGlassAccess` entity tracks review
   status (`reviewed`, `reviewedBy`, `reviewedAt`) for DPO review.
4. **Time-limited**: Access expires after 30 minutes (configurable via
   `BreakTheGlassAccess::DEFAULT_DURATION_MINUTES`).
5. **Restricted to healthcare professionals**: Only users with medical
   roles (ROLE_DOCTOR, ROLE_NURSE) may use it. Technical
   administrators (ROLE_TECH_ADMIN) can never use this capability.
6. **Automatic redirect**: When a medical professional is denied access
   to a patient, the `AccessDeniedHandler` redirects them to the
   break-the-glass form, where they must provide justification.

### Technical Design

```
Entity: BreakTheGlassAccess
  - user (ManyToOne → User)
  - patient (ManyToOne → Patient)
  - justification (text, required, min 10 chars)
  - accessedAt (datetime_immutable)
  - expiresAt (datetime_immutable, +30 min)
  - reviewed (boolean, default false)
  - reviewedBy (ManyToOne → User, nullable)
  - reviewedAt (datetime_immutable, nullable)

Voter logic:
  - User in patient.authorizedPractitioners → GRANT
  - Active (non-expired) BreakTheGlassAccess for user+patient → GRANT
  - Otherwise → DENY

AccessDeniedHandler:
  - If subject is Patient and user has medical role
    → redirect to /break-the-glass/{patientId} form
  - Otherwise → show standard 403 error
```

---

*This analysis was prepared for a BTS SIO educational project. In a real
hospital context, formal legal review by the DPO (Délégué à la Protection
des Données) and compliance with the establishment's security policy would
be required.*
