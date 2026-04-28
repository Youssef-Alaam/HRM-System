# Egyptian Labor & Employment Compliance Rules

> **Critical reference document.** Claude Code reads this when implementing payroll, leave, termination, hiring, and any compliance-touching feature.
>
> **Last updated:** 2026-04-28
> **Sources:** Egyptian Labor Law No. 14 of 2025, Social Insurance Law No. 148 of 2019, Personal Data Protection Law No. 151 of 2020, Income Tax Law No. 91 of 2005 (and amendments).
>
> **Important:** Laws change. Verify any rule before final production deployment, especially around tax rates and social insurance caps which adjust annually.

---

## How to use this document

- When implementing a feature with legal implications, read the relevant section first.
- Cite the law/article in code comments when implementing a calculation: `// Per Law 14/2025 Art. 89 — annual leave entitlement scales by tenure`.
- Never make up rules. If unsure, flag for Walid to verify.
- All EGP amounts assume 2026 rates. Update annually.

---

## 1. Working hours and overtime

### Standard working hours
- **Maximum 8 hours per day** (per Labor Law Art. 80)
- **Maximum 48 hours per week** (Art. 80)
- **One rest day per week** (Friday or Saturday for most private sector — YZH defaults Sunday-Thursday workweek per Decision 9)
- **Lunch break of at least 1 hour** for shifts longer than 5 hours

### Overtime rules (Labor Law Art. 85)
- **Maximum 2 hours overtime per day**
- **Maximum 12 hours total work per day** (regular + overtime)
- **Overtime rate: 1.5x regular hourly rate** (daytime)
- **Overtime on rest day: 2x regular rate**
- **Overtime on public holiday: 3x regular rate** (matches our Decision 17 — show up = triple pay)

### Implementation rules
- Calculate hourly rate as: `monthly_salary / (working_days_per_month * hours_per_day)`
- Overtime calculation must respect the 2hr/day cap (anything beyond is unpaid or requires HR documentation)
- Minimum 12-hour rest period required between shifts (Art. 80)

---

## 2. Annual leave (Labor Law Art. 89)

### Entitlement scaling
| Tenure / condition | Annual leave days |
|---|---|
| Less than 1 year | **15 days** (was 21 under old law) |
| 1-10 years tenure | **21 days** |
| 10+ years tenure OR age 50+ | **30 days** |
| Disabled employees (any tenure) | **45 days** |
| Months 6-12 (first year) | Prorated, minimum 15 days by month 12 |

### Implementation
- Entitlement is **calculated, not stored** (function: `calculateAnnualLeaveEntitlement(employee)`)
- Recalculated on every anniversary of hire date
- Disability status comes from employee profile (HR-set with medical doc)
- Per Decision 18: universal law-minimum balance from Day 1; contract extras added manually by HR

### Year-end behavior
- Per Decision 12 + ANA-3.15: configurable per contract:
  - `paid_out` (default for YZH) — unused days paid at year-end
  - `forfeit` — unused days lost
  - `rollover_max_1_year` — carry forward 1 year max
  - `rollover_max_2_years` — Egyptian law allows up to 2 years rollover

### Approval discretion (Decision 19)
- Annual leave: manager has approval discretion. Refusal requires mandatory reason.
- Can be refused for staffing needs.
- Public holidays NOT counted in leave days (Decision 16)
- Weekends ARE counted when included in leave period; confirmation modal warns employee on submission (per Cycle 1 ANA-2.17)

---

## 3. Sick leave (Labor Law Art. 91-92)

### Layered payment structure
| Cumulative sick days in calendar year | Payment % of salary |
|---|---|
| Days 1-90 | **75%** |
| Days 91-180 | **85%** |
| Days 180+ | **0%** (unpaid, but tracked) |
| Chronic illness (doctor-certified) | **100%**, indefinite |

### Documentation requirements
- Medical certificate **required for absence of 3+ consecutive days**
- Certificate must be from a registered physician or government clinic
- Without valid certificate: leave is rejected OR converted to unpaid leave (per company policy)
- Chronic illness flag set by HR after physician certification (separate workflow)

### Approval (Decision 19)
- Sick leave is a **right**, not discretionary
- Manager can ONLY refuse for: missing required documentation OR fraud suspicion
- Fraud refusal requires HR involvement
- Available from Day 1 of employment (no tenure requirement)

