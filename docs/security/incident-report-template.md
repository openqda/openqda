# Security Incident Report Template

## Incident Information

### Incident ID
**Incident Number:** [Unique identifier - e.g., INC-2024-001]  
**Report Date:** [Date the report was created]  
**Report Author:** [Name and role of the person completing this report]  
**Report Status:** [Draft | Under Review | Final]

### Incident Overview
**Incident Title:** [Brief descriptive title]  
**Discovery Date/Time:** [When was the incident first discovered]  
**Incident Start Date/Time:** [When did the incident actually begin]  
**Incident End Date/Time:** [When was the incident contained/resolved]  
**Current Status:** [Active | Contained | Resolved | Under Investigation]

---

## Executive Summary

[Provide a high-level, non-technical summary of the incident. This section should be understandable by executive management and should include:
- What happened
- When it happened
- Impact on the organization
- Current status
- Key actions taken]

**Key Points:**
- 
- 
- 

---

## Incident Classification

### Incident Type
- [ ] Data Breach
- [ ] Malware/Ransomware
- [ ] Unauthorized Access
- [ ] Denial of Service (DoS/DDoS)
- [ ] Phishing/Social Engineering
- [ ] Insider Threat
- [ ] System Compromise
- [ ] Data Loss/Corruption
- [ ] Physical Security Breach
- [ ] Other: [Specify]

### Severity Level
- [ ] **Critical** - Severe impact on business operations, data breach of sensitive information, significant financial loss
- [ ] **High** - Major impact on business operations, potential data breach, moderate financial loss
- [ ] **Medium** - Limited impact on business operations, minor data exposure, low financial loss
- [ ] **Low** - Minimal or no impact on business operations, no data breach, negligible financial loss

### Confidence Level
- [ ] **Confirmed** - Incident verified through multiple sources and evidence
- [ ] **Likely** - Strong indicators suggest incident occurred
- [ ] **Possible** - Some indicators present but not conclusive
- [ ] **Unknown** - Insufficient information to determine

---

## Detection and Identification

### How Was the Incident Detected?
- [ ] Automated monitoring/alerting system
- [ ] Security tool (SIEM, IDS/IPS, antivirus, etc.)
- [ ] User report
- [ ] Internal audit
- [ ] Third-party notification
- [ ] Other: [Specify]

**Detection Details:**
[Provide details on how the incident was first detected, including specific alerts, logs, or reports]

### Detection Timeline
| Date/Time | Event |
|-----------|-------|
| [Date/Time] | [First indication of suspicious activity] |
| [Date/Time] | [Security team notified] |
| [Date/Time] | [Incident confirmed] |

---

## Incident Timeline

[Document all significant events in chronological order. Include all relevant timestamps in the same timezone.]

| Date/Time (UTC) | Event Description | Actions Taken | Personnel Involved |
|-----------------|-------------------|---------------|-------------------|
| [Date/Time] | [Event] | [Action] | [Name/Role] |
| [Date/Time] | [Event] | [Action] | [Name/Role] |
| [Date/Time] | [Event] | [Action] | [Name/Role] |

---

## Affected Systems and Data

### Systems Affected
| System Name | System Type | IP Address/Identifier | Impact Level | Status |
|-------------|-------------|----------------------|--------------|--------|
| [Name] | [Server/Workstation/Network Device] | [IP/ID] | [Critical/High/Medium/Low] | [Compromised/At Risk/Secure] |

### Data Affected
| Data Type | Classification Level | Records/Volume Affected | Potential Exposure |
|-----------|---------------------|------------------------|-------------------|
| [e.g., User data] | [Public/Internal/Confidential/Restricted] | [Number of records] | [Yes/No/Unknown] |

### User/Customer Impact
**Number of Users/Customers Affected:** [Number or range]  
**Type of Impact:** [Access disruption, data exposure, service unavailability, etc.]  
**Notification Required:** [ ] Yes [ ] No  
**Notification Status:** [Not started | In progress | Completed | N/A]

---

## Attack Vector and Root Cause

### Attack Vector
[Describe how the attacker gained access or how the incident occurred]
- **Entry Point:** [e.g., phishing email, vulnerable web application, misconfigured server]
- **Method:** [e.g., SQL injection, credential stuffing, social engineering]
- **Tools/Techniques Used:** [List any known tools or techniques used by the attacker]

### Vulnerabilities Exploited
1. **Vulnerability Description:** [Describe the vulnerability]
   - **CVE ID (if applicable):** [CVE-XXXX-XXXXX]
   - **Affected Component:** [Software/System component]
   - **Patch Status:** [Available/Not Available]

### Root Cause Analysis
[Provide detailed analysis of what allowed this incident to occur]

**Contributing Factors:**
- 
- 
- 

**Underlying Issues:**
- 
- 
- 

---

## Response Actions Taken

