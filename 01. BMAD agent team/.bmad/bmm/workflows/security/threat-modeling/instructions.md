# Threat Modeling Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Define Scope and Assets">
  <action>Gather system information:</action>
  <ask>
    Let's create a threat model. Tell me:
    1. What system/feature are we modeling?
    2. What are the key assets to protect? (data, functionality, availability)
    3. Who are the users/actors? (admins, users, external systems)
    4. Do you have architecture diagrams?
    5. What's the trust boundary? (what do we control vs external)
  </ask>
  <action>Create or review data flow diagram</action>
</step>

<step n="2" goal="Create Data Flow Diagram">
  <action>Map the system architecture:</action>

  **Components to identify:**
  - External entities (users, third parties)
  - Processes (application logic)
  - Data stores (databases, caches, files)
  - Data flows (how data moves)
  - Trust boundaries (where trust changes)

  <template-output section="dfd">Document data flow diagram</template-output>
</step>

<step n="3" goal="STRIDE Analysis">
  <action>Apply STRIDE to each component:</action>

  **S - Spoofing Identity**
  - Can an attacker pretend to be someone else?
  - Authentication bypass risks

  **T - Tampering with Data**
  - Can data be modified in transit or at rest?
  - Integrity protection measures

  **R - Repudiation**
  - Can actions be denied?
  - Audit logging adequacy

  **I - Information Disclosure**
  - Can sensitive data be exposed?
  - Confidentiality controls

  **D - Denial of Service**
  - Can the system be made unavailable?
  - Resource exhaustion risks

  **E - Elevation of Privilege**
  - Can users gain unauthorized access?
  - Privilege escalation paths

  <template-output section="stride">Document STRIDE findings per component</template-output>
</step>

<step n="4" goal="Risk Assessment">
  <action>Rate each threat:</action>

  **DREAD Scoring (1-10 each):**
  - Damage: How bad is the impact?
  - Reproducibility: How easy to reproduce?
  - Exploitability: How easy to exploit?
  - Affected Users: How many impacted?
  - Discoverability: How easy to find?

  **Risk = (D + R + E + A + D) / 5**

  **Priority:**
  - 8-10: Critical - Immediate action
  - 5-7: High - Address soon
  - 3-4: Medium - Plan to address
  - 1-2: Low - Accept or defer

  <template-output section="risk">Document risk ratings</template-output>
</step>

<step n="5" goal="Define Mitigations">
  <action>Recommend controls for each threat:</action>

  **Control Types:**
  - Preventive: Stop the attack
  - Detective: Identify when attack occurs
  - Corrective: Respond and recover

  **For each threat:**
  - Current controls (if any)
  - Recommended controls
  - Residual risk after mitigation
  - Implementation effort

  <template-output section="mitigations">Document mitigations</template-output>
</step>

<step n="6" goal="Generate Threat Model Document">
  <action>Compile complete threat model:</action>
  - System overview and DFD
  - Asset inventory
  - Threat catalog with STRIDE
  - Risk ratings with DREAD
  - Mitigation roadmap
  - Assumptions and out-of-scope items

  <action>Save to output file</action>
  <action>Present top threats and recommended actions</action>
</step>

</workflow>