---

## 4. Casual leave (Labor Law Art. 90)

- **7 days per year** (NEW in 2025 law, was 6)
- **Maximum 2 consecutive days** per single instance
- **Deducted from annual leave balance** (not a separate pool)
- Can be submitted same-day or retroactively (up to 24 hours)
- Manager approval only (no HR approval needed for casual)
- Available from Day 1 of employment (per Decision 18)

---

## 5. Permissions (hourly leave)

- Hours-based, not days-based
- For partial-day absences: doctor visits, prayer time, urgent personal matters
- Configurable hours per year (typically 4-8 hours/month allowance)
- Manager approval (rarely refused)
- Tracked in minutes for accuracy

---

## 6. Maternity leave (Labor Law Art. 93)

- **120 days paid at 100% of salary** (NEW: was 90 days under old law)
- Applies to all female employees regardless of tenure
- **Maximum 3 instances** during entire service with employer
- Cannot decrement annual leave balance (separate pool)
- **Termination prohibited during maternity leave** (Art. 93 — legal protection)
- HR uploads birth certificate to employee record
- Auto-calculates expected return date (start_date + 120 days)

### System enforcement
- Flag employee as `on_protected_leave` when maternity active
- Block any termination action during this period
- HR override requires explicit legal justification + audit log entry

---

## 7. Paternity leave (Labor Law Art. 93 bis — NEW in 2025)

- **1 day paid leave** per child birth
- **Maximum 3 instances** during service
- Must be taken within 30 days of birth
- HR uploads birth certificate to employee record
- Cannot overlap with annual or other leave types

---

## 8. Study leave (Labor Law Art. 94)

- Paid leave for accredited education exam days
- Doesn't decrement annual balance (separate workflow)
- **10-day advance notice required** (system enforces — submission blocked if less)
- Exam attendance proof required post-facto (certificate or stamp)
- Applies only to government-recognized educational institutions
- Manager has approval discretion (can refuse for staffing needs per Decision 19)

---

## 9. Probation period (Labor Law Art. 41)

- **Maximum 3 months** (cannot be extended)
- Either party can terminate **without notice** during probation
- **No severance owed** during probation
- Cannot place existing permanent employees on probation
- Must be in writing in the contract

### System enforcement
- Probation contract auto-enforces 3-month max duration
- Day 75: HR alert ("Probation for [Employee] ends in 15 days")
- Day 90: Auto-convert to indefinite contract if no action
- "Terminate without notice" button only visible during probation

---

## 10. Notice period and termination (Labor Law Art. 110-115)

### Notice periods
- **Indefinite contracts (post-probation): 3 months notice** (uniform — was tenure-based, now standardized)
- **During probation: no notice required** (either side)
- **Fixed-term contracts: 1 month notice** OR pay-in-lieu equal to 1 month per year served if employer terminates early
- **Limited-term contracts: compensation = 1 month salary per year of service** if employer terminates early (NEW in 2025)

### Resignation rules (NEW in 2025 — major change)
- **Resignation must be authenticated by labor office** before submission
- Eliminates abuse of "Form No. 6" pre-signed resignation forms
- **Employer has 10 days to respond** to authenticated resignation
- **Employee has 10 days to withdraw** after acceptance
- System requires upload of authenticated resignation document

### Deemed resignation (Labor Law Art. 69)
- **10 consecutive unauthorized absent days** = deemed resignation, OR
- **20 non-consecutive unauthorized absences in a year** = deemed resignation
- System auto-detects via nightly background job (per Decision/ANA-3.14)
- HR must review + confirm before finalizing (cannot auto-terminate)
- Employer must attempt contact before processing
- Standard termination workflow with reason "deemed_resignation"

### End-of-service benefits (EOSB) calculation
- **Indefinite contracts:**
  - First 5 years: half month's salary per year of service
  - Above 5 years: full month's salary per year for those additional years
- **Fixed-term: 1 month salary per year** if employer terminates early
- Calculated on the last drawn salary
- Includes basic salary plus regular monthly allowances (excludes one-time bonuses)
- Tax-exempt up to certain thresholds (verify with current tax law)

### Termination protections
- **Cannot terminate during:**
  - Maternity leave (Art. 93)
  - Sick leave with valid medical certificate
  - Authorized leave of any kind
  - Pregnancy (entire pregnancy + maternity period)
  - Union officer status (Art. 73)
