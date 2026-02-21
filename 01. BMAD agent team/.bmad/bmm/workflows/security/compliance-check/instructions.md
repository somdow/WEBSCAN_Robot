# Compliance Check Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Identify Applicable Frameworks">
  <action>Determine compliance requirements:</action>
  <ask>
    Let's assess your compliance posture. Tell me:
    1. What data do you handle? (PII, health, financial, children's)
    2. Where are your users located? (US, EU, California, etc.)
    3. What industry? (healthcare, finance, e-commerce, SaaS)
    4. Do you have existing compliance certifications?
    5. Are customers requesting specific compliance? (SOC2, etc.)
    6. Do you process payments?
  </ask>

  **Framework Mapping:**
  - PII + EU users → GDPR
  - PII + California → CCPA
  - Health data → HIPAA
  - Payment cards → PCI-DSS
  - Enterprise SaaS → SOC 2
  - Children's data → COPPA
</step>

<step n="2" goal="SOC 2 Assessment (if applicable)">
  <action>Evaluate SOC 2 Trust Service Criteria:</action>

  **Security (Common Criteria):**
  - Access controls
  - Network security
  - Change management
  - Risk assessment
  - Incident response

  **Availability:**
  - Uptime monitoring
  - Disaster recovery
  - Capacity planning

  **Confidentiality:**
  - Data classification
  - Encryption
  - Access restrictions

  **Processing Integrity:**
  - Input validation
  - Error handling
  - Quality assurance

  **Privacy:**
  - Notice and consent
  - Data minimization
  - Retention and disposal

  <template-output section="soc2">Document SOC 2 assessment</template-output>
</step>

<step n="3" goal="GDPR Assessment (if applicable)">
  <action>Evaluate GDPR requirements:</action>

  **Lawful Basis:**
  - Consent mechanisms
  - Legitimate interest documentation
  - Contract necessity

  **Data Subject Rights:**
  - Right to access
  - Right to rectification
  - Right to erasure (right to be forgotten)
  - Right to data portability
  - Right to object

  **Technical Measures:**
  - Privacy by design
  - Data protection impact assessment
  - Breach notification process
  - Data processing agreements

  <template-output section="gdpr">Document GDPR assessment</template-output>
</step>

<step n="4" goal="PCI-DSS Assessment (if applicable)">
  <action>Evaluate PCI-DSS requirements:</action>

  **Build Secure Network:**
  - Firewall configuration
  - No vendor defaults

  **Protect Cardholder Data:**
  - Data storage minimization
  - Encryption of transmission

  **Vulnerability Management:**
  - Anti-malware
  - Secure development

  **Access Control:**
  - Need-to-know basis
  - Unique IDs
  - Physical access

  **Monitoring:**
  - Logging and monitoring
  - Regular testing

  **Security Policy:**
  - Documented policies
  - Security awareness

  <template-output section="pci">Document PCI-DSS assessment</template-output>
</step>

<step n="5" goal="Gap Analysis">
  <action>Identify compliance gaps:</action>

  **For each applicable framework:**
  - Current state vs requirements
  - Gap severity (critical, major, minor)
  - Remediation effort estimate
  - Evidence needed for audit

  <template-output section="gaps">Document gap analysis</template-output>
</step>

<step n="6" goal="Generate Compliance Report">
  <action>Compile compliance assessment:</action>
  - Applicable frameworks summary
  - Current compliance status
  - Gap analysis with priorities
  - Remediation roadmap
  - Evidence collection checklist

  <action>Save to output file</action>
  <action>Present compliance readiness score and top gaps</action>
</step>

</workflow>