### Immediate Response (Containment)
| Action | Date/Time | Performed By | Result |
|--------|-----------|--------------|--------|
| [e.g., Isolated affected system] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Disabled compromised accounts] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Blocked malicious IP addresses] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |

### Eradication Actions
| Action | Date/Time | Performed By | Result |
|--------|-----------|--------------|--------|
| [e.g., Removed malware] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Applied security patches] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Reset credentials] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |

### Recovery Actions
| Action | Date/Time | Performed By | Result |
|--------|-----------|--------------|--------|
| [e.g., Restored from backup] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Brought systems back online] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |
| [e.g., Verified system integrity] | [Date/Time] | [Name/Team] | [Successful/Failed/Partial] |

---

## Evidence Collection and Preservation

### Digital Evidence Collected
| Evidence Type | Description | Location/Storage | Collected By | Date/Time |
|---------------|-------------|------------------|--------------|-----------|
| [e.g., Log files] | [Description] | [Storage location] | [Name] | [Date/Time] |
| [e.g., Memory dump] | [Description] | [Storage location] | [Name] | [Date/Time] |
| [e.g., Network captures] | [Description] | [Storage location] | [Name] | [Date/Time] |

### Chain of Custody
[Describe how evidence has been handled and who has had access to it]

**Evidence Storage Location:** [Secure location details]  
**Access Restrictions:** [Who has access and under what conditions]

---

## Communication and Notifications

### Internal Notifications
| Stakeholder | Notification Date/Time | Method | Notified By |
|-------------|----------------------|--------|-------------|
| [e.g., IT Management] | [Date/Time] | [Email/Call/Meeting] | [Name] |
| [e.g., Executive Team] | [Date/Time] | [Email/Call/Meeting] | [Name] |
| [e.g., Legal Department] | [Date/Time] | [Email/Call/Meeting] | [Name] |