- **Just cause termination** (no notice, no severance) requires documented disciplinary process per Art. 67-69:
  - Repeated violation of work regulations
  - Drunk/impaired at work
  - Theft, fraud, or violence
  - Disclosure of trade secrets
  - Repeated unjustified absence (different from deemed resignation)

---

## 11. Retirement (Social Insurance Law 148/2019)

- **Standard retirement age: 60 years**
- Pension eligibility: minimum 10 years of contributions
- System workflow:
  - 6 months before 60th birthday: HR alert ("Initiate retirement process")
  - 3 months before: retirement workflow triggered
  - On 60th birthday: auto-termination with reason "retirement" UNLESS HR sets `retirement_extended_until = [date]`
  - Final payslip + EOSB + NOSI notification
  - System access revoked
  - Employee transitioned to "retired" status (archived, retained 7 years per data retention)

### Extension allowed
- HR can extend with mutual agreement (record `retirement_extended_until` date)
- Workflow re-triggers 3 months before extended date

---

## 12. Social Insurance — Egyptians (Law 148/2019)

### Contribution rates
- **Employee contribution: 11% of insurable wage**
- **Employer contribution: 18.75% of insurable wage**
- **Health insurance: 1% employee + 3.25% employer** (separate from main SI)
- **Training fund: 0.25% employer** (paid to government training fund)
- **Total employer cost: 22.25% of insurable wage**
- **Total employee deduction: 12% of insurable wage**

### Insurable wage caps
- **Minimum (2026): EGP 2,300/month**
- **Maximum (2026): EGP 14,500/month**
- Insurable wage = base + regular monthly allowances (not bonuses)
- Wages above the maximum cap → contributions calculated on the cap, not actual wage
- **Caps adjust annually by government decree** — must be configurable in Settings

### Implementation rules
- All calculations in **piasters as integers** (1 EGP = 100 piasters per Decision 8)
- Half-up rounding for fractional piasters
- Caps stored in `system_settings` table, editable by Admin
- Year-by-year history of caps (so old payslips remain accurate)

---

## 13. Social Insurance — Expats (Law 148/2019, post-2019 changes)

### Major change in 2019
- **Expats now contribute on the same terms as Egyptians**
- Same 11% employee / 18.75% employer rates
- Same caps apply

### Director special rate (commercial register)
- Directors whose names appear on the commercial register: **21% employer rate** (instead of 18.75%)
- Calculated on the **maximum threshold only** (not actual wage)
- Tracked via `is_on_commercial_register` flag on employee record

### SI claim-back on departure
- Expat permanently leaving Egypt can **reclaim contributions**
- Must submit request **within 6 months of termination**
- System notifies expat on termination if they're leaving Egypt
- HR provides documentation package (contributions ledger)

### Foreign worker quotas (Labor Law Art. 17)
- **Foreign employees ≤ 10% of total headcount**
- **Foreign compensation ≤ 35% of total payroll**
- Dashboard widget shows real-time compliance (per Decision/ANA-3.20)
- New expat hire blocked at red status with override option (HR admin only, logged)

---

## 14. Income tax (Income Tax Law 91/2005, current 2026 brackets)

### Personal allowance
- **EGP 20,000/year tax-free** (applies to all employees)

### Progressive brackets (2026 rates)
| Annual taxable income (EGP) | Rate |
|---|---|
| 0 – 40,000 | **0%** (when added to personal allowance, effective 0% up to 60,000) |
| 40,001 – 55,000 | **10%** |
| 55,001 – 70,000 | **15%** |
| 70,001 – 200,000 | **20%** |
| 200,001 – 400,000 | **22.5%** |
| 400,001 – 1,200,000 | **25%** |
| 1,200,001 – 2,400,000 | **27.5%** |
| 2,400,001+ | **30%** |

### Calculation method
- **Annualize monthly salary** (× 12) for bracket determination
- **Subtract personal allowance** (EGP 20,000)
- **Subtract SI contributions** (employee portion deductible)
- Apply progressive brackets to remaining
- Divide by 12 for monthly tax withholding
- **Round half-up** to nearest piaster

