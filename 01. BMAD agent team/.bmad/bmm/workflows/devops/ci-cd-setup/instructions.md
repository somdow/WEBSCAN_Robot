# CI/CD Setup Workflow Instructions

<critical>The workflow execution engine is governed by: {project_root}/.bmad/core/tasks/workflow.xml</critical>

<workflow>

<step n="1" goal="Gather Requirements">
  <action>Understand the project needs:</action>
  <ask>
    Let's design your CI/CD pipeline. Tell me:
    1. What's your tech stack? (language, framework, build tools)
    2. Where's your code hosted? (GitHub, GitLab, Bitbucket, Azure DevOps)
    3. Where do you deploy? (AWS, GCP, Azure, Kubernetes, VPS, PaaS)
    4. Current deployment process? (manual, semi-automated, existing CI)
    5. Any compliance requirements? (approval gates, audit logs)
    6. Team size and branching strategy?
  </ask>
</step>

<step n="2" goal="Design CI Pipeline">
  <action>Design the Continuous Integration pipeline:</action>

  **Build Stage:**
  - Dependency installation
  - Compilation/transpilation
  - Asset building
  - Artifact creation

  **Test Stage:**
  - Unit tests
  - Integration tests
  - Code coverage thresholds
  - Static analysis (linting, type checking)

  **Security Stage:**
  - Dependency vulnerability scanning
  - SAST (Static Application Security Testing)
  - Secrets detection
  - License compliance

  **Quality Gates:**
  - Minimum coverage requirements
  - No critical vulnerabilities
  - All tests passing
  - Linting passes

  <template-output section="ci-pipeline">Document CI pipeline design</template-output>
</step>

<step n="3" goal="Design CD Pipeline">
  <action>Design the Continuous Deployment pipeline:</action>

  **Environments:**
  - Development (auto-deploy on merge)
  - Staging (auto-deploy, mirrors production)
  - Production (manual approval or auto)

  **Deployment Strategy:**
  - Rolling deployment
  - Blue-green deployment
  - Canary releases
  - Feature flags

  **Rollback Strategy:**
  - Automatic rollback triggers
  - Manual rollback procedure
  - Database migration rollbacks

  <template-output section="cd-pipeline">Document CD pipeline design</template-output>
</step>

<step n="4" goal="Select Tools">
  <action>Recommend CI/CD tooling:</action>

  **CI/CD Platforms:**
  | Platform | Best For | Considerations |
  |----------|----------|----------------|
  | GitHub Actions | GitHub repos, simplicity | Free tier limits |
  | GitLab CI | GitLab repos, full DevOps | Self-hosted option |
  | Jenkins | Complex pipelines, flexibility | Maintenance overhead |
  | CircleCI | Speed, parallelization | Cost at scale |
  | Azure DevOps | Microsoft stack | Enterprise features |

  **Supporting Tools:**
  - Artifact storage (S3, GCR, ECR, Artifactory)
  - Secrets management (Vault, AWS Secrets Manager)
  - Notifications (Slack, Teams, PagerDuty)

  <template-output section="tooling">Document tool recommendations</template-output>
</step>

<step n="5" goal="Generate Pipeline Configuration">
  <action>Create pipeline configuration files:</action>

  **Based on selected platform, generate:**
  - Main pipeline configuration
  - Environment-specific overrides
  - Reusable workflow templates
  - Secret references

  **Include:**
  - Caching strategies
  - Parallelization
  - Conditional execution
  - Matrix builds (if applicable)

  <template-output section="configuration">Include pipeline config code</template-output>
</step>

<step n="6" goal="Documentation & Runbook">
  <action>Create operational documentation:</action>

  - Pipeline architecture diagram
  - How to trigger deployments
  - How to rollback
  - Troubleshooting common failures
  - Adding new services/environments

  <action>Save complete documentation to output file</action>
  <action>Present summary with immediate next steps</action>
</step>

</workflow>
