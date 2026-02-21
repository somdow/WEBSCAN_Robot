# Security Audit Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Gather Application Context">
  <action>Request application details:</action>
  <ask>
    Let's audit your application's security. Tell me:
    1. What type of application? (web app, API, mobile backend)
    2. Tech stack? (language, framework, database)
    3. Authentication method? (sessions, JWT, OAuth)
    4. What sensitive data do you handle? (PII, payments, health)
    5. Any existing security measures?
    6. Compliance requirements? (SOC2, GDPR, PCI-DSS, HIPAA)
    7. Can I review the codebase or is this documentation-based?
  </ask>
  <action>If code access granted, review key files</action>
</step>

<step n="2" goal="OWASP Top 10 Assessment">
  <action>Evaluate against OWASP Top 10 2021:</action>

  **A01: Broken Access Control**
  - Authorization checks on all endpoints
  - IDOR vulnerabilities
  - Missing function-level access control
  - CORS misconfiguration

  **A02: Cryptographic Failures**
  - Data encryption at rest
  - Data encryption in transit (TLS)
  - Weak algorithms
  - Hardcoded secrets

  **A03: Injection**
  - SQL injection (parameterized queries?)
  - Command injection
  - LDAP injection
  - XPath injection

  **A04: Insecure Design**
  - Business logic flaws
  - Missing rate limiting
  - Lack of defense in depth

  **A05: Security Misconfiguration**
  - Default credentials
  - Unnecessary features enabled
  - Error messages exposing info
  - Missing security headers

  <template-output section="owasp-top5">Document findings for A01-A05</template-output>
</step>

<step n="3" goal="OWASP Top 10 (Continued)">
  <action>Continue OWASP assessment:</action>

  **A06: Vulnerable Components**
  - Outdated dependencies
  - Known CVEs
  - Unmaintained libraries

  **A07: Auth & Session Failures**
  - Weak passwords allowed
  - Missing MFA
  - Session fixation
  - Insecure session storage

  **A08: Data Integrity Failures**
  - Unsigned updates
  - Deserialization vulnerabilities
  - CI/CD pipeline security

  **A09: Logging & Monitoring Failures**
  - Insufficient logging
  - Missing alerting
  - No audit trail

  **A10: SSRF**
  - Server-side request forgery
  - URL validation

  <template-output section="owasp-bottom5">Document findings for A06-A10</template-output>
</step>

<step n="4" goal="Authentication & Authorization Deep Dive">
  <action>Detailed auth review:</action>

  **Authentication:**
  - Password policy strength
  - Brute force protection
  - Account lockout
  - Password reset flow
  - MFA implementation

  **Authorization:**
  - Role-based access control
  - Attribute-based access control
  - Principle of least privilege
  - Token validation

  **Session Management:**
  - Session timeout
  - Secure cookie flags
  - Session invalidation on logout

  <template-output section="auth">Document auth findings</template-output>
</step>

<step n="5" goal="Data Protection Assessment">
  <action>Review data handling:</action>

  **Data Classification:**
  - What sensitive data exists
  - Where is it stored
  - Who has access

  **Encryption:**
  - At-rest encryption
  - In-transit encryption
  - Key management

  **Data Lifecycle:**
  - Retention policies
  - Secure deletion
  - Backup security

  <template-output section="data">Document data protection findings</template-output>
</step>

<step n="6" goal="Generate Security Report">
  <action>Compile findings with risk ratings:</action>

  **Risk Levels:**
  - Critical: Actively exploitable, high impact
  - High: Exploitable with moderate effort
  - Medium: Requires specific conditions
  - Low: Minimal impact or unlikely

  <action>Provide remediation guidance for each finding</action>
  <action>Save report to output file</action>
  <action>Present executive summary with critical findings</action>
</step>

</workflow>