### Implementation
- Tax brackets stored in `tax_brackets` table, year-versioned
- Calculator function: `calculateMonthlyIncomeTax(annualSalary, year)`
- Unit tests must verify every bracket transition (the off-by-one risk)
- Year-by-year history retained for old payslip accuracy

### Annual reconciliation
- By January 31: generate annual tax summary per employee
- Form 6 auto-generation per employee at year-end (per Decision/ANA-4.14)
- Bilingual format (Arabic + English)
- Stored permanently in employee documents

---

## 15. Mandatory annual raise (Labor Law Art. 12)

- **Minimum 3% annual raise of insurable wage**
- Applied **January 1** of each calendar year
- Applies to all employees with **>1 year tenure**
- Minimum, not ceiling (performance raises can exceed)
- Must update SI contribution base going forward

### YZH-specific (per Decision/ANA-2.9)
- System applies the 3% minimum automatically
- Anything beyond 3% added manually by HR
- Tracked: `annual_raise_applied: { year: amount }` per employee for audit

---

## 16. Minimum wage (set by National Wages Council)

- **Current 2026: EGP 7,000/month** for private sector
- Updated annually by government decree
- System validation:
  - Salary < minimum wage = blocking error
  - Salary 1-10% above minimum = warning
  - Historical minimum wages retained (so old contracts remain accurate)
- Configurable in Settings

---

## 17. Work-on-holiday compensation

### YZH approach (per Decision 17)
- Public holidays = **day off by default** for all employees
- HR creates "Holiday Work Request" if someone needs to work
- Employee not obligated to respond
- **If employee shows up and checks in: triple pay automatically applied** to next payslip
- If employee doesn't show up: no penalty, no formal refusal needed
- Volunteer check-ins (no HR request) get triple pay but flagged for HR review

### Triple pay calculation
- 3× regular daily wage for the entire day worked
- Added as line item on next payslip with description: "Holiday Work — [holiday name]"

---

## 18. Holiday rule clarification (Labor Law Art. 86)

### YZH practice (per Decision 9)
- Holiday on **weekday = paid day off** (normal)
- Holiday on **weekend (Friday or Saturday) = lost** (no replacement day)
- Government can declare "make-up holiday" — HR applies manually via bulk holiday tool

### Public holidays in Egypt (typical year)
- January 7 — Coptic Christmas
- January 25 — Revolution Day
- April 25 — Sinai Liberation Day
- May 1 — Labor Day
- June 30 — June 30 Revolution Day
- July 23 — Revolution Day (1952)
- October 6 — Armed Forces Day
- Eid Al-Fitr (lunar — typically 3-4 days)
- Eid Al-Adha (lunar — typically 4 days)
- Islamic New Year (lunar — 1 day)
- Prophet's Birthday (lunar — 1 day)
- Coptic Easter Monday (variable — 1 day)

### Implementation
- Holidays stored in `holidays` table per organization
- HR can bulk-import for the year
- Lunar dates require manual entry annually
- Recurring vs one-time flag

---

## 19. Personal Data Protection Law (PDPL — Law 151/2020)

### Key principles
- **Lawful basis** required for collecting personal data (employment contract = lawful basis for HR data)
- **Explicit consent** required for biometric data (face photos)
- **Right to access:** employees can request all data held on them
- **Right to correct:** employees can request corrections
- **Right to deletion:** within retention limits (employment data must be kept 7 years post-termination per Labor Law)
- **Data security:** encryption at rest, encryption in transit
- **Breach notification:** within 72 hours to authorities + affected individuals

### Compliance requirements for YZH-HR
- **Consent flow on first login:** photo, location, data sharing
- **Self-service data export** endpoint (per Decision/SWE-3.10)
- **Audit log** all access to sensitive data
- **Encryption:** at rest (database + storage) and in transit (HTTPS)
- **Retention policy:** active employment + 7 years post-termination
- **Penalty for non-compliance:** EGP 100,000 to EGP 5 million depending on violation

### Specific PDPL gotchas
- **Biometric data** (face photos for verification per Decision 7) requires explicit consent
- **Cross-border data transfer** requires authority approval
- **Personal Data Protection Center** is the regulator (under Ministry of Communications)

---

## 20. Audit and record-keeping requirements

### Mandatory records
- Employment contracts (kept for duration of employment + 7 years post)
- Work permits and residency permits for expats (kept current + history)
- Attendance records (1 year minimum for inspection)
- Payroll records (5 years for tax authority)
- Social insurance records (entire employment + indefinite for pension)
- Termination documentation (5 years minimum)

