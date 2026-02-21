# Authentication Review Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Understand Auth Architecture">
  <action>Gather authentication details:</action>
  <ask>
    Let's review your authentication system. Tell me:
    1. What auth method? (session-based, JWT, OAuth 2.0, SAML)
    2. Identity provider? (self-managed, Auth0, Okta, Firebase, Cognito)
    3. User types and roles?
    4. MFA implemented?
    5. Password reset flow?
    6. Social login options?
    7. API authentication method?
  </ask>
</step>

<step n="2" goal="Password Security Review">
  <action>Assess password handling:</action>

  **Password Policy:**
  - Minimum length (12+ recommended)
  - Complexity requirements
  - Common password blocking
  - Password history

  **Password Storage:**
  - Hashing algorithm (bcrypt, Argon2, scrypt)
  - Salt usage
  - Work factor/iterations

  **Password Reset:**
  - Token generation (secure random)
  - Token expiration
  - One-time use enforcement
  - Account enumeration prevention

  <template-output section="passwords">Document password security</template-output>
</step>

<step n="3" goal="Session Management Review">
  <action>Assess session handling:</action>

  **Session Creation:**
  - Session ID generation (cryptographically secure)
  - Session binding (IP, user agent)

  **Session Storage:**
  - Server-side vs client-side
  - Storage security

  **Session Protection:**
  - Secure flag on cookies
  - HttpOnly flag
  - SameSite attribute
  - Session timeout (idle and absolute)

  **Session Termination:**
  - Logout invalidation
  - Session revocation capability
  - Concurrent session handling

  <template-output section="sessions">Document session management</template-output>
</step>

<step n="4" goal="Token Security Review (JWT/OAuth)">
  <action>If using tokens, assess:</action>

  **JWT Security:**
  - Algorithm used (RS256 recommended)
  - Secret/key strength
  - Token expiration (short-lived)
  - Refresh token rotation
  - Token revocation strategy

  **OAuth 2.0 (if applicable):**
  - Grant types used
  - PKCE implementation
  - State parameter usage
  - Redirect URI validation
  - Scope management

  <template-output section="tokens">Document token security</template-output>
</step>

<step n="5" goal="Authorization Review">
  <action>Assess access control:</action>

  **Access Control Model:**
  - RBAC (Role-Based Access Control)
  - ABAC (Attribute-Based Access Control)
  - Permission granularity

  **Implementation:**
  - Authorization checks on every endpoint
  - Consistent enforcement
  - Default deny
  - Principle of least privilege

  **Common Vulnerabilities:**
  - IDOR (Insecure Direct Object Reference)
  - Missing function-level access control
  - Privilege escalation paths

  <template-output section="authorization">Document authorization review</template-output>
</step>

<step n="6" goal="Generate Auth Review Report">
  <action>Compile findings:</action>
  - Current auth architecture
  - Security strengths
  - Vulnerabilities found
  - Risk ratings
  - Remediation recommendations
  - Best practice gaps

  <action>Save to output file</action>
  <action>Present critical findings and quick wins</action>
</step>

</workflow>