### External Notifications
| Entity | Notification Date/Time | Method | Reason | Reference Number |
|--------|----------------------|--------|--------|------------------|
| [e.g., Data Protection Authority] | [Date/Time] | [Portal/Email] | [Regulatory requirement] | [Ref #] |
| [e.g., Law Enforcement] | [Date/Time] | [Call/Report] | [Criminal activity] | [Case #] |
| [e.g., Affected Customers] | [Date/Time] | [Email/Portal] | [Data breach notification] | [N/A] |

### Public Communication
**Press Release Issued:** [ ] Yes [ ] No  
**Public Statement:** [Link or text of any public statements made]

---

## Impact Assessment

### Business Impact
**Operational Impact:**
- [e.g., Service downtime duration: X hours]
- [e.g., Number of users unable to access system: X]
- [e.g., Critical business processes affected: List]

**Financial Impact:**
- **Direct Costs:** [e.g., forensic investigation, legal fees, notification costs]
  - Estimated: $[Amount]
  - Actual: $[Amount]
- **Indirect Costs:** [e.g., productivity loss, reputation damage]
  - Estimated: $[Amount]
- **Total Estimated Financial Impact:** $[Amount]

### Reputational Impact
**Media Coverage:** [ ] Yes [ ] No  
**Social Media Impact:** [Brief description]  
**Customer Confidence Impact:** [Low | Medium | High]

### Compliance and Legal Impact
**Regulatory Requirements Triggered:**
- [ ] GDPR (EU General Data Protection Regulation)
- [ ] HIPAA (Health Insurance Portability and Accountability Act)
- [ ] PCI DSS (Payment Card Industry Data Security Standard)
- [ ] SOX (Sarbanes-Oxley Act)
- [ ] Other: [Specify]

**Legal Actions:**
- [ ] None anticipated
- [ ] Under review by legal counsel
- [ ] Pending litigation
- [ ] Active litigation

---

## Lessons Learned

### What Went Well
1. 
2. 
3. 

### What Could Be Improved
1. 
2. 
3. 

### Key Takeaways
1. 
2. 
3. 

---

## Recommendations and Action Items

### Immediate Actions (0-30 days)
| Action Item | Priority | Owner | Due Date | Status |
|-------------|----------|-------|----------|--------|
| [Action] | [Critical/High/Medium/Low] | [Name] | [Date] | [Not Started/In Progress/Completed] |

### Short-term Actions (1-3 months)
| Action Item | Priority | Owner | Due Date | Status |
|-------------|----------|-------|----------|--------|
| [Action] | [Critical/High/Medium/Low] | [Name] | [Date] | [Not Started/In Progress/Completed] |

### Long-term Actions (3-12 months)
| Action Item | Priority | Owner | Due Date | Status |
|-------------|----------|-------|----------|--------|
| [Action] | [Critical/High/Medium/Low] | [Name] | [Date] | [Not Started/In Progress/Completed] |

### Security Controls to Implement/Enhance
- **Preventive Controls:**
  - 
  - 
- **Detective Controls:**
  - 
  - 
- **Corrective Controls:**
  - 
  - 
- **Recovery Controls:**
  - 
  - 

---

## Post-Incident Review Meeting

**Meeting Date:** [Date]  
**Attendees:** [List of participants]  
**Meeting Notes:** [Summary of discussion or link to detailed notes]

**Decisions Made:**
1. 
2. 
3. 

---

## Appendices

### Appendix A: Technical Details
[Include detailed technical information, log excerpts, command outputs, etc.]

### Appendix B: Indicators of Compromise (IoCs)
| IoC Type | Value | Description | Detection Date |
|----------|-------|-------------|----------------|
| [IP Address] | [xxx.xxx.xxx.xxx] | [Description] | [Date] |
| [Domain] | [malicious.example.com] | [Description] | [Date] |
| [File Hash] | [MD5/SHA-256 hash] | [Description] | [Date] |
| [Email Address] | [suspicious@example.com] | [Description] | [Date] |

### Appendix C: Related Documentation
- [Link to forensic analysis report]
- [Link to vulnerability assessment]
- [Link to patch management records]
- [Other relevant documentation]

---

## Approval and Sign-off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Incident Response Lead | [Name] | [Digital signature/approval] | [Date] |
| Security Manager | [Name] | [Digital signature/approval] | [Date] |
| IT Director/CIO | [Name] | [Digital signature/approval] | [Date] |
| Legal Counsel | [Name] | [Digital signature/approval] | [Date] |

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | [Date] | [Name] | Initial report |
| 1.1 | [Date] | [Name] | [Description of changes] |

---

## Sources and References

This security incident report template is based on best practices and frameworks from the following authoritative sources:

### Industry Standards and Frameworks

1. **NIST Computer Security Incident Handling Guide**
   - NIST Special Publication 800-61 Revision 2
   - National Institute of Standards and Technology
   - https://nvlpubs.nist.gov/nistpubs/specialpublications/nist.sp.800-61r2.pdf

2. **ISO/IEC 27035:2016**
   - Information technology — Security techniques — Information security incident management
   - International Organization for Standardization
   - https://www.iso.org/standard/60803.html

3. **SANS Incident Response Process**
   - SANS Institute
   - Preparation, Identification, Containment, Eradication, Recovery, Lessons Learned
   - https://www.sans.org/white-papers/incident-handlers-handbook/

4. **CIS Critical Security Controls**
   - Center for Internet Security
   - Control 17: Incident Response Management
   - https://www.cisecurity.org/controls/

5. **PCI DSS Incident Response Requirements**
   - Payment Card Industry Data Security Standard
   - Requirement 12.10: Implement an incident response plan
   - https://www.pcisecuritystandards.org/

### Regulatory Requirements

6. **GDPR (General Data Protection Regulation)**
   - Article 33: Notification of a personal data breach to the supervisory authority
   - Article 34: Communication of a personal data breach to the data subject
   - https://gdpr-info.eu/

7. **NIS2 Directive (Network and Information Security Directive)**
   - European Union cybersecurity legislation
   - Incident reporting requirements for essential entities
   - https://digital-strategy.ec.europa.eu/en/policies/nis2-directive

8. **HIPAA Security Rule**
   - Security Incident Procedures (45 CFR § 164.308(a)(6))
   - U.S. Department of Health & Human Services
   - https://www.hhs.gov/hipaa/for-professionals/security/index.html

### Additional Resources

9. **CISA Cyber Incident Reporting**
   - Cybersecurity and Infrastructure Security Agency
   - https://www.cisa.gov/incident-notification-guidelines

10. **ENISA Threat Landscape Reports**
    - European Union Agency for Cybersecurity
    - https://www.enisa.europa.eu/topics/threat-risk-management/threats-and-trends

11. **FIRST (Forum of Incident Response and Security Teams)**
    - Best practices for incident response teams
    - https://www.first.org/

12. **MITRE ATT&CK Framework**
    - Adversary tactics and techniques knowledge base
    - https://attack.mitre.org/

13. **OWASP Incident Response Project**
    - Open Web Application Security Project
    - https://owasp.org/www-community/Incident_Response

14. **BSI IT-Grundschutz (German Federal Office for Information Security)**
    - Security incident management guidelines
    - https://www.bsi.bund.de/EN/

15. **Australian Cyber Security Centre (ACSC) Guidelines**
    - Cyber security incident management guidelines
    - https://www.cyber.gov.au/

---

## Notes for Using This Template

- **Completeness:** Not all sections may be applicable to every incident. Adapt as needed.
- **Timeliness:** Complete this report as soon as possible while the incident is still fresh.
- **Accuracy:** Ensure all information is accurate and based on verified evidence.
- **Confidentiality:** This report may contain sensitive information. Handle according to your organization's classification policy.
- **Distribution:** Limit distribution to authorized personnel only.
- **Updates:** This is a living document that should be updated as new information becomes available.
- **Legal Review:** Have legal counsel review before external disclosure.
- **Archive:** Maintain this report for the period required by your organization's retention policy and applicable regulations.