### Implementation
- Soft delete only (`deleted_at` timestamp, never hard delete)
- Audit log every create/update/delete on employee data (Decision 8)
- Document retention policy enforced via background job that excludes data older than retention period from active queries (but doesn't delete it)

---

## 21. Foreign worker compliance details

### Documentation required
- Valid passport (tracked + expiry alerts)
- Work permit (employer-sponsored, 6-12 month renewals, 2-3 month processing)
- Residency permit / visa (tracked + expiry)
- Police clearance from home country (one-time)
- Educational certificates (verified)

### Contract language
- If employee doesn't speak Arabic: contract in **Arabic + their language**
- **Arabic version prevails legally**

### Quotas (Art. 17)
- Foreign employees ≤ 10% of total headcount
- Foreign compensation ≤ 35% of total payroll
- Sectors with national security implications may have stricter limits
- Quota check runs on every new expat hire attempt

---

## 22. Common compliance pitfalls to avoid

When implementing features, watch for:

1. **Tax bracket off-by-one errors** — boundary days at brackets must be unit-tested
2. **SI cap missing for high earners** — silent over-deduction if cap logic wrong
3. **Probation extending past 3 months** — even by accident
4. **Sick leave certificate not enforced after 3 days** — auto-reject without
5. **Maternity termination not blocked** — must hard-block in code
6. **Annual raise not applied January 1** — schedule must run reliably
7. **Notice period skipped for fixed-term contracts** — different rules
8. **EOSB calculated on basic only** — must include regular allowances
9. **Public holiday in weekend treated as paid day off** — wrong, it's lost
10. **Foreign quota check bypassed on bulk import** — must run on each
11. **Resignation processed without authentication** — illegal post-2025
12. **Deemed resignation auto-finalized** — must require HR confirmation
13. **Sick leave 75/85% boundary wrong** — day 90 vs day 91
14. **Director SI rate not applied** — must check commercial register flag
15. **Retirement worked past 60** — must flag or auto-process
16. **Local timezone bug in payroll period** — must bucket in Africa/Cairo

---

## 23. Updates and revisions

This document must be updated when:
- Annual SI caps change (every January)
- Tax brackets change (typically with annual budget)
- Minimum wage changes (typically annual)
- New labor law amendments published in Official Gazette
- New PDPL implementation regulations published
- Court rulings clarify ambiguous rules

**Update process:**
1. Walid notices a change (or HR informs)
2. Update relevant section here with new citation
3. Update related code (system_settings, calculations)
4. Add unit tests for new boundary
5. Note change in `PROGRESS.md` for the day
6. Commit with `compliance: update [rule] per [law/regulation]`

---

## Quick reference: key numbers (2026)

| Item | Value |
|---|---|
| Minimum wage | EGP 7,000/month |
| SI insurable min | EGP 2,300/month |
| SI insurable max | EGP 14,500/month |
| SI employee rate | 11% |
| SI employer rate | 18.75% |
| Health insurance employee | 1% |
| Health insurance employer | 3.25% |
| Training fund (employer) | 0.25% |
| Director SI rate (employer) | 21% |
| Annual raise minimum | 3% of insurable wage |
| Personal income tax allowance | EGP 20,000/year |
| Probation maximum | 3 months |
| Notice period (indefinite, post-probation) | 3 months |
| Annual leave (year 1) | 15 days |
| Annual leave (year 2+) | 21 days |
| Annual leave (10+ years OR age 50+) | 30 days |
| Annual leave (disabled) | 45 days |
| Casual leave | 7 days/year (max 2 consecutive) |
| Sick leave (year max) | 6 months total |
| Maternity leave | 120 days @ 100%, max 3 instances |
| Paternity leave | 1 day, max 3 instances |
| Retirement age | 60 |
| Foreign worker headcount cap | 10% |
| Foreign worker compensation cap | 35% |
| Working hours daily max | 8 (regular) + 2 (overtime) = 10 |
| Working hours weekly max | 48 |
| Overtime rate (regular) | 1.5x |
| Overtime rate (rest day) | 2x |
| Overtime rate (public holiday) | 3x |
| Document retention post-termination | 7 years |
| PDPL breach notification window | 72 hours |
